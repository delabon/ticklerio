<?php

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => 'mysql-service',
            'port' => 3306,
            'name' => 'ticklerio',
            'user' => 'test',
            'pass' => '12345',
            'charset' => 'utf8mb4',
            'fetch_mode' => 'assoc',
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'memory' => true,
            'name' => 'ticklerio',
            'charset' => 'utf8mb4',
            'fetch_mode' => 'assoc',
        ]
    ],
    'version_order' => 'creation'
];
