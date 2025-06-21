<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class ServerHasNoUsers implements MiddlewareInterface
{
    private const string CREATE_USER_URL_PATH = '/create-user';

    public function __construct(
        private readonly UserApi $userApi,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->userApi->hasUsers() === true) {
            return null;
        }

        return Response::createSeeOther(
            $this->urlService->createApplicationUrl(
                RelativeUrl::create(self::CREATE_USER_URL_PATH),
            ),
        );
    }
}
