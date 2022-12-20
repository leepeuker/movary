<?php declare(strict_types=1);

namespace Movary;

use Doctrine\DBAL;
use Dotenv\Dotenv;
use GuzzleHttp;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Movary\Api\Github\GithubApi;
use Movary\Api\Tmdb;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Api\Trakt\Cache\User\Movie\Watched;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\TraktClient;
use Movary\Command;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\SettingsController;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;
use Movary\Service\JobProcessor;
use Movary\Service\UrlGenerator;
use Movary\Util\File;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Config;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\Http\Request;
use OutOfBoundsException;
use Phinx\Console\PhinxApplication;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Twig;

class Factory
{
    public static function createConfig() : Config
    {
        $dotenv = Dotenv::createMutable(__DIR__ . '/..');
        $dotenv->safeLoad();

        return Config::createFromEnv();
    }

    public static function createCurrentHttpRequest() : Request
    {
        return Request::createFromGlobals();
    }

    public static function createDatabaseMigrationMigrateCommand(ContainerInterface $container) : Command\DatabaseMigrationMigrate
    {
        return new Command\DatabaseMigrationMigrate(
            $container->get(PhinxApplication::class),
            __DIR__ . '/../settings/phinx.php'
        );
    }

    public static function createDatabaseMigrationRollbackCommand(ContainerInterface $container) : Command\DatabaseMigrationRollback
    {
        return new Command\DatabaseMigrationRollback(
            $container->get(PhinxApplication::class),
            __DIR__ . '/../settings/phinx.php'
        );
    }

    public static function createDatabaseMigrationStatusCommand(ContainerInterface $container) : Command\DatabaseMigrationStatus
    {
        return new Command\DatabaseMigrationStatus(
            $container->get(PhinxApplication::class),
            __DIR__ . '/../settings/phinx.php'
        );
    }

    public static function createDbConnection(Config $config) : DBAL\Connection
    {
        return DBAL\DriverManager::getConnection(
            [
                'charset' => $config->getAsString('DATABASE_CHARSET'),
                'dbname' => $config->getAsString('DATABASE_NAME'),
                'port' => $config->getAsInt('DATABASE_PORT'),
                'user' => $config->getAsString('DATABASE_USER'),
                'password' => $config->getAsString('DATABASE_PASSWORD'),
                'host' => $config->getAsString('DATABASE_HOST'),
                'driver' => $config->getAsString('DATABASE_DRIVER'),
            ],
        );
    }

    public static function createHttpClient() : ClientInterface
    {
        return new GuzzleHttp\Client();
    }

    public static function createImageCacheService(ContainerInterface $container) : ImageCacheService
    {
        return new ImageCacheService(
            $container->get(File::class),
            $container->get(LoggerInterface::class),
            $container->get(ClientInterface::class),
            $container->get(DBAL\Connection::class),
            __DIR__ . '/../public/',
            '/images/cached/',
        );
    }

    public static function createJobQueueScheduler(ContainerInterface $container, Config $config) : JobQueueScheduler
    {
        try {
            $enableImageCaching = $config->getAsBool('TMDB_ENABLE_IMAGE_CACHING');
        } catch (OutOfBoundsException) {
            $enableImageCaching = false;
        }

        return new JobQueueScheduler(
            $container->get(JobQueueApi::class),
            $enableImageCaching
        );
    }

    public static function createLineFormatter(Config $config) : LineFormatter
    {
        $formatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);
        $formatter->includeStacktraces($config->getAsBool('LOG_ENABLE_STACKTRACE'));

        return $formatter;
    }

    public static function createLogger(ContainerInterface $container, Config $config) : LoggerInterface
    {
        $logger = new Logger('movary');

        $logger->pushHandler(self::createLoggerStreamHandlerStdout($container, $config));

        if ($config->getAsBool('LOG_ENABLE_FILE_LOGGING') === true) {
            $logger->pushHandler(self::createLoggerStreamHandlerFile($container, $config));
        }

        return $logger;
    }

    public static function createSettingsController(ContainerInterface $container, Config $config) : SettingsController
    {
        try {
            $applicationVersion = $config->getAsString('APPLICATION_VERSION');
        } catch (OutOfBoundsException) {
            $applicationVersion = null;
        }

        return new SettingsController(
            $container->get(Twig\Environment::class),
            $container->get(JobQueueApi::class),
            $container->get(Authentication::class),
            $container->get(UserApi::class),
            $container->get(MovieApi::class),
            $container->get(GithubApi::class),
            $container->get(SessionWrapper::class),
            $applicationVersion
        );
    }

    public static function createTmdbApiClient(ContainerInterface $container, Config $config) : Tmdb\TmdbClient
    {
        return new Tmdb\TmdbClient(
            $container->get(ClientInterface::class),
            $config->getAsString('TMDB_API_KEY')
        );
    }

    public static function createTraktApi(ContainerInterface $container) : TraktApi
    {
        return new TraktApi(
            $container->get(TraktClient::class),
            $container->get(Watched\Service::class),
        );
    }

    public static function createTwigEnvironment(ContainerInterface $container) : Twig\Environment
    {
        $twig = new Twig\Environment($container->get(Twig\Loader\LoaderInterface::class));

        $currentRequest = $container->get(Request::class);
        $routeUsername = $currentRequest->getRouteParameters()['username'] ?? null;

        $userAuthenticated = $container->get(Authentication::class)->isUserAuthenticated();

        $twig->addGlobal('loggedIn', $userAuthenticated);

        $user = null;
        $dateFormatPhp = DateFormat::getPhpDefault();
        $dataFormatJavascript = DateFormat::getJavascriptDefault();
        if ($userAuthenticated === true) {
            $currentUserId = $container->get(Authentication::class)->getCurrentUserId();

            /** @var User\UserEntity $user */
            $user = $container->get(User\UserApi::class)->fetchUser($currentUserId);

            $dateFormatPhp = DateFormat::getPhpById($user->getDateFormatId());
            $dataFormatJavascript = DateFormat::getJavascriptById($user->getDateFormatId());
        }

        $twig->addGlobal('currentUsername', $user?->getName());
        $twig->addGlobal('routeUsername', $routeUsername ?? $user?->getName());
        $twig->addGlobal('dateFormatPhp', $dateFormatPhp);
        $twig->addGlobal('dateFormatJavascript', $dataFormatJavascript);
        $twig->addGlobal('requestUrlPath', self::createCurrentHttpRequest()->getPath());

        return $twig;
    }

    public static function createTwigFilesystemLoader() : Twig\Loader\FilesystemLoader
    {
        return new Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
    }

    public static function createUrlGenerator(ContainerInterface $container, Config $config) : UrlGenerator
    {
        try {
            $enableImageCaching = $config->getAsBool('TMDB_ENABLE_IMAGE_CACHING');
        } catch (OutOfBoundsException) {
            $enableImageCaching = false;
        }

        return new UrlGenerator(
            $container->get(TmdbUrlGenerator::class),
            $container->get(ImageCacheService::class),
            $enableImageCaching
        );
    }

    private static function createLoggerStreamHandlerFile(ContainerInterface $container, Config $config) : StreamHandler
    {
        $streamHandler = new StreamHandler(
            __DIR__ . '/../tmp/app.log',
            $config->getAsString('LOG_LEVEL')
        );
        $streamHandler->setFormatter($container->get(LineFormatter::class));

        return $streamHandler;
    }

    private static function createLoggerStreamHandlerStdout(ContainerInterface $container, Config $config) : StreamHandler
    {
        $streamHandler = new StreamHandler('php://stdout', $config->getAsString('LOG_LEVEL'));
        $streamHandler->setFormatter($container->get(LineFormatter::class));

        return $streamHandler;
    }

    public function createProcessJobCommand(ContainerInterface $container, Config $config) : Command\ProcessJobs
    {
        try {
            $minRuntimeInSeconds = $config->getAsInt('MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING');
        } catch (OutOfBoundsException) {
            $minRuntimeInSeconds = null;
        }

        return new Command\ProcessJobs(
            $container->get(JobQueueApi::class),
            $container->get(JobProcessor::class),
            $container->get(LoggerInterface::class),
            $minRuntimeInSeconds,
        );
    }
}
