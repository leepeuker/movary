<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
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
        $token = $request->getHeader('X-Movary-Token');
        if ($token === null || $token === '') {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingAuthToken',
                    'message' => 'Authentication token header is missing'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        $authenticatedUser = $this->authenticationService->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            return Response::createUnauthorized();
        }

        $user = $this->userApi->findUserById($authenticatedUser->getUserId());
        if ($user === null) {
            return Response::createUnauthorized();
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
