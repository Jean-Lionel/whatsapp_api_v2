<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function sideBarContacts(Request $request)
    {
        $latestMessagesSubquery = Message::query()
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('to_number');

        $contacts = Message::query()
            ->joinSub($latestMessagesSubquery, 'latest', function ($join) {
                $join->on('messages.id', '=', 'latest.id');
            })
            ->select([
                'messages.id',
                'messages.to_number as phone',
                'messages.to_number as name',
                'messages.body as last_message',
                'messages.created_at as last_message_at',
            ])
            ->selectSub(
                Message::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('to_number', 'messages.to_number')
                    ->where('direction', 'in')
                    ->where('status', '!=', 'read'),
                'unread_count'
            )
            ->orderBy('messages.created_at', 'desc')
            ->paginate(15);

        $contacts->getCollection()->transform(function ($contact) {
            $contact->avatar = 'https://ui-avatars.com/api/?name='.urlencode($contact->name).'&background=0D8ABC&color=fff';

            return $contact;
        });

        return response()->json($contacts);
    }

    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('name', 'asc')->paginate();

        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'country_code' => 'nullable|string',
            'phone' => 'nullable|string|unique:contacts,phone',
            'email' => 'nullable|email|unique:contacts,email',
        ]);

        $contact = Contact::create([
            'name' => $request->name,
            'country_code' => $request->country_code,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return response()->json($contact, 201);
    }

    public function show(Contact $contact)
    {
        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'country_code' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $contact->update($request->all());

        return response()->json($contact);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(['message' => 'Contact deleted']);
    }

    /**
     * Sidebar combinee: contacts + groupes tries par dernier message
     */
    public function sidebar(Request $request)
    {
        // Tous les contacts
        $contacts = Contact::all()->map(function ($contact) {
            // Chercher le dernier message pour ce contact
            $lastMessage = Message::where('contact_id', $contact->id)
                ->orWhere(function ($q) use ($contact) {
                    $fullPhone = $contact->full_phone;
                    if ($fullPhone) {
                        $q->where('from_number', $fullPhone)
                            ->orWhere('to_number', $fullPhone);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->first();

            // Compter les messages non lus
            $unreadCount = Message::where(function ($q) use ($contact) {
                $q->where('contact_id', $contact->id);
                $fullPhone = $contact->full_phone;
                if ($fullPhone) {
                    $q->orWhere('from_number', $fullPhone);
                }
            })
                ->where('direction', 'in')
                ->whereNull('read_at')
                ->count();

            return [
                'id' => $contact->id,
                'type' => 'contact',
                'name' => $contact->name,
                'phone' => $contact->full_phone,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($contact->name).'&background=25d366&color=fff',
                'last_message' => $lastMessage?->body,
                'last_message_at' => $lastMessage?->created_at,
                'unread_count' => $unreadCount,
            ];
        });

        // Tous les groupes
        $groups = WhatsappGroup::query()
            ->withCount('contacts as member_count')
            ->with('lastMessage')
            ->get()
            ->map(fn ($group) => [
                'id' => $group->id,
                'type' => 'group',
                'name' => $group->name,
                'member_count' => $group->member_count,
                'avatar' => null,
                'last_message' => $group->lastMessage?->body,
                'last_message_at' => $group->lastMessage?->created_at,
                'unread_count' => 0,
            ]);

        // Fusionner et trier par date du dernier message
        $sidebar = $contacts->concat($groups)
            ->sortByDesc(fn ($item) => $item['last_message_at'] ?? '1970-01-01')
            ->values();

        return response()->json($sidebar);
    }
}
