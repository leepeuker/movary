<?php
declare(strict_types=1);
namespace Movary\HttpController\Api;

use Movary\ValueObject\Http\Request;
use FastRoute;
use Movary\ValueObject\Config;
use Movary\ValueObject\Http\Response;

class PreflightRequestController
{
    public function __construct(
        private readonly Config $config
    ) { }

    public function handleRequest(Request $request, FastRoute\Dispatcher $dispatcher) : Response
    {
        $requestedRoute = $request->getPath();
        $dispatch = $dispatcher->dispatch('OPTIONS', $requestedRoute);
        if($dispatch[0] === FastRoute\Dispatcher::NOT_FOUND) {
            return Response::createNotFound();
        }
        $methods = $dispatch[1] ?? [];
        array_push($methods, 'OPTIONS');
        $origin = $this->config->getAsString('FRONTEND_URL', $this->config->getAsString('APPLICATION_URL', '*'));
        return Response::createCors($methods, $origin);
    }
}