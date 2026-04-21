<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class TracksApiTest extends TestCase
{
    private $baseUrl = 'http://poetrax-local.ru:8086';
    
    public function testGetTracksReturnsSuccess()
    {
        $response = $this->makeRequest('/api/tracks');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }
    
    public function testGetTracksWithPagination()
    {
        $response = $this->makeRequest('/api/tracks?page=2&limit=5');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertEquals(2, $data['data']['page']);
        $this->assertEquals(5, $data['data']['limit']);
    }
    
    public function testGetTrackById()
    {
        $response = $this->makeRequest('/api/tracks/1');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertEquals(1, $data['data']['id']);
    }
    
    public function testSearchTracks()
    {
        $response = $this->makeRequest('/api/tracks/search?q=' . urlencode('прости'));
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertTrue($data['success']);
    }
    
    public function testFilterByLanguage()
    {
        $response = $this->makeRequest('/api/tracks?lang=ru');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertTrue($data['success']);
    }
    
    public function testPopularTracks()
    {
        $response = $this->makeRequest('/api/tracks/popular');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertTrue($data['success']);
    }
    
    public function testCorsPreflight()
    {
        $ch = curl_init($this->baseUrl . '/api/tracks');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Origin: http://localhost:3000',
            'Access-Control-Request-Method: GET'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $headers = $this->parseHeaders($response);
        
        $this->assertArrayHasKey('access-control-allow-origin', $headers);
        $this->assertEquals('*', $headers['access-control-allow-origin']);
    }
    
    private function makeRequest($endpoint)
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body];
    }
    
    private function parseHeaders($response)
    {
        $headers = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }
}