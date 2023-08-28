<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\SessionService;
use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\NoVerificationCode;
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

    public function login(Request $request) : Response
    {
        $postParameters = $request->getPostParameters();

        try {
            $this->authenticationService->login(
                $postParameters['email'],
                $postParameters['password'],
                isset($postParameters['rememberMe']) === true,
            );
        } catch (NoVerificationCode) {
            $this->sessionWrapper->set('useTwoFactorAuthentication', true);
        } catch (InvalidCredentials) {
            $this->sessionWrapper->set('failedLogin', true);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
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

    public function renderLoginPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation('/')],
            );
        }

        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $this->sessionWrapper->unset('failedLogin');

        $renderedTemplate = $this->twig->render(
            'page/login.html.twig',
            [
                'failedLogin' => $failedLogin
            ],
        );

        $this->sessionWrapper->unset('failedLogin');

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
