<?php
return [
    'provider' => $_ENV['SMS_PROVIDER'] ?? 'africas_talking',
    'sender_id' => null, // Left null so Africa's Talking uses verified default shortcode routing in Malawi
    'africastalking' => [
        'username' => $_ENV['SMS_USERNAME'] ?? 'ziweto',
        'api_key' => $_ENV['SMS_API_KEY'] ?? 'atsk_064dc082a3f8be360fdc8fc44c761d6b8e4ac360c5470ee49cc44b66167e36efbffe2408',
        'sandbox' => false,
    ],
    'cost_per_segment_mwk' => 25,
];
