<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Payit\PayitClient;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = [
    'base_url' => $_ENV['PAYIT_BASE_URL'],
    'token_url' => $_ENV['OAUTH_TOKEN_URL'],
    'client_id' => $_ENV['CLIENT_ID'],
    'client_secret' => $_ENV['CLIENT_SECRET'],
    'resource' => $_ENV['RESOURCE'],
];

$client = new PayitClient($config);
try {
    $token = $client->ensureAccessToken();
    echo "Access token:\n$token\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
