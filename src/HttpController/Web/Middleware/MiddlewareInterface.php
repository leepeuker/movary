<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request) : ?Response;
}
