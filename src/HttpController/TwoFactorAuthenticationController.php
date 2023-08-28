<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class TwoFactorAuthenticationController
{
    private const MAX_EXPIRATION_AGE_IN_DAYS = 30;

    private const TOTP_COOKIE_NAME = 'RememberTOTP';

    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly TwoFactorAuthenticationFactory $twoFactorAuthenticationFactory,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function createTotpUri() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $currentUserName = $this->authenticationService->getCurrentUser()->getName();
        $totp = $this->twoFactorAuthenticationFactory->createTotp($currentUserName);

        $response = Json::encode([
            'uri' => $totp->getProvisioningUri(),
            'secret' => $totp->getSecret()
        ]);

        return Response::createJson($response);
    }

    public function disableTOTP() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->twoFactorAuthenticationApi->deleteTotp($this->authenticationService->getCurrentUserId());
        $this->sessionWrapper->set('twoFactorAuthenticationDisabled', true);

        return Response::createOk();
    }

    public function enableTOTP(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $requestData = Json::decode($request->getBody());
        $input = (int)$requestData['input'];
        $uri = $requestData['uri'];

        $valid = $this->twoFactorAuthenticationApi->verifyTotpUri($userId, $input, $uri);
        if ($valid === false) {
            return Response::createBadRequest();
        }

        $this->twoFactorAuthenticationApi->updateTotpUri($userId, $uri);
        $this->sessionWrapper->set('twoFactorAuthenticationEnabled', true);

        return Response::createOk();
    }

    public function verifyTotp(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $inputTOTP = $request->getPostParameters()['TOTPCode'];
        $rememberMe = $this->sessionWrapper->find('RememberMe');
        $rememberTOTP = isset($request->getPostParameters()['rememberTOTP']) === true;
        $userId = (int)$this->sessionWrapper->find('TOTPUserId');

        if ($this->twoFactorAuthenticationApi->verifyTotpUri($userId, (int)$inputTOTP) === false) {
            $this->sessionWrapper->set('InvalidTOTPCode', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $TOTPSecret = $this->twoFactorAuthenticationApi->fetchTotpUriSecretByUserId($userId);
        $this->authenticationService->createAuthenticationCookie($userId, $rememberMe);
        $expirationDate = (int)$this->authenticationService->createExpirationDate(self::MAX_EXPIRATION_AGE_IN_DAYS)->format('U');
        if ($rememberTOTP === true) {
            setcookie(self::TOTP_COOKIE_NAME, $TOTPSecret, $expirationDate);
        }

        $this->sessionWrapper->unset('TOTPUserId');
        $this->sessionWrapper->unset('RememberMe');

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
