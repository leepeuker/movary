<?php declare(strict_types=1);

use Movary\Factory;

require_once(__DIR__ . '/vendor/autoload.php');

$builder = new DI\ContainerBuilder();
$builder->addDefinitions(
    [
        \Movary\ValueObject\Config::class => DI\factory([Factory::class, 'createConfig']),
        \Movary\Api\Trakt\Api::class => DI\factory([Factory::class, 'createTraktApi']),
        \Movary\Api\Tmdb\Client::class => DI\factory([Factory::class, 'createTmdbApiClient']),
        \Movary\HttpController\SettingsController::class => DI\factory([Factory::class, 'createSettingsController']),
        \Movary\ValueObject\Http\Request::class => DI\factory([Factory::class, 'createCurrentHttpRequest']),
        \Movary\Command\DatabaseMigrationStatus::class => DI\factory([Factory::class, 'createDatabaseMigrationStatusCommand']),
        \Movary\Command\DatabaseMigrationMigrate::class => DI\factory([Factory::class, 'createDatabaseMigrationMigrateCommand']),
        \Movary\Command\DatabaseMigrationRollback::class => DI\factory([Factory::class, 'createDatabaseMigrationRollbackCommand']),
        \Movary\Command\ProcessJobs::class => DI\factory([Factory::class, 'createProcessJobCommand']),
        \Psr\Http\Client\ClientInterface::class => DI\factory([Factory::class, 'createHttpClient']),
        \Psr\Log\LoggerInterface::class => DI\factory([Factory::class, 'createFileLogger']),
        \Doctrine\DBAL\Connection::class => DI\factory([Factory::class, 'createDbConnection']),
        \Twig\Loader\LoaderInterface::class => DI\factory([Factory::class, 'createTwigFilesystemLoader']),
        \Twig\Environment::class => DI\factory([Factory::class, 'createTwigEnvironment']),
    ]
);

return $builder->build();
