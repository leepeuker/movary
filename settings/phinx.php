<?php declare(strict_types=1);

/** @var DI\Container $container */
$container = require(__DIR__ . '/../bootstrap.php');

$config = $container->get(Movary\ValueObject\Config::class);

return [
    'paths' => [
        'migrations' => __DIR__ . '/../db/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'dynamic',
        'dynamic' => [
            'adapter' => $config->getAsString('DATABASE_DRIVER') === 'pdo_mysql' ? 'mysql' : $config->getAsString('database.driver'),
            'host' => $config->getAsString('DATABASE_HOST'),
            'port' => $config->getAsString('DATABASE_PORT'),
            'name' => $config->getAsString('DATABASE_NAME'),
            'user' => $config->getAsString('DATABASE_USER'),
            'pass' => $config->getAsString('DATABASE_PASSWORD'),
            'charset' => $config->getAsString('DATABASE_CHARSET'),
            'collation' => 'utf8_unicode_ci',
        ],
    ],
];
