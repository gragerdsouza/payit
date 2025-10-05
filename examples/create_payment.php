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

// Build the payload according to the Payit docs — this is an example shape
$payload = [
    "merchantId" => $_ENV['MERCHANT_ID'] ?? 'merchant-123',
    "amount" => [
        "currency" => "GBP",
        "amount" => "12.34"
    ],
    "reference" => "INV-1001",
    "beneficiary" => [
        "name" => "John Doe",
        "account" => [
            "sortCode" => "123456",
            "accountNumber" => "12345678"
        ]
    ],
    "returnUrl" => "https://yourapp.example.com/payit/return",
    "paymentType" => "Immediate",
    "customerEmail" => "john.doe@example.com"
    // other required fields per docs...
];

try {
    $resp = $client->createPayment($payload);
    print_r($resp);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
