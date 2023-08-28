<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PasswordResetController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $token = $request->getRouteParameters()['token'];

        // check if token is valid
        // require 2fa on password change?
        // remove all existing sessions after password change
        // => user has to log in again on all devices after password change

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/password-reset.html.twig', [
                'token' => $token,
            ]),
        );
    }
}
