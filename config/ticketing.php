<?php

// App-specific settings: reservation hold time and the local-only demo payment switch.

return [
    'reservation_minutes' => 15,
    // Local demonstration only. Never enable this as a real payment gateway.
    'demo_payments' => env('DEMO_PAYMENTS', false),
];
