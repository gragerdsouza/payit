<?php

namespace Payit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Payit\PayitEnvironment;

/**
 * Class PayitClientLink
 *
 * Handles Payit Payment Links (one-time, reusable) and related API operations.
 * Supports refunds, reconciliation, health checks, and information endpoints.
 *
 * @author Grager D'souza <gragerdsouza@gmail.com>
 */
class PayitClientLink
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

    /** @var string|null Environment */
    private ?string $environment = null;

    /**
     * PayitClient constructor.
     *
     * @param array $config {
     *   @var string $base_url    Base URL for the Payit API (e.g., sandbox endpoint).
     *   @var string $token_url   OAuth2 token endpoint URL.
     *   @var string $client_id   Client ID from Payit / Azure AD.
     *   @var string $client_secret Client secret from Payit / Azure AD.
     *   @var string $resource Resource.
     *   @var string $environment Environment.
     *   @var float|null $timeout Optional request timeout (seconds).
     * }
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->tokenUrl = $config['token_url'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->resource = $config['resource'];
        $this->environment = $config['environment'];
        $this->http = $config['http'] ?? new Client(['timeout' => 15.0]);
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

        try {
            $resp = $this->http->post($this->tokenUrl, [
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
     * Generic API request method.
     *
     * @param string $method HTTP method (GET, POST, DELETE).
     * @param string $uri API endpoint path.
     * @param array $options Optional Guzzle options.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array Decoded JSON response
     * @throws \RuntimeException
     */
    private function request(string $method, string $uri, array $options = [])
    {
        $accessToken = $this->ensureAccessToken();

        $options['headers']['Authorization'] = "Bearer {$accessToken}";
        $options['headers']['Accept'] = "application/app.v3+json";
        $options['headers']['Content-Type'] = "application/json";
        $options['headers']['x-api-version'] = "3";
        $options['headers']['x-transaction-id'] = uniqid("txn_");

        $resp = $this->http->request($method, $this->baseUrl . $uri, $options);
        return json_decode((string)$resp->getBody(), true);
    }

    # -------------------------------
    # Links
    # -------------------------------

    /**
     * Create a one-time payment link.
     *
     * @param array $payload Payment link details.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array Response data
     */
    public function createOneTimeLink(array $payload): array
    {
        return $this->request('POST', $this->getLinkUrl('/links'), ['json' => $payload]);
    }

    /**
     * Create a reusable payment link.
     *
     * @param array $payload Payment link details.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array Response data.
     */
    public function createReusableLink(array $payload): array
    {
        return $this->request('POST', $this->getLinkUrl('/reusableLinks'), ['json' => $payload]);
    }

    /**
     * Cancel a one-time link.
     *
     * @param string $linkId Link ID.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function cancelOneTimeLink(string $linkId): array
    {
        return $this->request('DELETE', "/links/{$linkId}");
    }

    /**
     * Cancel a reusable link.
     *
     * @param string $linkId Link ID.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function cancelReusableLink(string $linkId): array
    {
        return $this->request('DELETE', "/reusableLinks/{$linkId}");
    }

    # -------------------------------
    # Historical Data
    # -------------------------------

    /**
     * Retrieve historical one-time link details.
     *
     * @param array $criteria Conditions.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function getLinkDetails(array $criteria): array
    {
        return $this->request('POST', '/linkDetails', ['json' => $criteria]);
    }

    /**
     * Retrieve historical reusable link details.
     *
     * @param array $criteria Conditions.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function getReusableLinkDetails(array $criteria): array
    {
        return $this->request('POST', '/reusableLinkDetails', ['json' => $criteria]);
    }

    /**
     * Retrieve reusable link payment sessions.
     *
     * @param array $criteria Conditions.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function getReusableLinkSessions(array $criteria): array
    {
        return $this->request('POST', '/reusableLinksSessions', ['json' => $criteria]);
    }

    # -------------------------------
    # Refunds
    # -------------------------------

    /**
     * Create a refund request.
     *
     * @param array $payload Payment details.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function requestRefund(array $payload): array
    {
        return $this->request('POST', '/merchant-refunds', ['json' => $payload]);
    }

    /**
     * Confirm a refund.
     *
     * @param array $payload Payment details.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function confirmRefund(array $payload): array
    {
        return $this->request('POST', '/merchant-refunds-confirm', ['json' => $payload]);
    }

    # -------------------------------
    # Reconciliation
    # -------------------------------

    /**
     * Download reconciliation file (binary).
     *
     * @param string $filetype File type.
     * @param string $date Format YYYY-MM-DD.
     * @param string $run Run number.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return string Binary file content.
     */
    public function downloadReconciliation(string $filetype, string $date, string $run): string
    {
        $resp = $this->http->get($this->baseUrl . "/download/reconciliation/{$filetype}/{$date}/{$run}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->ensureAccessToken(),
                'Accept' => 'application/octet-stream',
            ]
        ]);

        return (string)$resp->getBody(); // binary file
    }

    /**
     * Set reconciliation version.
     *
     * @param string $companyId Company ID.
     * @param string $version Version.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function setReconciliationVersion(string $companyId, string $version): array
    {
        return $this->request('POST', "/reconciliation-version/{$companyId}/{$version}");
    }

    # -------------------------------
    # Health + Info
    # -------------------------------

    /**
     * Get POM version information.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function getInfo(): array
    {
        return $this->request('GET', '/info');
    }

    /**
     * Get API health status.
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return array
     */
    public function getHealth(): array
    {
        return $this->request('GET', '/health');
    }

    /**
     * Get full URL for link management endpoints (one-time or reusable).
     *
     * @param string $endpoint Example: '/links' or '/reusableLinks'
     *
     * @author Grager D'souza <gragerdsouza@gmail.com>
     * @return string
     */
    private function getLinkUrl(string $endpoint): string
    {
        // If the base URL already has the service in it, just append endpoint
        if (strpos($this->baseUrl, 'linkmanagementservice') !== false) {
            return rtrim($this->baseUrl, '/') . $endpoint;
        }

        // Otherwise, add default service path based on environment.
        $servicePath = $this->environment === PayitEnvironment::PRODUCTION ? 'prdplg-linkmanagementservice' : 'ppdplg-linkmanagementservice';
        return '/' . $servicePath . $endpoint;
    }
}
