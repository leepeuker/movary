<?php declare(strict_types=1);

namespace Movary;

use Doctrine\DBAL;
use GuzzleHttp;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Movary\Api\Trakt\Client;
use Movary\ValueObject\Config;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class Factory
{
    private ?DBAL\Connection $dbConnection = null;

    public static function createConfig() : Config
    {
        return Config::createFromFile(__DIR__ . '/../settings/config.ini');
    }

    public static function createFileLogger(Config $config) : LoggerInterface
    {
        $logger = new Logger('file');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../' . $config->getAsString('logger.file'), $config->getAsString('logger.logLevel')));

        return $logger;
    }

    public static function createHttpClient() : ClientInterface
    {
        return new GuzzleHttp\Client();
    }

    public static function createTraktApiClient(ContainerInterface $container, Config $config) : Client
    {
        return new Client(
            $container->get(ClientInterface::class),
            $config->getAsString('trakt.clientId')
        );
    }

    public function createDbConnection(Config $config) : DBAL\Connection
    {
        if ($this->dbConnection === null) {
            $this->dbConnection = DBAL\DriverManager::getConnection(
                [
                    'dbname' => $config->getAsString('database.name'),
                    'user' => $config->getAsString('database.username'),
                    'password' => $config->getAsString('database.password'),
                    'host' => $config->getAsString('database.host'),
                    'driver' => $config->getAsString('database.driver'),
                ]
            );
        }

        return $this->dbConnection;
    }
}
