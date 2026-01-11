<?php

namespace App\Http\Controllers;

use App\Models\WhatsappData;
use App\Http\Requests\StoreWhatsappDataRequest;
use App\Http\Requests\UpdateWhatsappDataRequest;

class WhatsappDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $whatsappData = WhatsappData::all();
        return response()->json($whatsappData);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWhatsappDataRequest $request)
    {
        $whatsappData = WhatsappData::create($request->validated());
        return response()->json($whatsappData, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WhatsappData $whatsappData)
    {
        return response()->json($whatsappData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WhatsappData $whatsappData)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWhatsappDataRequest $request, WhatsappData $whatsappData)
    {
        $whatsappData->update($request->validated());
        return response()->json($whatsappData);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WhatsappData $whatsappData)
    {
        $whatsappData->delete();
        return response()->json(['message' => 'WhatsappData deleted successfully']);
    }
}
