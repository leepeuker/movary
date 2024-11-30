<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class ServerHasRegistrationEnabled implements MiddlewareInterface
{
    public function __construct(
        private readonly bool $registrationEnabled,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->registrationEnabled === false) {
            return null;
        }

        return Response::createForbidden();
    }
}
