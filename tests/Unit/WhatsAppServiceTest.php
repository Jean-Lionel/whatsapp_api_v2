<?php

namespace Tests\Unit;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    protected WhatsAppService $whatsAppService;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuration pour les tests
        config([
            'services.whatsapp.api_url' => 'https://graph.instagram.com/v18.0/',
            'services.whatsapp.api_token' => 'test_token_123',
            'services.whatsapp.phone_id' => 'test_phone_id_123',
            'services.whatsapp.phone_number' => '1234567890',
            'services.whatsapp.business_account_id' => 'test_business_id_123',
        ]);

        $this->whatsAppService = new WhatsAppService();
    }

    /**
     * Test: Récupérer les templates avec succès
     */
    public function test_get_available_templates_success()
    {
        Http::fake([
            'https://graph.instagram.com/v18.0/test_business_id_123/message_templates' => Http::response([
                'data' => [
                    [
                        'id' => 'template_1',
                        'name' => 'hello_world',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'language' => 'fr',
                        'components' => [
                            [
                                'type' => 'BODY',
                                'text' => 'Bonjour {1}!',
                            ],
                        ],
                        'created_at' => '2026-01-15T10:00:00Z',
                    ],
                    [
                        'id' => 'template_2',
                        'name' => 'order_confirmation',
                        'status' => 'APPROVED',
                        'category' => 'TRANSACTIONAL',
                        'language' => 'fr',
                        'components' => [
                            [
                                'type' => 'BODY',
                                'text' => 'Votre commande {1} a été confirmée',
                            ],
                        ],
                        'created_at' => '2026-01-14T12:30:00Z',
                    ],
                ],
                'paging' => [
                    'cursors' => [
                        'before' => 'before_cursor',
                        'after' => 'after_cursor',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->whatsAppService->getAvaliableTemplate();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['templates']);
        $this->assertEquals('hello_world', $result['templates'][0]['name']);
        $this->assertEquals('APPROVED', $result['templates'][0]['status']);
        $this->assertEquals('order_confirmation', $result['templates'][1]['name']);
        $this->assertNotNull($result['paging']);
    }

    /**
     * Test: Erreur API lors de la récupération des templates
     */
    public function test_get_available_templates_api_error()
    {
        Http::fake([
            'https://graph.instagram.com/v18.0/test_business_id_123/message_templates' => Http::response([
                'error' => [
                    'message' => 'Invalid access token',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $result = $this->whatsAppService->getAvaliableTemplate();

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid access token', $result['error']);
        $this->assertEmpty($result['templates']);
    }

    /**
     * Test: Récupérer les templates avec réponse vide
     */
    public function test_get_available_templates_empty()
    {
        Http::fake([
            'https://graph.instagram.com/v18.0/test_business_id_123/message_templates' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $result = $this->whatsAppService->getAvaliableTemplate();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['templates']);
    }

    /**
     * Test: Exception lors de la connexion API
     */
    public function test_get_available_templates_connection_error()
    {
        Http::fake([
            'https://graph.instagram.com/v18.0/test_business_id_123/message_templates' => Http::response(
                ['error' => 'Connection timeout'],
                500
            ),
        ]);

        $result = $this->whatsAppService->getAvaliableTemplate();

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
        $this->assertEmpty($result['templates']);
    }

    /**
     * Test: Vérifier que les templates ont la bonne structure
     */
    public function test_template_structure()
    {
        Http::fake([
            'https://graph.instagram.com/v18.0/test_business_id_123/message_templates' => Http::response([
                'data' => [
                    [
                        'id' => 'template_1',
                        'name' => 'test_template',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'language' => 'fr',
                        'components' => [],
                        'created_at' => '2026-01-15T10:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->whatsAppService->getAvaliableTemplate();

        $template = $result['templates'][0];
        $this->assertArrayHasKey('id', $template);
        $this->assertArrayHasKey('name', $template);
        $this->assertArrayHasKey('status', $template);
        $this->assertArrayHasKey('category', $template);
        $this->assertArrayHasKey('language', $template);
        $this->assertArrayHasKey('components', $template);
        $this->assertArrayHasKey('created_at', $template);
    }
}
