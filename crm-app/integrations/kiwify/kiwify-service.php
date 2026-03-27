<?php
/*
 * integrations/kiwify/kiwify-service.php
 * Kiwify API Service Layer
 */

class KiwifyService {
    private const API_BASE = 'https://public-api.kiwify.com/v1';
    private string $apiKey;

    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey ?: (defined('KIWIFY_API_KEY') ? KIWIFY_API_KEY : '');
    }

    public function getOrders(int $page = 1, string $status = 'paid'): array {
        return $this->request('/orders', ['page' => $page, 'status' => $status]);
    }

    public function getProducts(): array {
        return $this->request('/products');
    }

    private function request(string $path, array $params = []): array {
        $url  = self::API_BASE . $path . ($params ? '?' . http_build_query($params) : '');
        $ctx  = stream_context_create(['http' => [
            'header'  => "Authorization: Bearer {$this->apiKey}\r\nContent-Type: application/json\r\n",
            'timeout' => 15,
        ]]);
        $raw  = file_get_contents($url, false, $ctx);
        if ($raw === false) return ['error' => 'Connection failed'];
        return json_decode($raw, true) ?? [];
    }
}
