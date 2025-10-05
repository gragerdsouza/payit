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
$banks = $client->listBanks(['country' => 'GB']); // adjust query per docs
print_r($banks);
