<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;

class ContactController extends Controller
{
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
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
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
