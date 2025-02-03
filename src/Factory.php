<?php declare(strict_types=1);

namespace Movary;

use Doctrine\DBAL;
use Dotenv\Dotenv;
use GuzzleHttp;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Movary\Api\Tmdb;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Api\Trakt\Cache\User\Movie\Watched;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\TraktClient;
use Movary\Command\CreatePublicStorageLink;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\OpenApiController;
use Movary\HttpController\Web\CreateUserController;
use Movary\HttpController\Web\JobController;
use Movary\HttpController\Web\LandingPageController;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\Export\ExportService;
use Movary\Service\Export\ExportWriter;
use Movary\Service\ImageCacheService;
use Movary\Service\JobProcessor;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Service\ServerSettings;
use Movary\Service\UrlGenerator;
use Movary\Util\File;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Config;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Phinx\Console\PhinxApplication;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Twig;

class Factory
{
    private const string DEFAULT_DATABASE_SQLITE = 'storage/movary.sqlite';

    private const string DEFAULT_DATABASE_MODE = 'sqlite';

    private const string SRC_DIRECTORY_NAME = 'src';

    private const int DEFAULT_MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING = 15;

    private const string DEFAULT_DATABASE_MYSQL_CHARSET = 'utf8mb4';

    private const int DEFAULT_DATABASE_MYSQL_PORT = 3306;

    private const string DEFAULT_LOG_LEVEL = LogLevel::WARNING;

    private const bool DEFAULT_TMDB_IMAGE_CACHING = false;

    private const bool DEFAULT_LOG_ENABLE_STACKTRACE = false;

    private const bool DEFAULT_ENABLE_FILE_LOGGING = true;

    public static function createConfig(ContainerInterface $container) : Config
    {
        $dotenv = Dotenv::createMutable(self::createDirectoryAppRoot());
        $dotenv->safeLoad();

        $fpmEnvironment = $_ENV;
        $systemEnvironment = getenv();

        return new Config(
            $container->get(File::class),
            array_merge($fpmEnvironment, $systemEnvironment),
        );
    }

    public static function createCreatePublicStorageLink(ContainerInterface $container) : CreatePublicStorageLink
    {
        return new CreatePublicStorageLink(
            $container->get(File::class),
            self::createDirectoryStorageApp(),
            self::createDirectoryAppRoot(),
        );
    }

    public static function createCreateUserController(ContainerInterface $container) : CreateUserController
    {
        return new CreateUserController(
            $container->get(Twig\Environment::class),
            $container->get(Authentication::class),
            $container->get(UserApi::class),
            $container->get(SessionWrapper::class),
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
            self::createDirectoryAppRoot() . 'settings/phinx.php',
        );
    }

    public static function createDatabaseMigrationRollbackCommand(ContainerInterface $container) : Command\DatabaseMigrationRollback
    {
        return new Command\DatabaseMigrationRollback(
            $container->get(PhinxApplication::class),
            self::createDirectoryAppRoot() . 'settings/phinx.php',
        );
    }

    public static function createDatabaseMigrationStatusCommand(ContainerInterface $container) : Command\DatabaseMigrationStatus
    {
        return new Command\DatabaseMigrationStatus(
            $container->get(PhinxApplication::class),
            self::createDirectoryAppRoot() . 'settings/phinx.php',
        );
    }

    public static function createDbConnection(Config $config) : DBAL\Connection
    {
        $databaseMode = self::getDatabaseMode($config);

        $config = match ($databaseMode) {
            'sqlite' => [
                'driver' => 'sqlite3',
                'path' => self::createDirectoryAppRoot() . $config->getAsString('DATABASE_SQLITE', self::DEFAULT_DATABASE_SQLITE),
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
            default => throw new RuntimeException('Not supported database mode: ' . $databaseMode)
        };

        $connection = DBAL\DriverManager::getConnection($config);

        if ($databaseMode === 'sqlite') {
            $connection->executeQuery('PRAGMA busy_timeout = 3000');
            $connection->executeQuery('PRAGMA foreign_keys = ON');
        }

        return $connection;
    }

    public static function createExportService(ContainerInterface $container) : ExportService
    {
        return new ExportService(
            $container->get(MovieApi::class),
            $container->get(MovieWatchlistApi::class),
            $container->get(ExportWriter::class),
            self::createDirectoryStorage(),
        );
    }

    public static function createHttpClient() : ClientInterface
    {
        return new GuzzleHttp\Client(['timeout' => 4]);
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
            $container->get(SessionWrapper::class),
            self::createDirectoryStorageApp(),
        );
    }

    public static function createJobQueueScheduler(ContainerInterface $container, Config $config) : JobQueueScheduler
    {
        return new JobQueueScheduler(
            $container->get(JobQueueApi::class),
            self::getTmdbEnabledImageCaching($config),
        );
    }

    public static function createLandingPageController(ContainerInterface $container, Config $config) : LandingPageController
    {
        return new LandingPageController(
            $container->get(Twig\Environment::class),
            $container->get(SessionWrapper::class),
            $config->getAsBool('ENABLE_REGISTRATION', false),
            $config->getAsStringNullable('DEFAULT_LOGIN_EMAIL'),
            $config->getAsStringNullable('DEFAULT_LOGIN_PASSWORD'),
        );
    }

    public static function createLineFormatter(Config $config) : LineFormatter
    {
        $formatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);

        $enableStackTrace = $config->getAsBool('LOG_ENABLE_STACKTRACE', self::DEFAULT_LOG_ENABLE_STACKTRACE);

        $formatter->includeStacktraces($enableStackTrace);

        return $formatter;
    }

    public static function createLogger(ContainerInterface $container, Config $config) : LoggerInterface
    {
        $logger = new Logger('movary');

        $logger->pushHandler(self::createLoggerStreamHandlerStdout($container, $config));

        $enableFileLogging = $config->getAsBool('LOG_ENABLE_FILE_LOGGING', self::DEFAULT_ENABLE_FILE_LOGGING);

        if ($enableFileLogging === true) {
            $logger->pushHandler(self::createLoggerStreamHandlerFile($container, $config));
        }

        return $logger;
    }

    public static function createMiddlewareServerHasRegistrationEnabled(Config $config) : HttpController\Web\Middleware\ServerHasRegistrationEnabled
    {
        return new HttpController\Web\Middleware\ServerHasRegistrationEnabled(
            $config->getAsBool('ENABLE_REGISTRATION', false),
        );
    }

    public static function createOpenApiController(ContainerInterface $container) : OpenApiController
    {
        return new OpenApiController(
            $container->get(File::class),
            $container->get(ServerSettings::class),
            self::createDirectoryDocs(),
        );
    }

    public static function createTmdbApiClient(ContainerInterface $container) : Tmdb\TmdbClient
    {
        return new Tmdb\TmdbClient(
            $container->get(ClientInterface::class),
            $container->get(ServerSettings::class),
        );
    }

    public static function createTraktApi(ContainerInterface $container) : TraktApi
    {
        return new TraktApi(
            $container->get(TraktClient::class),
            $container->get(Watched\Service::class),
        );
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity
    public static function createTwigEnvironment(ContainerInterface $container) : Twig\Environment
    {
        $twig = new Twig\Environment($container->get(Twig\Loader\LoaderInterface::class));

        $currentRequest = $container->get(Request::class);
        $routeUsername = $currentRequest->getRouteParameters()['username'] ?? null;

        $userAuthenticated = $container->get(Authentication::class)->isUserAuthenticatedWithCookie();

        $twig->addGlobal('loggedIn', $userAuthenticated);

        $user = null;
        $dateFormatPhp = DateFormat::getPhpDefault();
        $dataFormatJavascript = DateFormat::getJavascriptDefault();
        if ($userAuthenticated === true) {
            $currentUserId = $container->get(Authentication::class)->getCurrentUserId();

            /** @var User\UserEntity $user */
            $user = $container->get(User\UserApi::class)->findUserById($currentUserId);

            $dateFormatPhp = DateFormat::getPhpById($user->getDateFormatId());
            $dataFormatJavascript = DateFormat::getJavascriptById($user->getDateFormatId());
        }

        $twig->addGlobal('applicationName', $container->get(ServerSettings::class)->getApplicationName() ?? 'Movary');
        $twig->addGlobal('applicationTimezone', $container->get(ServerSettings::class)->getApplicationTimezone() ?? DateTime::DEFAULT_TIME_ZONE);
        $twig->addGlobal('currentUserName', $user?->getName());
        $twig->addGlobal('currentUserIsAdmin', $user?->isAdmin());
        $twig->addGlobal('currentUserCountry', $user?->getCountry());
        $twig->addGlobal('currentUserLocationsEnabled', $user?->hasLocationsEnabled());
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
            self::getTmdbEnabledImageCaching($config),
        );
    }

    public static function getDatabaseSqlite(Config $config) : string
    {
        return $config->getAsString('DATABASE_SQLITE', self::DEFAULT_DATABASE_SQLITE);
    }

    public static function getDatabaseMode(Config $config) : string
    {
        return $config->getAsString('DATABASE_MODE', self::DEFAULT_DATABASE_MODE);
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

    private static function createDirectoryDocs() : string
    {
        return self::createDirectoryAppRoot() . 'docs/';
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
            self::getLogLevel($config),
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
