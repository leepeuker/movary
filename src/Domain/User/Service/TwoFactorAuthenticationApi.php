<?php

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use RuntimeException;

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

    public function findTotpUri(int $userId) : ?string
    {
        return $this->userApi->findTotpUri($userId);
    }

    public function updateTotpUri(int $userId, string $uri) : void
    {
        $this->userApi->updateTotpUri($userId, $uri);
    }

    public function verifyTotpUri(int $userId, string $verificationCode, ?string $uri = null) : bool
    {
        if ($verificationCode === '' || preg_match('/^[0-9]{6}$/D', $verificationCode) !== 1) {
            return false;
        }

        if ($uri === null) {
            $uri = $this->userApi->findTotpUri($userId);
        }

        if ($uri === null) {
            throw new RuntimeException('Could not find totp uri for user with id: ' . $userId);
        }

        $totp = $this->twoFactorAuthenticationFactory->createOtpFromProvisioningUri($uri);

        return $totp->verify($verificationCode);
    }
}
