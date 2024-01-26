<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\SessionService;
use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\InvalidTotpCode;
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
        $redirect = $postParameters['redirect'];
        $target = $redirect ?? $_SERVER['HTTP_REFERER'];

        $urlParts = parse_url($target);
        if (is_array($urlParts) === false) {
            $urlParts = ['path' => '/'];
        }

        /* @phpstan-ignore-next-line */
        $targetRelativeUrl = $urlParts['path'] . $urlParts['query'] ?? '';

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($targetRelativeUrl)],
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
