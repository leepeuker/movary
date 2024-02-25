<?php declare(strict_types=1);

use Movary\Factory;

require_once(__DIR__ . '/vendor/autoload.php');

$builder = new DI\ContainerBuilder();
$builder->addDefinitions(
    [
        \Movary\ValueObject\Config::class => DI\factory([Factory::class, 'createConfig']),
        \Movary\Api\Trakt\TraktApi::class => DI\factory([Factory::class, 'createTraktApi']),
        \Movary\Service\ImageCacheService::class => DI\factory([Factory::class, 'createImageCacheService']),
        \Movary\HttpController\Api\ImagesController::class => DI\factory([Factory::class, 'createImagesController']),
        \Movary\JobQueue\JobQueueScheduler::class => DI\factory([Factory::class, 'createJobQueueScheduler']),
        \Movary\Api\Tmdb\TmdbClient::class => DI\factory([Factory::class, 'createTmdbApiClient']),
        \Movary\Service\UrlGenerator::class => DI\factory([Factory::class, 'createUrlGenerator']),
        \Movary\Service\Export\ExportService::class => DI\factory([Factory::class, 'createExportService']),
        \Movary\HttpController\Api\OpenApiController::class => DI\factory([Factory::class, 'createOpenApiController']),
        \Movary\HttpController\Web\CreateUserController::class => DI\factory([Factory::class, 'createCreateUserController']),
        \Movary\HttpController\Web\JobController::class => DI\factory([Factory::class, 'createJobController']),
        \Movary\HttpController\Web\LandingPageController::class => DI\factory([Factory::class, 'createLandingPageController']),
        \Movary\HttpController\Web\Middleware\ServerHasRegistrationEnabled::class => DI\factory([Factory::class, 'createMiddlewareServerHasRegistrationEnabled']),
        \Movary\ValueObject\Http\Request::class => DI\factory([Factory::class, 'createCurrentHttpRequest']),
        \Movary\Command\CreatePublicStorageLink::class => DI\factory([Factory::class, 'createCreatePublicStorageLink']),
        \Movary\Command\DatabaseMigrationStatus::class => DI\factory([Factory::class, 'createDatabaseMigrationStatusCommand']),
        \Movary\Command\DatabaseMigrationMigrate::class => DI\factory([Factory::class, 'createDatabaseMigrationMigrateCommand']),
        \Movary\Command\DatabaseMigrationRollback::class => DI\factory([Factory::class, 'createDatabaseMigrationRollbackCommand']),
        \Movary\Command\ProcessJobs::class => DI\factory([Factory::class, 'createProcessJobCommand']),
        \Psr\Http\Client\ClientInterface::class => DI\factory([Factory::class, 'createHttpClient']),
        \Psr\Log\LoggerInterface::class => DI\factory([Factory::class, 'createLogger']),
        \Doctrine\DBAL\Connection::class => DI\factory([Factory::class, 'createDbConnection']),
        \Twig\Loader\LoaderInterface::class => DI\factory([Factory::class, 'createTwigFilesystemLoader']),
        \Twig\Environment::class => DI\factory([Factory::class, 'createTwigEnvironment']),
        \Monolog\Formatter\LineFormatter::class => DI\factory([Factory::class, 'createLineFormatter']),
    ],
);

$container = $builder->build();

$timezone = $container->get(\Movary\ValueObject\Config::class)->getAsString('TIMEZONE', \Movary\ValueObject\DateTime::DEFAULT_TIME_ZONE);
date_default_timezone_set($timezone);

return $container;
