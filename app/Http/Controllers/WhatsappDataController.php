<?php

namespace App\Http\Controllers;

use App\Models\WhatsappData;
use App\Http\Requests\StoreWhatsappDataRequest;
use App\Http\Requests\UpdateWhatsappDataRequest;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsappDataController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $whatsappData = WhatsappData::latest()->paginate(10);
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

    /**
     * Récupérer les templates disponibles avec leurs paramètres
     */
    public function getTemplates(Request $request)
    {
        
        $result = $this->whatsAppService->getAvaliableTemplate();

        if (!$result['success']) {
            return response()->json(
                ['error' => $result['error']],
                400
            );
        }

        // Formater les templates avec leurs paramètres
        $formattedTemplates = array_map(function ($template) {
            return [
                'id' => $template['id'],
                'name' => $template['name'],
                'status' => $template['status'],
                'category' => $template['category'],
                'language' => $template['language'],
                'created_at' => $template['created_at'],
                'parameters' => $this->extractTemplateParameters($template['components']),
            ];
        }, $result['templates']);

        return response()->json([
            'success' => true,
            'count' => count($formattedTemplates),
            'templates' => $formattedTemplates,
            'paging' => $result['paging'] ?? null,
        ]);
    }

    /**
     * Extraire les paramètres d'un template
     */
    private function extractTemplateParameters(array $components): array
    {
        $parameters = [];

        foreach ($components as $component) {
            if ($component['type'] === 'BODY' && isset($component['text'])) {
                // Extraire les paramètres dynamiques de la forme {1}, {2}, etc.
                preg_match_all('/\{(\d+)\}/', $component['text'], $matches);

                if (!empty($matches[1])) {
                    for ($i = 1; $i <= max(array_map('intval', $matches[1])); $i++) {
                        $parameters[] = [
                            'position' => $i,
                            'placeholder' => '{' . $i . '}',
                        ];
                    }
                }
            }
        }

        return $parameters;
    }
}

