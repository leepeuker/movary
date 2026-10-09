<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\Domain\User\UserApi;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class TwoFactorAuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly TwoFactorAuthenticationFactory $twoFactorAuthenticationFactory,
        private readonly FlashMessageService $flashMessageService,
        private readonly UserApi $userApi,
    ) {
    }

    public function createTotpUri() : Response
    {
        $authenticatedUser = $this->authenticationService->requireWebSession();
        $currentUserName = $this->userApi->fetchUser($authenticatedUser->getUserId())->getName();
        $totp = $this->twoFactorAuthenticationFactory->createTotp($currentUserName);

        $response = Json::encode([
            'uri' => $totp->getProvisioningUri(),
            'secret' => $totp->getSecret()
        ]);

        return Response::createJson($response);
    }

    public function disableTOTP() : Response
    {
        $this->twoFactorAuthenticationApi->deleteTotp($this->authenticationService->requireWebSession()->getUserId());
        $this->flashMessageService->add(FlashMessage::TWO_FACTOR_AUTHENTICATION_DISABLED);

        return Response::createOk();
    }

    public function enableTOTP(Request $request) : Response
    {
        $userId = $this->authenticationService->requireWebSession()->getUserId();

        $requestData = Json::decode($request->getBody());
        $input = (int)$requestData['input'];
        $uri = $requestData['uri'];

        $valid = $this->twoFactorAuthenticationApi->verifyTotpUri($userId, $input, $uri);
        if ($valid === false) {
            return Response::createBadRequest();
        }

        $this->twoFactorAuthenticationApi->updateTotpUri($userId, $uri);
        $this->flashMessageService->add(FlashMessage::TWO_FACTOR_AUTHENTICATION_ENABLED);

        return Response::createOk();
    }
}
