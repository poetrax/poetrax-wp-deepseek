<?php
namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

class MerchApiTest extends TestCase
{
    private $baseUrl = 'http://poetrax-local.ru:8086';

    public function testGetProductsReturnsSuccess()
    {
        $response = $this->makeRequest('/api/products');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function testGetProductByIdReturnsSuccess()
    {
        $response = $this->makeRequest('/api/products/1');
        $this->assertEquals(200, $response['code']);
        $data = json_decode($response['body'], true);
        $this->assertTrue($data['success']);
    }

    public function testCartOperations()
    {
        // Добавление в корзину
        $addResponse = $this->makeRequest('/api/cart/items', 'POST', [
            'product_id' => 1,
            'quantity' => 1
        ]);
        $this->assertEquals(200, $addResponse['code']);

        // Получение корзины
        $cartResponse = $this->makeRequest('/api/cart');
        $this->assertEquals(200, $cartResponse['code']);

        // Очистка корзины
        $clearResponse = $this->makeRequest('/api/cart', 'DELETE');
        $this->assertEquals(200, $clearResponse['code']);
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null)
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => $body];
    }
}