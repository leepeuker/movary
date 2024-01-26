<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\NoVerificationCode;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class AuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function requestToken(Request $request) : Response
    {
        $postParameters = Json::decode($request->getBody());
        $headers = $request->getHeaders();
        if($postParameters['email'] === null || $postParameters['password'] === null) {
            return Response::createBadRequest('Username or password has not been provided');
        }
        $totpCode = $postParameters['totpCode'] ?? 0;
        
        if(isset($headers['X-Movary-Client']) === false) {
            return Response::createBadRequest();
        }
        
        $client = $headers['X-Movary-Client'];

        try {
            $this->authenticationService->login($postParameters['email'], $postParameters['password'], false, (int)$totpCode);

            if($client === 'Movary Web') {
                $redirect = $postParameters['redirect'] ?? null;
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
            
            return Response::createJson(Json::encode(['token' => $this->authenticationService->getToken()]));
        } catch (InvalidCredentials $e) {
            return Response::createBadRequest(Json::encode([
                'error' => basename(str_replace('\\', '/', get_class($e))),
                'message' => $e->getMessage()
            ]));
        }
    }
}