<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Endpoints
{
    // ========================
    // AUTHENTICATION
    // ========================

    #[OA\Post(
        path: '/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/login',
        summary: 'Connexion d\'un utilisateur',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Identifiants invalides'),
        ]
    )]
    public function login(): void {}

    // ========================
    // CONTACTS
    // ========================

    #[OA\Get(
        path: '/contacts',
        summary: 'Liste des contacts',
        tags: ['Contacts'],
        security: [['ApiKeyAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des contacts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Contact')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function contactsIndex(): void {}

    #[OA\Post(
        path: '/contacts',
        summary: 'Créer un contact',
        tags: ['Contacts'],
        security: [['ApiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'phone', type: 'string', example: '25779000000'),
                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Contact créé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function contactsStore(): void {}

    // ========================
    // MESSAGES
    // ========================

    #[OA\Get(
        path: '/messages',
        summary: 'Liste des messages',
        tags: ['Messages'],
        security: [['ApiKeyAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des messages',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Message')),
                    ]
                )
            ),
        ]
    )]
    public function messagesIndex(): void {}

    #[OA\Post(
        path: '/send_whatsapp',
        summary: 'Envoyer un message WhatsApp',
        description: 'Envoie un message texte ou template via l\'API WhatsApp Business',
        tags: ['Messages'],
        security: [['ApiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'message'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string', description: 'Numéro de téléphone avec indicatif pays', example: '25779000000'),
                    new OA\Property(property: 'message', type: 'string', description: 'Contenu du message', example: 'Bonjour, comment allez-vous?'),
                    new OA\Property(property: 'template', type: 'string', description: 'Nom du template (optionnel)', example: 'hello_world'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message envoyé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message_id', type: 'string', example: 'wamid.xxxxx'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
            new OA\Response(response: 429, description: 'Rate limit dépassé'),
        ]
    )]
    public function sendWhatsApp(): void {}

    // ========================
    // API KEYS
    // ========================

    #[OA\Get(
        path: '/api-keys',
        summary: 'Liste des clés API',
        tags: ['API Keys'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des clés API',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ApiKey')),
                    ]
                )
            ),
        ]
    )]
    public function apiKeysIndex(): void {}

    #[OA\Post(
        path: '/api-keys',
        summary: 'Créer une clé API',
        tags: ['API Keys'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Production Key'),
                    new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string', enum: ['read', 'write', 'send_messages']), example: ['read', 'write', 'send_messages']),
                    new OA\Property(property: 'rate_limit', type: 'integer', minimum: 10, maximum: 1000, example: 100),
                    new OA\Property(property: 'expires_in_days', type: 'integer', minimum: 1, maximum: 365, example: 90),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Clé API créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'key', type: 'string', description: 'La clé complète (affichée une seule fois)'),
                            new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string')),
                        ]),
                        new OA\Property(property: 'warning', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function apiKeysStore(): void {}

    // ========================
    // WEBHOOKS
    // ========================

    #[OA\Get(
        path: '/webhooks',
        summary: 'Liste des webhooks',
        tags: ['Webhooks'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des webhooks',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Webhook')),
                    ]
                )
            ),
        ]
    )]
    public function webhooksIndex(): void {}

    #[OA\Post(
        path: '/webhooks',
        summary: 'Créer un webhook',
        description: 'Crée un webhook pour recevoir des notifications sur votre endpoint',
        tags: ['Webhooks'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'url', 'events'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'My Webhook'),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://myapp.com/webhook'),
                    new OA\Property(
                        property: 'events',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['message.received', 'message.sent', 'message.failed', 'contact.created', 'contact.updated', '*']),
                        example: ['message.received', 'message.sent']
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Webhook créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'url', type: 'string'),
                            new OA\Property(property: 'secret', type: 'string', description: 'Le secret pour valider les signatures (affiché une seule fois)'),
                            new OA\Property(property: 'events', type: 'array', items: new OA\Items(type: 'string')),
                        ]),
                        new OA\Property(property: 'warning', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function webhooksStore(): void {}

    #[OA\Post(
        path: '/webhooks/{id}/test',
        summary: 'Tester un webhook',
        description: 'Envoie une requête de test à votre endpoint webhook',
        tags: ['Webhooks'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Test réussi'),
            new OA\Response(response: 502, description: 'Le webhook n\'a pas répondu correctement'),
        ]
    )]
    public function webhooksTest(): void {}

    // ========================
    // WHATSAPP CONFIGURATIONS
    // ========================

    #[OA\Get(
        path: '/whatsapp-configurations',
        summary: 'Liste des configurations WhatsApp',
        description: 'Récupère toutes les configurations WhatsApp de l\'utilisateur',
        tags: ['WhatsApp Configurations'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des configurations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WhatsappConfiguration')),
                    ]
                )
            ),
        ]
    )]
    public function configurationsIndex(): void {}

    #[OA\Post(
        path: '/whatsapp-configurations',
        summary: 'Créer une configuration WhatsApp',
        description: 'Crée une nouvelle configuration WhatsApp pour l\'utilisateur',
        tags: ['WhatsApp Configurations'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'api_token', 'phone_id', 'phone_number', 'business_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Production WhatsApp'),
                    new OA\Property(property: 'api_url', type: 'string', example: 'https://graph.facebook.com/v22.0/'),
                    new OA\Property(property: 'api_version', type: 'string', example: 'v22.0'),
                    new OA\Property(property: 'api_token', type: 'string', description: 'Token d\'accès WhatsApp Business API'),
                    new OA\Property(property: 'phone_id', type: 'string', example: '123456789'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '25779000000'),
                    new OA\Property(property: 'business_id', type: 'string', example: '987654321'),
                    new OA\Property(property: 'verify_token', type: 'string', description: 'Token de vérification webhook'),
                    new OA\Property(property: 'is_default', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Configuration créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function configurationsStore(): void {}

    #[OA\Post(
        path: '/whatsapp-configurations/{id}/set-default',
        summary: 'Définir comme configuration par défaut',
        tags: ['WhatsApp Configurations'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Configuration définie par défaut'),
            new OA\Response(response: 404, description: 'Configuration non trouvée'),
        ]
    )]
    public function configurationsSetDefault(): void {}

    #[OA\Post(
        path: '/whatsapp-configurations/{id}/test',
        summary: 'Tester la connexion WhatsApp',
        description: 'Teste la connexion à l\'API WhatsApp avec cette configuration',
        tags: ['WhatsApp Configurations'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Connection successful'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'templates_count', type: 'integer', example: 5),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Échec de la connexion'),
        ]
    )]
    public function configurationsTest(): void {}
}
