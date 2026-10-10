<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\CurrentWebUser;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserIsAdmin implements MiddlewareInterface
{
    public function __construct(
        private readonly CurrentWebUser $currentWebUser,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->currentWebUser->requireUser()->isAdmin() === true) {
            return null;
        }

        return Response::createForbidden();
    }
}
