<?php

return [
    'email' => [
        'recipient' => ['josephm2800@gmail.com'],
        'bcc' => [],
        'cc' => [],
        'subject' => 'An error was occured - ' . env('APP_NAME'),
    ],

    'disabledOn' => [
        'local',
    ],

    'cacheCooldown' => 10, // in minutes
];
