<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactController extends Controller
{
    public function sideBarContacts(Request $request){
        $collection = Message::with('contact')->latest()->get()
        ->unique('to_number')
        ->map(fn ($m) => [
            'name' => $m->contact->name ?? $m->to_number,
            'phone' => $m->to_number ?? null,
            'last_message' => $m->body,
            'last_message_at' => $m->created_at,
            'unread_count' => rand(0, 10),
            'avatar' => 'https://ui-avatars.com/api/?name='.($m->contact->name ?? $m->to_number).'&background=0D8ABC&color=fff',
            ])
            ->values();
            
            $page = request('page', 1);
            $perPage = 20;
            
            $contacts = new LengthAwarePaginator(
                $collection->forPage($page, $perPage),
                $collection->count(),
                $perPage,
                $page
            );
            
            
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
    