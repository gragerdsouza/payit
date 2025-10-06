<?php

require 'vendor/autoload.php';

use Payit\PayitClientLink;

$payitClient = new PayitClientLink([
    'base_url'       => 'https://sandbox.payit.com/api',
    'token_url'      => 'https://sandbox.payit.com/oauth2/token',
    'client_id'      => 'your-client-id',
    'client_secret'  => 'your-client-secret',
    'resource'       => 'your-resource',
    'environment'    => 'sandbox',
]);

$payload = [
    'amount' => 100.00,
    'currency' => 'GBP',
    'description' => 'Test Payment',
];

$response = $payitClient->createOneTimeLink($payload);

print_r($response);
