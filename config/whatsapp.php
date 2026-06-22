<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp provider configuration
    |--------------------------------------------------------------------------
    | Foundation placeholders only. The client implementation lands in
    | docs/tasks/004-whatsapp-accounts.
    */
    'provider' => env('WHATSAPP_PROVIDER', 'cloud_api'),

    'api_base' => env('WHATSAPP_API_BASE', 'https://graph.facebook.com/v21.0'),

    'rate_per_second' => (int) env('WHATSAPP_RATE_PER_SECOND', 10),

];
