<?php

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;

class TwoFactorAuthenticationApi
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly TwoFactorAuthenticationFactory $twoFactorAuthenticationFactory,
    ) {
    }

    public function deleteTotp(int $userId) : void
    {
        $this->userApi->deleteTotpUri($userId);
    }

    public function fetchTotpUriSecretByTotpUri(string $totpUri) : string
    {
        return $this->twoFactorAuthenticationFactory->createOtpFromProvisioningUri($totpUri)->getSecret();
    }

    public function fetchTotpUriSecretByUserId(int $userId) : string
    {
        $totpUri = $this->findTotpUri($userId);

        if (empty($totpUri) === true) {
            throw new \RuntimeException('Could not find totp uri for user with id: ' . $userId);
        }

        return $this->twoFactorAuthenticationFactory->createOtpFromProvisioningUri($totpUri)->getSecret();
    }

    public function findTotpUri(int $userId) : ?string
    {
        return $this->userApi->findTotpUri($userId);
    }

    public function isValidTOTPCookie(string $totpUri, string $TOTPCookieValue) : bool
    {
        return $this->fetchTotpUriSecretByTotpUri($totpUri) === $TOTPCookieValue;
    }

    public function updateTotpUri(int $userId, string $uri) : void
    {
        $this->userApi->updateTotpUri($userId, $uri);
    }

    public function verifyTotpUri(int $userId, int $verificationCode, ?string $uri = null) : bool
    {
        if ($uri === null) {
            $uri = $this->userApi->findTotpUri($userId);
        }

        if ($uri === null) {
            throw new \RuntimeException('Could not find totp uri for user with id: ' . $userId);
        }

        $totp = $this->twoFactorAuthenticationFactory->createOtpFromProvisioningUri($uri);

        return $totp->verify((string)$verificationCode);
    }
}
