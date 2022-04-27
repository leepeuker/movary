<?php declare(strict_types=1);

use Movary\Factory;

require_once(__DIR__ . '/vendor/autoload.php');

$builder = new DI\ContainerBuilder();
$builder->addDefinitions(
    [
        \Movary\ValueObject\Config::class => DI\factory([Factory::class, 'createConfig']),
        \Movary\Api\Trakt\Api::class => DI\factory([Factory::class, 'createTraktApi']),
        \Movary\Api\Trakt\Client::class => DI\factory([Factory::class, 'createTraktApiClient']),
        \Movary\Api\Tmdb\Client::class => DI\factory([Factory::class, 'createTmdbApiClient']),
        \Movary\ValueObject\HttpRequest::class => DI\factory([Factory::class, 'createCurrentHttpRequest']),
        \Psr\Http\Client\ClientInterface::class => DI\factory([Factory::class, 'createHttpClient']),
        \Psr\Log\LoggerInterface::class => DI\factory([Factory::class, 'createFileLogger']),
        \Doctrine\DBAL\Connection::class => DI\factory([Factory::class, 'createDbConnection']),
        \Twig\Loader\LoaderInterface::class => DI\factory([Factory::class, 'createTwigFilesystemLoader']),
    ]
);

return $builder->build();
