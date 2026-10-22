<?php declare(strict_types=1);

/** @var DI\Container $container */

use Movary\HttpController\Web\ErrorController;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\ResponseMapper\ErrorResponseMapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

$container = require(__DIR__ . '/../bootstrap.php');
$httpRequest = $container->get(Request::class);
$uri = rawurldecode($httpRequest->getPath());
$usesApiResponse = str_starts_with($uri, '/api')
    || str_starts_with($uri, '/webhooks/')
    || str_starts_with($uri, '/feeds/');

try {
    $dispatcher = FastRoute\simpleDispatcher(
        require(__DIR__ . '/../settings/routes.php'),
    );

    $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            $response = Response::createNotFound();
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $response = Response::createMethodNotAllowed();
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1]['handler'];

            $httpRequest->addRouteParameters($routeInfo[2]);

            foreach ($routeInfo[1]['middleware'] as $middleware) {
                $middlewareResponse = $container->call($middleware, [$httpRequest]);

                if ($middlewareResponse instanceof Response) {
                    $response = $middlewareResponse;
                    break 2;
                }
            }

            $response = $container->call($handler, [$httpRequest]);
            break;
        default:
            throw new LogicException('Unhandled dispatcher status :' . $routeInfo[0]);
    }

    if ($response->getStatusCode()->getCode() === 404 && $usesApiResponse === false) {
        $response = $container->get(ErrorController::class)->renderNotFound($httpRequest);
    }

} catch (InvalidApiRequest $exception) {
    $response = $container->get(ErrorResponseMapper::class)->mapInvalidRequest($exception);
} catch (Throwable $t) {
    $container->get(LoggerInterface::class)->emergency($t->getMessage(), ['exception' => $t]);

    if ($usesApiResponse === true) {
        $response = $container->get(ErrorResponseMapper::class)->mapInternalServerError();
    } else {
        $response = $container->get(ErrorController::class)->renderInternalServerError();
    }
}

header((string)$response->getStatusCode());
foreach ($response->getHeaders() as $header) {
    header((string)$header);
}

echo $response->getBody();

exit(0);
