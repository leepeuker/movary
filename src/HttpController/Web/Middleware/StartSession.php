<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class StartSession implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $this->sessionWrapper->start();

        return null;
    }
}
