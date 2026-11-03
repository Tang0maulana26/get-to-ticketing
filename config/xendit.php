<?php

return [
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'public_key' => env('XENDIT_PUBLIC_KEY'),
    'api_url' => env('XENDIT_API_URL', 'https://api.xendit.co'),
    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
];

