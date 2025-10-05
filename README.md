# Payit PHP Client

This repository contains a small PHP client for the Payit API (NatWest / Payit). It demonstrates:

- OAuth2 client_credentials token retrieval
- Basic endpoints: list banks, create payments, check payment status
- Example scripts

## Setup

1. Copy `.env.example` to `.env` and fill in your sandbox/production credentials.
2. `composer install`
3. Run examples: `php examples/get_token.php`

## Notes

- Replace placeholder endpoints and payloads with the exact shapes from the Payit API documentation.
- Do not store client secrets in source control.
- Use the sandbox environment for testing before going live.