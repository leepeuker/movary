<?php

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use OTPHP\TOTP;
use OTPHP\Factory;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthentication
{
    private const REGENERATION_TIME = 10;
    private const DIGEST_ALGORITHM = 'sha-1';
    private const DIGITS = 6;

    public function __construct(
        private readonly UserApi $userApi,
        private readonly ServerSettings $serverSettings,
    ) {}
    
    public function getTotpUri(int $userId) : ?string
    {
        return $this->userApi->findTotpUri($userId);
    }

    public function createTotpUri(string $userName) : ?TOTP
    {
        $totp = TOTP::generate();
        $totp->setPeriod(self::REGENERATION_TIME);
        $totp->setDigest(self::DIGEST_ALGORITHM);
        $totp->setDigits(self::DIGITS);
        $totp->setLabel($userName);
        $totp->setIssuer($this->serverSettings->getTotpIssuer());
        return $totp;
    }

    public function verifyTotpUri(int $userId, int $userInput) : bool
    {
        $totp = Factory::loadFromProvisioningUri($this->userApi->findTotpUri($userId));
        return $totp->verify($userInput);
    }

    public function deleteTotp(int $userId) : void
    {
        $this->userApi->deleteTotpUri($userId);
    }

    public function updateTotpUri(TOTP $totp, int $userId) : void
    {
        $this->userApi->updateTotpUri($userId, $totp->getProvisioningUri());
    }
}