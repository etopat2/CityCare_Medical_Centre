<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/** Handles authenticated HTTP communication with PesaPal's transaction and IPN APIs. */
class PesapalService
{
    /** Builds provider-facing URLs from the configured public origin, including in CLI commands. */
    public function publicRoute(string $routeName): string
    {
        $baseUrl = rtrim((string) config('services.pesapal.public_url'), '/');
        if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https' || ! parse_url($baseUrl, PHP_URL_HOST)) {
            throw new RuntimeException('Set PESAPAL_PUBLIC_URL to the public HTTPS ngrok URL before starting Pesapal payments.');
        }

        $path = URL::route($routeName, [], false);

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /** Converts a saved patient address to the restricted character set accepted by PesaPal. */
    public function formatAddressLine(?string $address): ?string
    {
        if (blank($address)) {
            return null;
        }

        $formatted = Str::ascii($address);
        $formatted = preg_replace('/[^A-Za-z0-9._:@-]+/', '_', $formatted) ?? '';
        $formatted = trim(preg_replace('/_+/', '_', $formatted) ?? '', '_');

        return $formatted === '' ? null : substr($formatted, 0, 100);
    }

    /** Registers the server callback URL with PesaPal so asynchronous payment updates can reach the application. */
    public function registerIpn(string $url): array
    {
        return $this->request('post', '/api/URLSetup/RegisterIPN', [
            'url' => $url,
            'ipn_notification_type' => 'GET',
        ]);
    }

    /** Submits a checkout order and returns PesaPal's tracking and redirect details.
     * @param array<string, mixed> $order
     */
    public function submitOrder(array $order): array
    {
        $response = $this->request('post', '/api/Transactions/SubmitOrderRequest', $order);
        $error = $response['error'] ?? null;

        if (filled($error)) {
            $message = is_array($error) ? ($error['message'] ?? null) : $error;

            throw new RuntimeException(is_string($message) && $message !== ''
                ? 'Pesapal rejected the checkout order: '.$message
                : 'Pesapal rejected the checkout order.');
        }

        if (! filled($response['redirect_url'] ?? null) || ! filled($response['order_tracking_id'] ?? null)) {
            $message = $response['message'] ?? 'The provider response did not include a checkout URL and tracking ID.';

            throw new RuntimeException('Pesapal could not create the checkout order: '.(string) $message);
        }

        return $response;
    }

    /**
     * Queries PesaPal for the current status of a previously submitted order.
     * @return array<string, mixed>
     */
    public function transactionStatus(string $trackingId): array
    {
        return $this->request('get', '/api/Transactions/GetTransactionStatus', query: [
            'orderTrackingId' => $trackingId,
        ]);
    }

    /**
     * Obtains an access token, sends one API request, and raises an exception for failed or empty responses.
     * @param array<string, mixed>|null $body JSON payload for POST endpoints.
     * @param array<string, string>|null $query Query parameters for GET endpoints.
     * @return array<string, mixed> Decoded JSON response from PesaPal.
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
