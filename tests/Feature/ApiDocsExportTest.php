<?php

namespace Tests\Feature;

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Tests\TestCase;

class ApiDocsExportTest extends TestCase
{
    public function test_can_export_detailed_api_docs_json(): void
    {
        $response = $this->withoutMiddleware(RestrictedDocsAccess::class)
            ->get('/docs/api/export.json');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="solar-bigbang-frontend-ai-api.json"');

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('generated_at', $payload);
        $this->assertSame('frontend_ai_codegen', $payload['purpose']);
        $this->assertArrayHasKey('base_url', $payload);
        $this->assertArrayHasKey('default_headers', $payload);
        $this->assertArrayHasKey('apis', $payload);

        $registerEndpoint = collect($payload['apis'])
            ->firstWhere('path', '/auth/register');

        $this->assertNotNull($registerEndpoint);
        $this->assertSame('POST', $registerEndpoint['method']);
        $this->assertIsString($registerEndpoint['url']);
        $this->assertIsArray($registerEndpoint['headers']);
        $this->assertIsArray($registerEndpoint['params']);
        $this->assertArrayHasKey('query', $registerEndpoint['params']);
        $this->assertArrayHasKey('path', $registerEndpoint['params']);
        $this->assertArrayHasKey('request', $registerEndpoint);
        $this->assertIsArray($registerEndpoint['responses']);
        $this->assertArrayHasKey('frontend', $registerEndpoint);
        $this->assertArrayHasKey('suggested_headers', $registerEndpoint['frontend']);
    }
}
