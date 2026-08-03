<?php

return [
    /*
     * Inactivity thresholds (in days since last login, or account creation
     * if the account has never been logged into) that drive the automatic
     * account lifecycle: active -> disabled -> archived -> deleted.
     */
    'inactivity' => [
        'disable_after_days' => env('ACCOUNT_DISABLE_AFTER_DAYS', 15),
        'archive_after_days' => env('ACCOUNT_ARCHIVE_AFTER_DAYS', 25),
        'delete_after_days' => env('ACCOUNT_DELETE_AFTER_DAYS', 30),
    ],
];
