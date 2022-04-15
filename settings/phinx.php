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
        $config->getAsString('environment.system') => [
            'adapter' => $config->getAsString('database.driver'),
            'host' => $config->getAsString('database.host'),
            'name' => $config->getAsString('database.name'),
            'user' => $config->getAsString('database.username'),
            'pass' => $config->getAsString('database.password'),
            'charset' => $config->getAsString('database.charset'),
            'collation' => 'utf8_unicode_ci',
        ],
    ],
];
