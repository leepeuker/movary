<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\SessionService;
use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\Service\AuthenticationWeb;
use Movary\Util\Json;
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
        private readonly SessionWrapper $sessionWrapper,
        private readonly AuthenticationWeb $authenticationService,
    ) {
    }

    public function login(Request $request) : Response
    {
        $tokenRequestBody = Json::decode($request->getBody());

        if (isset($tokenRequestBody['email']) === false || isset($tokenRequestBody['password']) === false) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingCredentials',
                    'message' => 'Email or password is missing'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        $totpCode = empty($tokenRequestBody['totpCode']) === true ? null : (int)$tokenRequestBody['totpCode'];
        $rememberMe = $tokenRequestBody['rememberMe'] ?? false;

        try {
            $this->authenticationService->login(
                $tokenRequestBody['email'],
                $tokenRequestBody['password'],
                (bool)$rememberMe,
                $request->getUserAgent(),
                $totpCode,
            );
        } catch (MissingTotpCode) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingTotpCode',
                    'message' => 'Two-factor authentication code missing'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (InvalidTotpCode) {
            return Response::createUnauthorized(
                Json::encode([
                    'error' => 'InvalidTotpCode',
                    'message' => 'Two-factor authentication code wrong'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (InvalidCredentials) {
            return Response::createUnauthorized(
                Json::encode([
                    'error' => 'InvalidCredentials',
                    'message' => 'Invalid credentials'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        return Response::createNoContent();
    }

    public function logout() : Response
    {
        $this->authenticationService->logout();

        return Response::CreateNoContent();
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
