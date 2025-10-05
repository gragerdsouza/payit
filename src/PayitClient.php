<?php
namespace Payit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Payit Client.
 *
 * A simple PHP client wrapper for the Payit (NatWest) API.
 * Handles OAuth2 authentication and provides helper methods
 * for common Payit endpoints such as banks, payments, and status checks.
 *
 * @author  Grager D'souza <gragerdsouza@gmail.com>
 * @version 1.0.0
 */
final class PayitClient
{
    /** @var Client Guzzle HTTP client instance */
    private Client $http;

    /** @var string Base API URL */
    private string $baseUrl;

    /** @var string OAuth2 token URL */
    private string $tokenUrl;

    /** @var string OAuth2 client ID */
    private string $clientId;

    /** @var string OAuth2 client secret */
    private string $clientSecret;

    /** @var string|null Cached access token */
    private ?string $accessToken = null;

    /** @var string|null Resource */
    private ?string $resource = null;

    /** @var int Access token expiry timestamp */
    private int $accessTokenExpiresAt = 0;

    /**
     * PayitClient constructor.
     *
     * @param array $config {
     *   @var string $base_url    Base URL for the Payit API (e.g., sandbox endpoint)
     *   @var string $token_url   OAuth2 token endpoint URL
     *   @var string $client_id   Client ID from Payit / Azure AD
     *   @var string $client_secret Client secret from Payit / Azure AD
     *   @var string $resource Resource
     *   @var float|null $timeout Optional request timeout (seconds)
     * }
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->tokenUrl = $config['token_url'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->resource = $config['resource'];

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 10.0,
        ]);
    }

    /**
     * Ensure that an OAuth2 access token is available and valid.
     * If expired or not present, fetches a new one using client_credentials.
     *
     * @throws RuntimeException If token cannot be retrieved.
     * 
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return string The valid OAuth2 access token
     */
    public function ensureAccessToken(): string
    {
        if ($this->accessToken && time() < $this->accessTokenExpiresAt - 30) {
            return $this->accessToken;
        }

        $http = new Client(['timeout' => 10.0]);

        try {
            $resp = $http->post($this->tokenUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'resource' => $this->resource,
                    //'scope' => 'https://lpapi.natwestpayit.com/.default' // <-- important
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Failed to obtain access token: " . $e->getMessage());
        }

        $body = json_decode((string)$resp->getBody(), true);

        if (empty($body['access_token'])) {
            throw new \RuntimeException("No access_token in token response: " . json_encode($body));
        }

        $this->accessToken = $body['access_token'];
        $expiresIn = isset($body['expires_in']) ? (int)$body['expires_in'] : 3600;
        $this->accessTokenExpiresAt = time() + $expiresIn;

        return $this->accessToken;
    }

    /**
     * Build authorization headers with the current OAuth2 token.
     *
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return array<string,string> Headers including Authorization, Accept, and Content-Type
     */
    private function authHeaders(): array
    {
        $token = $this->ensureAccessToken();

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Retrieve the list of eligible banks supported by Payit.
     *
     * @param array $query Optional query parameters (e.g., ['country' => 'GB']).
     * @throws \RuntimeException If request fails.
     * 
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return array Decoded JSON response containing bank details
     */
    public function listBanks(array $query = []): array
    {
        $resp = $this->http->request('GET', '/eligible-banks', [
            'headers' => $this->authHeaders(),
            'query' => $query
        ]);

        return json_decode((string)$resp->getBody(), true);
    }

    /**
     * Initiate a new payment request.
     *
     * @param array $data Payload that matches Payit API specs for creating a payment.
     * @throws \RuntimeException If request fails.
     * 
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return array Decoded JSON response containing payment details
     */
    public function createPayment(array $data): array
    {
        $resp = $this->http->request('POST', '/lp2nos-merchant/merchant-payments', [
            'headers' => $this->authHeaders(),
            'json' => $data
        ]);

        return json_decode((string)$resp->getBody(), true);
    }

    /**
     * Confirm a previously created payment (if supported).
     *
     * @param string $paymentId ID of the payment to confirm.
     * @param array  $data Optional payload required for confirmation.
     * @throws \RuntimeException If request fails.
     * 
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return array Decoded JSON response with confirmation result.
     */
    public function confirmPayment(string $paymentId, array $data = []): array
    {
        $resp = $this->http->request('POST', "/payments/{$paymentId}/confirm", [
            'headers' => $this->authHeaders(),
            'json' => $data
        ]);

        return json_decode((string)$resp->getBody(), true);
    }

    /**
     * Retrieve the status of a specific payment.
     *
     * @param string $paymentId Unique payment identifier.
     * @throws \RuntimeException If request fails.
     * 
     * @author  Grager D'souza <gragerdsouza@gmail.com>
     * @return array Decoded JSON response containing payment status
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $resp = $this->http->request('GET', "/payments/{$paymentId}/status", [
            'headers' => $this->authHeaders()
        ]);

        return json_decode((string)$resp->getBody(), true);
    }

    /**
     * Placeholder for additional endpoints:
     * - reconciliation file downloads
     * - webhook/callback handlers
     * - refunds
     * Extend this class as per Payit API documentation.
     */
}
