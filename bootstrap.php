<?php declare(strict_types=1);

use Movary\Factory;

require_once(__DIR__ . '/vendor/autoload.php');

$builder = new DI\ContainerBuilder();
$builder->addDefinitions(
    [
        \Movary\ValueObject\Config::class => DI\factory([Factory::class, 'createConfig']),
        \Movary\Api\Trakt\TraktApi::class => DI\factory([Factory::class, 'createTraktApi']),
        \Movary\Service\ImageCacheService::class => DI\factory([Factory::class, 'createImageCacheService']),
        \Movary\JobQueue\JobQueueScheduler::class => DI\factory([Factory::class, 'createJobQueueScheduler']),
        \Movary\Api\Tmdb\TmdbClient::class => DI\factory([Factory::class, 'createTmdbApiClient']),
        \Movary\Service\UrlGenerator::class => DI\factory([Factory::class, 'createUrlGenerator']),
        \Movary\HttpController\SettingsController::class => DI\factory([Factory::class, 'createSettingsController']),
        \Movary\HttpController\PlexController::class => DI\factory([Factory::class, 'createPlexController']),
        \Movary\ValueObject\Http\Request::class => DI\factory([Factory::class, 'createCurrentHttpRequest']),
        \Movary\Command\DatabaseMigrationStatus::class => DI\factory([Factory::class, 'createDatabaseMigrationStatusCommand']),
        \Movary\Command\DatabaseMigrationMigrate::class => DI\factory([Factory::class, 'createDatabaseMigrationMigrateCommand']),
        \Movary\Command\DatabaseMigrationRollback::class => DI\factory([Factory::class, 'createDatabaseMigrationRollbackCommand']),
        \Movary\Command\ProcessJobs::class => DI\factory([Factory::class, 'createProcessJobCommand']),
        \Psr\Http\Client\ClientInterface::class => DI\factory([Factory::class, 'createHttpClient']),
        \Psr\Log\LoggerInterface::class => DI\factory([Factory::class, 'createLogger']),
        \PDO::class => DI\factory([Factory::class, 'createPdo']),
        \Doctrine\DBAL\Connection::class => DI\factory([Factory::class, 'createDbConnection']),
        \Twig\Loader\LoaderInterface::class => DI\factory([Factory::class, 'createTwigFilesystemLoader']),
        \Twig\Environment::class => DI\factory([Factory::class, 'createTwigEnvironment']),
        \Monolog\Formatter\LineFormatter::class => DI\factory([Factory::class, 'createLineFormatter']),
    ],
);

return $builder->build();
