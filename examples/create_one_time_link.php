<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Payit\PayitClientLink;

// Load environment.
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = [
    'base_url' => $_ENV['PAYIT_BASE_URL'],
    'token_url' => $_ENV['OAUTH_TOKEN_URL'],
    'client_id' => $_ENV['CLIENT_ID'],
    'client_secret' => $_ENV['CLIENT_SECRET'],
    'resource' => $_ENV['RESOURCE'], // Only if still needed, v2 vs v3
    'environment' => $_ENV['PAYIT_ENV'],
];

$client = new PayitClientLink($config);

// Step 1: Create a Payment Link
$linkPayload = [
    'companyId' => $_ENV['COMPANY_ID'],
    'brandId' => $_ENV['BRAND_ID'],
    'amount' => 12.34,
    'currency' => 'GBP',
    'fpReference' => 'HOSTEL-DEPOSIT-001',
    'description' =>  "tAf7Sd",
    //'autoInitiate' => true,
    'paymentContext' => [
        'paymentContextCode' => 'BillPayment',
        'merchantCategoryCode' => '123',
        'deliveryAddress' => [
            'countryCode' => 'GB',
            'townName' => 'London'
        ]
    ],
    'redirectUrl' => 'https://www.example.com/redirect.php', // user is redirected here after approval
    'linkExpiry' => 'P0Y0M3DT0H0M0S',
    'notification' => [
        'notificationUrl' => 'https://www.example.com/redirect.php',
        'notificationStyle' => 'Payit', // PLG and Payit
        'events' => [
            'Successful'
        ],
    ],
    //'minAmount' => 12.34,
    //'maxAmount' => 15.00
];

$linkResponse = $client->createOneTimeLink($linkPayload);

if (empty($linkResponse['paymentLink'])) {
    die("Failed to create payment link: " . json_encode($linkResponse));
}

$redirectUrl = $linkResponse['paymentLink'];

// Step 3: Redirect user to Payit
header("Location: " . $redirectUrl);
exit;
