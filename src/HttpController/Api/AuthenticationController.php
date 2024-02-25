<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
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
    ) {
    }

    public function createToken(Request $request) : Response
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

        $headers = $request->getHeaders();
        if (isset($headers['X-Movary-Client']) === false) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingRequestHeader',
                    'message' => 'Missing request header X-Movary-Client'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        $requestClient = $headers['X-Movary-Client'];
        $totpCode = empty($tokenRequestBody['totpCode']) === true ? null : (int)$tokenRequestBody['totpCode'];
        $rememberMe = $tokenRequestBody['rememberMe'] ?? false;

        try {
            $userAndAuthToken = $this->authenticationService->login(
                $tokenRequestBody['email'],
                $tokenRequestBody['password'],
                (bool)$rememberMe,
                $requestClient,
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

        return Response::createJson(
            Json::encode([
                'userId' => $userAndAuthToken['user']->getId(),
                'authToken' => $userAndAuthToken['token']
            ]),
        );
    }

    public function destroyToken(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $this->authenticationService->logout();

            return Response::CreateNoContent();
        }

        $apiToken = $request->getHeaders()['X-Auth-Token'] ?? null;
        if ($apiToken === null) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingAuthToken',
                    'message' => 'Authentication token to delete in headers missing'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        $this->authenticationService->deleteToken($apiToken);

        return Response::CreateNoContent();
    }
}
