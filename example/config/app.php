<?php
declare(strict_types=1);

return [
    'debug' => 3,
    'timezone' => 'Europe/Warsaw',
    'db' => [
        'dsn' => 'sqlite:' . dirname(__DIR__) . '/data/app.db',
    ],
    'view_path' => dirname(__DIR__) . '/templates',
    'log_path' => dirname(__DIR__) . '/data/logs',

    // Hardcoded demo credentials
    'admin_user' => 'admin',
    'admin_pass' => '$2y$12$zDireBbOuHj40qNcks5LJeDtDFXODpDkzhFPwtExTzhNQzKaeGSg.',
];
