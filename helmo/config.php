<?php
// App configuration
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'university_portal',
        'user' => 'root',
        'pass' => '', // set your phpMyAdmin password if any
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/',
        'name' => 'University Portal',
        'default_lang' => 'en',
        'langs' => ['en' => 'English', 'ar' => 'العربية']
    ]
];
