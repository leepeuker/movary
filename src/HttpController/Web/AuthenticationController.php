<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\SessionService;
use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\Service;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Service\Authentication $authenticationService,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function logout() : Response
    {
        $this->authenticationService->logout();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation('/')],
        );
    }

    public function renderLoginPage(Request $request) : Response
    {
        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $redirect = $request->getGetParameters()['redirect'] ?? false;
        $this->sessionWrapper->unset('failedLogin');

        $renderedTemplate = $this->twig->render(
            'page/login.html.twig',
            [
                'failedLogin' => $failedLogin,
                'redirect' => $redirect
            ],
        );

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
