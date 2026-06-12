<?php

// Values come from the environment (docker-compose locally, Apache SetEnv on the server)
return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;dbname=%s',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'missiondeck'
    ),
    'username' => getenv('DB_USER') ?: 'missiondeck',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
