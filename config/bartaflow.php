<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    | Application-wide defaults for new tenants (workspaces). See docs/database.md.
    */
    'defaults' => [
        'timezone' => env('BARTAFLOW_DEFAULT_TIMEZONE', 'Asia/Dhaka'),
        'locale' => 'en',
        'country' => 'BD', // default country for phone normalisation
    ],

    /*
    |--------------------------------------------------------------------------
    | Message retry policy
    |--------------------------------------------------------------------------
    | Used by the messaging queue pipeline (docs/tasks/007). Backoff is a list of
    | seconds to wait between attempts.
    */
    'messages' => [
        'tries' => (int) env('BARTAFLOW_MESSAGE_TRIES', 3),
        'backoff' => array_map(
            'intval',
            explode(',', (string) env('BARTAFLOW_MESSAGE_BACKOFF', '60,300,900'))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV import limits
    |--------------------------------------------------------------------------
    | Used by the contacts import flow (docs/tasks/005).
    */
    'csv' => [
        'max_rows' => (int) env('BARTAFLOW_CSV_MAX_ROWS', 5000),
        'max_size_kb' => (int) env('BARTAFLOW_CSV_MAX_SIZE_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan defaults (reserved for future billing — docs/database.md §4)
    |--------------------------------------------------------------------------
    | Default limits applied before billing ships. Null = unlimited.
    */
    'plan_defaults' => [
        'message_limit' => null,
        'account_limit' => 1,
        'seat_limit' => 3,
        'contact_limit' => null,
    ],

];
