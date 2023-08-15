<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthentication;
use Movary\Domain\User\UserApi;
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
        private readonly TwoFactorAuthentication $twoFactorAuthenticationService,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper
    ){ }

    public function createTotpUri() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $totp = $this->twoFactorAuthenticationService->createTOTPUri($this->authenticationService->getCurrentUser()->getName());
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
        $this->twoFactorAuthenticationService->deleteTOTP($this->authenticationService->getCurrentUserId());
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
        $valid = $this->twoFactorAuthenticationService->verifyTOTPUri($userId, $input, $uri);
        if($valid === false) {
            return Response::createBadRequest();
        }
        $this->twoFactorAuthenticationService->updateTOTPUri($uri, $userId);
        $this->sessionWrapper->set('twoFactorAuthenticationEnabled', true);
        return Response::createOk();
    }

    public function verifyTOTP(Request $request) : Response
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
        
        if($this->twoFactorAuthenticationService->verifyTOTPUri($userId, (int)$inputTOTP) === false) {
            $this->sessionWrapper->set('InvalidTOTPCode', true);
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }
        $TOTPSecret = $this->twoFactorAuthenticationService->getTOTPObject($userId)->getSecret();
        $this->authenticationService->createAuthenticationCookie($this->userApi->findUserById($userId), $rememberMe);
        $expirationDate = (int)$this->authenticationService->createExpirationDate(self::MAX_EXPIRATION_AGE_IN_DAYS)->format('U');
        if($rememberTOTP === true) {
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