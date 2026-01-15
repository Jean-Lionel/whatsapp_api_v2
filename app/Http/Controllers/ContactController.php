<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function sideBarContacts(Request $request)
    {
        $adminNumber = '+25779000001';

        // Sous-requête pour obtenir l'ID du dernier message par numéro de contact
        $latestMessagesSubquery = Message::query()
            ->select(DB::raw('MAX(id) as id'))
            ->where('direction', 'in')
            ->groupBy('from_number');

        // Requête principale avec pagination native
        $contacts = Message::query()
            ->joinSub($latestMessagesSubquery, 'latest', function ($join) {
                $join->on('messages.id', '=', 'latest.id');
            })
            ->select([
                'messages.id',
                'messages.from_number as phone',
                'messages.from_number as name',
                'messages.body as last_message',
                'messages.created_at as last_message_at',
            ])
            ->selectSub(
                Message::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('from_number', 'messages.from_number')
                    ->where('direction', 'in')
                    ->where('status', '!=', 'read'),
                'unread_count'
            )
            ->orderBy('messages.created_at', 'desc')
            ->paginate(15);

        // Ajouter l'avatar à chaque contact
        $contacts->getCollection()->transform(function ($contact) {
            $contact->avatar = 'https://ui-avatars.com/api/?name='.urlencode($contact->name).'&background=0D8ABC&color=fff';

            return $contact;
        });

        return response()->json($contacts);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $user->contacts();

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

        $user = Auth::user();

        $contact = $user->contacts()->create([
            'name' => $request->name,
            'country_code' => $request->country_code,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return response()->json($contact, 201);
    }

    public function show(Contact $contact)
    {
        if ($contact->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        if ($contact->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
        if ($contact->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contact->delete();

        return response()->json(['message' => 'Contact deleted']);
    }
}
