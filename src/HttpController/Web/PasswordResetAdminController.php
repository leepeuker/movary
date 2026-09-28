<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PasswordResetAdminController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PasswordResetRequestService $requestService,
        private readonly PasswordResetTokenService $tokenService,
    ) {
    }

    public function createReset(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $user = $this->userApi->findUserById($userId);

        if ($user === null) {
            return Response::createNotFound();
        }

        if ($user->hasCoreAccountChangesDisabled() === true) {
            return Response::createBadRequest('Password resets are disabled for this user.');
        }

        if ($this->requestService->requestForUser($user) === false) {
            return Response::createBadRequest('Could not send password reset email.');
        }

        return Response::createOk();
    }

    public function fetchPendingResets() : Response
    {
        return Response::createJson(Json::encode($this->tokenService->fetchPendingTokens()));
    }

    public function revokeReset(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        if ($this->userApi->findUserById($userId) === null) {
            return Response::createNotFound();
        }

        $this->tokenService->deleteTokenForUser($userId);

        return Response::createNoContent();
    }
}
