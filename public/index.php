<?php declare(strict_types=1);

session_start();

/** @var DI\Container $container */

use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

$container = require(__DIR__ . '/../bootstrap.php');
$httpRequest = $container->get(\Movary\ValueObject\Http\Request::class);

try {
    $dispatcher = FastRoute\simpleDispatcher(
        require(__DIR__ . '/../settings/routes.php')
    );

    $uri = $_SERVER['REQUEST_URI'];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            $response = Response::create(StatusCode::createNotFound());
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $response = Response::create(StatusCode::createMethodNotAllowed());
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $httpRequest->addRouteParameters($routeInfo[2]);

            $response = $container->call($handler, [$httpRequest]);
            break;
        default:
            throw new \LogicException('Unhandled dispatcher status :' . $routeInfo[0]);
    }

    header((string)$response->getStatusCode());
    foreach ($response->getHeaders() as $header) {
        header((string)$header);
    }

    echo $response->getBody();
} catch (\Throwable $t) {
    $container->get(\Psr\Log\LoggerInterface::class)->emergency($t->getMessage(), ['exception' => $t]);

    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
}
exit(0);
