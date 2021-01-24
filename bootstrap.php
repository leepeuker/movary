<?php declare(strict_types=1);

use Movary\Factory;

require_once(__DIR__ . '/vendor/autoload.php');

$builder = new DI\ContainerBuilder();
$builder->addDefinitions(
    [
        \Movary\ValueObject\Config::class => DI\factory([Factory::class, 'createConfig']),
        \Movary\Api\Trakt\Client::class => DI\factory([Factory::class, 'createTraktApiClient']),
        \Psr\Http\Client\ClientInterface::class => DI\factory([Factory::class, 'createHttpClient']),
        \Psr\Log\LoggerInterface::class => DI\factory([Factory::class, 'createFileLogger']),
        \Doctrine\DBAL\Connection::class => DI\factory([Factory::class, 'createDbConnection']),
    ]
);

return $builder->build();
