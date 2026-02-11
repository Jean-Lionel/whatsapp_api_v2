<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'WhatsApp API',
    description: 'API publique pour l\'envoi de messages WhatsApp. Utilisez votre clé API pour authentifier vos requêtes.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@example.com'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'ApiKeyAuth',
    type: 'apiKey',
    in: 'header',
    name: 'X-API-Key',
    description: 'Clé API obtenue via le dashboard. Format: wapi_xxxxx'
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Token Sanctum pour l\'accès au dashboard'
)]
#[OA\Tag(
    name: 'Authentication',
    description: 'Inscription et connexion'
)]
#[OA\Tag(
    name: 'Contacts',
    description: 'Gestion des contacts WhatsApp'
)]
#[OA\Tag(
    name: 'Messages',
    description: 'Envoi et consultation des messages'
)]
#[OA\Tag(
    name: 'Groups',
    description: 'Gestion des groupes WhatsApp'
)]
#[OA\Tag(
    name: 'API Keys',
    description: 'Gestion des clés API'
)]
#[OA\Tag(
    name: 'Webhooks',
    description: 'Configuration des webhooks client'
)]
#[OA\Tag(
    name: 'WhatsApp Configurations',
    description: 'Gestion des configurations WhatsApp multi-clients'
)]
#[OA\Schema(
    schema: 'Contact',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'phone', type: 'string', example: '25779000000'),
        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Message',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'phone', type: 'string', example: '25779000000'),
        new OA\Property(property: 'message', type: 'string', example: 'Bonjour!'),
        new OA\Property(property: 'direction', type: 'string', enum: ['incoming', 'outgoing']),
        new OA\Property(property: 'status', type: 'string', example: 'sent'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ApiKey',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Production Key'),
        new OA\Property(property: 'key_preview', type: 'string', example: 'wapi_abc123...'),
        new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string'), example: ['read', 'write', 'send_messages']),
        new OA\Property(property: 'rate_limit', type: 'integer', example: 100),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Webhook',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'My Webhook'),
        new OA\Property(property: 'url', type: 'string', example: 'https://myapp.com/webhook'),
        new OA\Property(property: 'events', type: 'array', items: new OA\Items(type: 'string'), example: ['message.received', 'message.sent']),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'failure_count', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'WhatsappConfiguration',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Production WhatsApp'),
        new OA\Property(property: 'api_url', type: 'string', example: 'https://graph.facebook.com/v22.0/'),
        new OA\Property(property: 'api_version', type: 'string', example: 'v22.0'),
        new OA\Property(property: 'phone_id', type: 'string', example: '123456789'),
        new OA\Property(property: 'phone_number', type: 'string', example: '25779000000'),
        new OA\Property(property: 'business_id', type: 'string', example: '987654321'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Invalid API key'),
        new OA\Property(property: 'message', type: 'string', example: 'The provided API key is invalid or expired'),
    ]
)]
class OpenApiSpec {}
