<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthentication;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class TwoFactorAuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthentication $twoFactorAuthenticationService,
        private readonly SessionWrapper $sessionWrapper
    ){ }

    public function createTotpUri() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $totp = $this->twoFactorAuthenticationService->createTotpUri($this->authenticationService->getCurrentUser()->getName());
        $uri = $totp->getProvisioningUri();
        $response = Json::encode([
            'uri' => $uri,
            'secret' => $totp->getSecret()
        ]);
        return Response::createJson($response);
    }

    public function disableTOTP() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $this->twoFactorAuthenticationService->deleteTotp($this->authenticationService->getCurrentUserId());
        $this->sessionWrapper->set('twoFactorAuthenticationDisabled', true);
        return Response::createOk();
    }

    public function enableTOTP(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $data = JSON::decode($request->getBody());
        $input = (int)$data['input'];
        $uri = $data['uri'];
        $valid = $this->twoFactorAuthenticationService->verifyTotpUri($userId, $input, $uri);
        if($valid === false) {
            return Response::createBadRequest();
        }
        $this->twoFactorAuthenticationService->updateTotpUri($uri, $userId);
        $this->sessionWrapper->set('twoFactorAuthenticationEnabled', true);
        return Response::createOk();
    }
}