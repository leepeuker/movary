<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PasswordResetController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $token = $request->getRouteParameters()['token'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/password-reset.html.twig', [
                'token' => $token,
            ]),
        );
    }

    public function resetPassword(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());
        $token = $requestData['token'];
        $newPassword = $requestData['newPassword'];

        try {
            $passwordResetData = $this->userApi->fetchPasswordResetEmailDataByPasswordResetToken($token);
        } catch (\RuntimeException) {
            return Response::createBadRequest('Reset token not valid or expired');
        }

        if (DateTime::createFromString($passwordResetData['expires_at'])->isAfter(DateTime::create()) === false) {
            return Response::createBadRequest('Reset token not valid or expired');
        }

        try {
            $this->userApi->updatePassword((int)$passwordResetData['id'], $newPassword);
        } catch (PasswordTooShort) {
            return Response::createBadRequest('Password is too short');
        }

        $this->userApi->deletePasswordReset($token);
        $this->authenticationService->logout();
        $this->sessionWrapper->set('passwordRested', true);

        return Response::create(StatusCode::createOk());
    }
}
