<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class AuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function getTokenData(Request $request) : Response
    {
        $authenticatedUser = $this->authenticationService->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            return Response::createBearerUnauthorized();
        }

        $user = $this->userApi->findUserById($authenticatedUser->getUserId());
        if ($user === null) {
            return Response::createBearerUnauthorized();
        }

        return Response::createJson(
            Json::encode([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'isAdmin' => $user->isAdmin(),
                ]
            ]),
        );
    }
}
