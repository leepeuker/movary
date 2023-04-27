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
use Movary\Command\CreatePublicStorageLink;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\CreateUserController;
use Movary\HttpController\JobController;
use Movary\HttpController\SettingsController;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;
use Movary\Service\JobProcessor;
use Movary\Service\Letterboxd\LetterboxdExporter;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Service\ServerSettings;
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
use Psr\Log\LogLevel;
use Twig;

class Factory
{
    private const SRC_DIRECTORY_NAME = 'src';

    private const DEFAULT_MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING = 15;

    private const DEFAULT_DATABASE_MYSQL_CHARSET = 'utf8mb4';

    private const DEFAULT_DATABASE_MYSQL_PORT = 3306;

    private const DEFAULT_LOG_LEVEL = LogLevel::WARNING;

    private const DEFAULT_APPLICATION_VERSION = 'dev';

    private const DEFAULT_TMDB_IMAGE_CACHING = false;

    private const DEFAULT_LOG_ENABLE_STACKTRACE = false;

    private const DEFAULT_ENABLE_FILE_LOGGING = true;

    public static function createConfig() : Config
    {
        $dotenv = Dotenv::createMutable(self::createDirectoryAppRoot());
        $dotenv->safeLoad();

        return Config::createFromEnv();
    }

    public static function createCreatePublicStorageLink(ContainerInterface $container) : CreatePublicStorageLink
    {
        return new CreatePublicStorageLink(
            $container->get(File::class),
            self::createDirectoryStorageApp(),
            self::createDirectoryAppRoot(),
        );
    }

    public static function createCreateUserController(ContainerInterface $container, Config $config) : CreateUserController
    {
        return new CreateUserController(
            $container->get(Twig\Environment::class),
            $container->get(Authentication::class),
            $container->get(UserApi::class),
            $container->get(SessionWrapper::class),
            $config->getAsBool('ENABLE_REGISTRATION', false),
        );
    }

    public static function createCurrentHttpRequest() : Request
    {
        return Request::createFromGlobals();
    }

    public static function createDatabaseMigrationMigrateCommand(ContainerInterface $container) : Command\DatabaseMigrationMigrate
    {
        return new Command\DatabaseMigrationMigrate(
            $container->get(PhinxApplication::class),
            self::createDirectoryAppRoot() . 'settings/phinx.php'
        );
    }

    public static function createDatabaseMigrationRollbackCommand(ContainerInterface $container) : Command\DatabaseMigrationRollback
    {
        return new Command\DatabaseMigrationRollback(
            $container->get(PhinxApplication::class),
            self::createDirectoryAppRoot() . 'settings/phinx.php'
        );
    }

    public static function createDatabaseMigrationStatusCommand(ContainerInterface $container) : Command\DatabaseMigrationStatus
    {
        return new Command\DatabaseMigrationStatus(
            $container->get(PhinxApplication::class),
            self::createDirectoryAppRoot() . 'settings/phinx.php'
        );
    }

    public static function createDbConnection(Config $config) : DBAL\Connection
    {
        $databaseMode = self::getDatabaseMode($config);

        $config = match ($databaseMode) {
            'sqlite' => [
                'driver' => 'sqlite3',
                'path' => self::createDirectoryAppRoot() . $config->getAsString('DATABASE_SQLITE'),
            ],
            'mysql' => [
                'driver' => 'pdo_mysql',
                'host' => $config->getAsString('DATABASE_MYSQL_HOST'),
                'port' => self::getDatabaseMysqlPort($config),
                'dbname' => $config->getAsString('DATABASE_MYSQL_NAME'),
                'user' => $config->getAsString('DATABASE_MYSQL_USER'),
                'password' => $config->getAsString('DATABASE_MYSQL_PASSWORD'),
                'charset' => self::getDatabaseMysqlCharset($config),
            ],
            default => throw new \RuntimeException('Not supported database mode: ' . $databaseMode)
        };

        $connection = DBAL\DriverManager::getConnection($config);

        if ($databaseMode === 'sqlite') {
            $connection->executeQuery('PRAGMA busy_timeout = 3000');
        }

        return $connection;
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
            self::createDirectoryAppRoot() . 'public/',
            '/storage/images/',
        );
    }

    public static function createJobController(ContainerInterface $container) : JobController
    {
        return new JobController(
            $container->get(Authentication::class),
            $container->get(JobQueueApi::class),
            $container->get(LetterboxdCsvValidator::class),
            $container->get(Twig\Environment::class),
            $container->get(SessionWrapper::class),
            self::createDirectoryStorageApp()
        );
    }

    public static function createJobQueueScheduler(ContainerInterface $container, Config $config) : JobQueueScheduler
    {
        return new JobQueueScheduler(
            $container->get(JobQueueApi::class),
            self::getTmdbEnabledImageCaching($config)
        );
    }

    public static function createLineFormatter(Config $config) : LineFormatter
    {
        $formatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);

        try {
            $enableStackTrace = $config->getAsBool('LOG_ENABLE_STACKTRACE');
        } catch (OutOfBoundsException) {
            $enableStackTrace = self::DEFAULT_LOG_ENABLE_STACKTRACE;
        }

        $formatter->includeStacktraces($enableStackTrace);

        return $formatter;
    }

    public static function createLogger(ContainerInterface $container, Config $config) : LoggerInterface
    {
        $logger = new Logger('movary');

        $logger->pushHandler(self::createLoggerStreamHandlerStdout($container, $config));

        try {
            $enableFileLogging = $config->getAsBool('LOG_ENABLE_FILE_LOGGING');
        } catch (OutOfBoundsException) {
            $enableFileLogging = self::DEFAULT_ENABLE_FILE_LOGGING;
        }

        if ($enableFileLogging === true) {
            $logger->pushHandler(self::createLoggerStreamHandlerFile($container, $config));
        }

        return $logger;
    }

    public static function createSettingsController(ContainerInterface $container, Config $config) : SettingsController
    {
        try {
            $applicationVersion = $config->getAsString('APPLICATION_VERSION');
        } catch (OutOfBoundsException) {
            $applicationVersion = self::DEFAULT_APPLICATION_VERSION;
        }

        return new SettingsController(
            $container->get(Twig\Environment::class),
            $container->get(JobQueueApi::class),
            $container->get(Authentication::class),
            $container->get(UserApi::class),
            $container->get(MovieApi::class),
            $container->get(GithubApi::class),
            $container->get(SessionWrapper::class),
            $container->get(LetterboxdExporter::class),
            $container->get(TraktApi::class),
            $container->get(ServerSettings::class),
            $applicationVersion
        );
    }

    public static function createTmdbApiClient(ContainerInterface $container) : Tmdb\TmdbClient
    {
        return new Tmdb\TmdbClient(
            $container->get(ClientInterface::class),
            $container->get(ServerSettings::class)
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

        $twig->addGlobal('currentUserName', $user?->getName());
        $twig->addGlobal('currentUserIsAdmin', $user?->isAdmin());
        $twig->addGlobal('routeUsername', $routeUsername ?? null);
        $twig->addGlobal('dateFormatPhp', $dateFormatPhp);
        $twig->addGlobal('dateFormatJavascript', $dataFormatJavascript);
        $twig->addGlobal('requestUrlPath', self::createCurrentHttpRequest()->getPath());
        $twig->addGlobal('theme', $_COOKIE['theme'] ?? 'light');

        return $twig;
    }

    public static function createTwigFilesystemLoader() : Twig\Loader\FilesystemLoader
    {
        return new Twig\Loader\FilesystemLoader(self::createDirectoryAppRoot() . 'templates');
    }

    public static function createUrlGenerator(ContainerInterface $container, Config $config) : UrlGenerator
    {
        return new UrlGenerator(
            $container->get(TmdbUrlGenerator::class),
            $container->get(ImageCacheService::class),
            self::getTmdbEnabledImageCaching($config)
        );
    }

    public static function getDatabaseMode(Config $config) : string
    {
        return $config->getAsString('DATABASE_MODE');
    }

    public static function getDatabaseMysqlCharset(mixed $config) : string
    {
        return $config->getAsString('DATABASE_MYSQL_CHARSET', self::DEFAULT_DATABASE_MYSQL_CHARSET);
    }

    public static function getDatabaseMysqlPort(Config $config) : int
    {
        return $config->getAsInt('DATABASE_MYSQL_PORT', self::DEFAULT_DATABASE_MYSQL_PORT);
    }

    private static function createDirectoryAppRoot() : string
    {
        return substr(__DIR__, 0, -strlen(self::SRC_DIRECTORY_NAME));
    }

    private static function createDirectoryStorage() : string
    {
        return self::createDirectoryAppRoot() . 'storage/';
    }

    private static function createDirectoryStorageApp() : string
    {
        return self::createDirectoryStorage() . 'app/';
    }

    private static function createDirectoryStorageLogs() : string
    {
        return self::createDirectoryStorage() . 'logs/';
    }

    private static function createLoggerStreamHandlerFile(ContainerInterface $container, Config $config) : StreamHandler
    {
        $streamHandler = new StreamHandler(
            self::createDirectoryStorageLogs() . 'app.log',
            self::getLogLevel($config)
        );
        $streamHandler->setFormatter($container->get(LineFormatter::class));

        return $streamHandler;
    }

    private static function createLoggerStreamHandlerStdout(ContainerInterface $container, Config $config) : StreamHandler
    {
        $streamHandler = new StreamHandler('php://stdout', self::getLogLevel($config));
        $streamHandler->setFormatter($container->get(LineFormatter::class));

        return $streamHandler;
    }

    private static function getLogLevel(Config $config) : string
    {
        return $config->getAsString('LOG_LEVEL', self::DEFAULT_LOG_LEVEL);
    }

    private static function getTmdbEnabledImageCaching(Config $config) : bool
    {
        return $config->getAsBool('TMDB_ENABLE_IMAGE_CACHING', self::DEFAULT_TMDB_IMAGE_CACHING);
    }

    public function createProcessJobCommand(ContainerInterface $container, Config $config) : Command\ProcessJobs
    {
        $minRuntimeInSeconds = $config->getAsInt('MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING', self::DEFAULT_MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING);

        return new Command\ProcessJobs(
            $container->get(JobQueueApi::class),
            $container->get(JobProcessor::class),
            $container->get(LoggerInterface::class),
            $minRuntimeInSeconds,
        );
    }
}
