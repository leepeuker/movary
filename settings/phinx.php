<?php declare(strict_types=1);

/** @var DI\Container $container */
$container = require(__DIR__ . '/../bootstrap.php');

$config = $container->get(Movary\ValueObject\Config::class);

$databaseMode = \Movary\Factory::getDatabaseMode($config);
if ($databaseMode === 'sqlite') {
    $sqliteFile = pathinfo($config->getAsString('DATABASE_SQLITE', \Movary\Factory::getDatabaseSqlite($config)));
    $databaseConfig = [
        'adapter' => 'sqlite',
        'name' => $sqliteFile['dirname'] . '/' . $sqliteFile['filename'],
        'suffix' => $sqliteFile['extension'],
    ];
} elseif (\Movary\Factory::getDatabaseMode($config) === 'mysql') {
    $databaseConfig = [
        'adapter' => 'mysql',
        'host' => $config->getAsString('DATABASE_MYSQL_HOST'),
        'port' => \Movary\Factory::getDatabaseMysqlPort($config),
        'name' => $config->getAsString('DATABASE_MYSQL_NAME'),
        'user' => $config->getAsString('DATABASE_MYSQL_USER'),
        'pass' => $config->getAsString('DATABASE_MYSQL_PASSWORD'),
        'charset' => \Movary\Factory::getDatabaseMysqlCharset($config),
        'collation' => 'utf8_unicode_ci',
    ];
} else {
    throw new \RuntimeException('Not supported database mode: ' . $databaseMode);
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/../db/migrations/' . $databaseMode,
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'dynamic',
        'dynamic' => $databaseConfig,
    ],
];
