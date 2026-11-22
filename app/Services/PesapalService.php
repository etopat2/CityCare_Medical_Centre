<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PesapalService
{
    public function registerIpn(string $url): array
    {
        return $this->request('post', '/api/URLSetup/RegisterIPN', [
            'url' => $url,
            'ipn_notification_type' => 'GET',
        ]);
    }

    /** @param array<string, mixed> $order */
    public function submitOrder(array $order): array
    {
        return $this->request('post', '/api/Transactions/SubmitOrderRequest', $order);
    }

    /** @return array<string, mixed> */
    public function transactionStatus(string $trackingId): array
    {
        return $this->request('get', '/api/Transactions/GetTransactionStatus', query: [
            'orderTrackingId' => $trackingId,
        ]);
    }

    /** @param array<string, mixed>|null $body
     *  @param array<string, string>|null $query
     *  @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null, ?array $query = null): array
    {
        $key = (string) config('services.pesapal.consumer_key');
        $secret = (string) config('services.pesapal.consumer_secret');

        if ($key === '' || $secret === '') {
            throw new RuntimeException('Pesapal credentials are not configured.');
        }

        $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
        $tokenResponse = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($baseUrl.'/api/Auth/RequestToken', [
                'consumer_key' => $key,
                'consumer_secret' => $secret,
            ])
            ->throw()
            ->json();

        $token = is_array($tokenResponse) ? ($tokenResponse['token'] ?? null) : null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Pesapal did not return an access token.');
        }

        $request = Http::withToken($token)->acceptJson()->asJson()->timeout(30);
        $response = $method === 'get'
            ? $request->get($baseUrl.$path, $query ?? [])
            : $request->post($baseUrl.$path, $body ?? []);

        return $response->throw()->json() ?? throw new RuntimeException('Pesapal returned an empty response.');
    }
}
