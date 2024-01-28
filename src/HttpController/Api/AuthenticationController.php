<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\CreateUserController;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class AuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function requestToken(Request $request) : Response
    {
        $tokenRequestBody = Json::decode($request->getBody());

        if ($tokenRequestBody['email'] === null || $tokenRequestBody['password'] === null) {
            return Response::createBadRequest('Username or password has not been provided');
        }

        $headers = $request->getHeaders();
        if (isset($headers['X-Movary-Client']) === false) {
            return Response::createBadRequest('Missing request header X-Movary-Client');
        }

        $requestClient = $headers['X-Movary-Client'];
        $totpCode = $tokenRequestBody['totpCode'] ?? 0;
        $rememberMe = $tokenRequestBody['rememberMe'] ?? false;

        try {
            $this->authenticationService->login(
                $tokenRequestBody['email'],
                $tokenRequestBody['password'],
                (bool)$rememberMe,
                $requestClient,
                $request->getUserAgent(),
                (int)$totpCode,
            );
        } catch (InvalidCredentials) {
            return Response::createBadRequest('Invalid login credentials');
        }

        if ($requestClient !== CreateUserController::MOVARY_WEB_CLIENT) {
            return Response::createJson(Json::encode(['token' => $this->authenticationService->getToken()]));
        }

        $redirect = $tokenRequestBody['redirect'] ?? null;
        $target = $redirect ?? $_SERVER['HTTP_REFERER'];

        $urlParts = parse_url($target);
        if (is_array($urlParts) === false) {
            $urlParts = ['path' => '/'];
        }
        $query = $urlParts['query'] ?? '';

        /* @phpstan-ignore-next-line */
        $targetRelativeUrl = $urlParts['path'] . $query;

        return Response::createSeeOther($targetRelativeUrl);
    }
}
