<?php declare(strict_types=1);

namespace Movary;

use Doctrine\DBAL;
use GuzzleHttp;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Movary\Api\Tmdb;
use Movary\Api\Trakt;
use Movary\ValueObject\Config;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class Factory
{
    public static function createConfig() : Config
    {
        return Config::createFromFile(__DIR__ . '/../settings/config.ini');
    }

    public static function createDbConnection(Config $config) : DBAL\Connection
    {
        return DBAL\DriverManager::getConnection(
            [
                'dbname' => $config->getAsString('database.name'),
                'user' => $config->getAsString('database.username'),
                'password' => $config->getAsString('database.password'),
                'host' => $config->getAsString('database.host'),
                'driver' => $config->getAsString('database.driver'),
            ]
        );
    }

    public static function createFileLogger(Config $config) : LoggerInterface
    {
        $formatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);
        $formatter->includeStacktraces(true);

        $handler = new StreamHandler(
            __DIR__ . '/../' . $config->getAsString('logger.file'),
            $config->getAsString('logger.logLevel')
        );
        $handler->setFormatter($formatter);

        $logger = new Logger('file');
        $logger->pushHandler($handler);

        return $logger;
    }

    public static function createHttpClient() : ClientInterface
    {
        return new GuzzleHttp\Client();
    }

    public static function createTmdbApiClient(ContainerInterface $container, Config $config) : Tmdb\Client
    {
        return new Tmdb\Client(
            $container->get(ClientInterface::class),
            $config->getAsString('tmdb.apiKey')
        );
    }

    public static function createTraktApiClient(ContainerInterface $container, Config $config) : Trakt\Client
    {
        return new Trakt\Client(
            $container->get(ClientInterface::class),
            $config->getAsString('trakt.clientId')
        );
    }
}
