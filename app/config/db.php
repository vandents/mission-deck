<?php

// Credentials come from the environment: docker-compose sets real env vars locally,
// Apache SetEnv provides them on the server (which surface in $_SERVER under mod_php).
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_SERVER[$key] ?? $default;
    }
    return (string) $value;
};

return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf('mysql:host=%s;dbname=%s', $env('DB_HOST', 'localhost'), $env('DB_NAME', 'missiondeck')),
    'username' => $env('DB_USER', 'missiondeck'),
    'password' => $env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
