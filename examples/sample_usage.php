<?php

// Autoload Composer packages
require_once 'vendor/autoload.php';

use Payit\PayitClientLink;

$payitClient = new PayitClientLink([
    'base_url'       => 'https://sandbox.payit.com/api',
    'token_url'      => 'https://sandbox.payit.com/oauth2/token',
    'client_id'      => 'your-client-id',
    'client_secret'  => 'your-client-secret',
    'resource'       => 'your-resource',
    'environment'    => 'sandbox', // or 'production'
]);

// Example: Create a one-time payment link
$payload = [
    'amount' => 100.00,
    'currency' => 'GBP',
    'description' => 'Test Payment',
];

try {
    $response = $payitClient->createOneTimeLink($payload);
    echo "One-time payment link created successfully: \n";
    print_r($response);
} catch (Exception $e) {
    echo "Error creating payment link: " . $e->getMessage();
}
