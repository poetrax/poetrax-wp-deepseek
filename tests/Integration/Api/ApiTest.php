<?php
namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private $baseUrl = 'http://poetrax-local.ru:8086';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Проверяем доступность API
        $ch = curl_init($this->baseUrl . '/api/tracks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 && $httpCode !== 404) {
            $this->markTestSkipped('API not available at ' . $this->baseUrl);
        }
    }
    
    public function testTracksEndpointReturnsSuccess(): void
    {
        $response = $this->makeRequest('/api/tracks');
        $this->assertEquals(200, $response['code']);
    }
    
    public function testTracksSearchEndpointWorks(): void
    {
        $response = $this->makeRequest('/api/tracks/search?q=test');
        $this->assertContains($response['code'], [200, 404]);
    }
    
    private function makeRequest(string $endpoint): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body];
    }
}
