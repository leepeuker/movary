<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class UserIsUnauthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly CurrentWebUser $currentWebUser,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $user = $this->currentWebUser->findUser();
        if ($user === null) {
            return null;
        }

        $userName = $user->getName();

        return Response::createSeeOther(
            $this->urlService->createApplicationUrl(
                RelativeUrl::create("/users/$userName"),
            ),
        );
    }
}
