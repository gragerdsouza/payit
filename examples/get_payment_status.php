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
];

$client = new PayitClient($config);
$paymentId = $argv[1] ?? 'payment-id-from-create';

try {
    $status = $client->getPaymentStatus($paymentId);
    print_r($status);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
