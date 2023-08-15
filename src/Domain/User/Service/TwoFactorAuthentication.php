<?php

namespace Movary\Domain\User\Service;

use InvalidArgumentException;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use OTPHP\TOTP;
use OTPHP\Factory;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthentication
{
    private const SECRET_LENGTH = 32;
    private const REGENERATION_TIME = 30;
    private const DIGEST_ALGORITHM = 'sha1';
    private const DIGITS = 6;

    public function __construct(
        private readonly UserApi $userApi,
        private readonly ServerSettings $serverSettings,
    ) {}

    public function createTOTPUri(string $userName) : ?TOTP
    {
        $secret = Base32::encodeUpper(random_bytes(self::SECRET_LENGTH));
        $totp = TOTP::createFromSecret($secret);
        $totp->setPeriod(self::REGENERATION_TIME);
        $totp->setDigest(self::DIGEST_ALGORITHM);
        $totp->setDigits(self::DIGITS);
        $totp->setLabel($userName);
        $totp->setIssuer($this->serverSettings->getTotpIssuer());
        return $totp;
    }

    public function deleteTOTP(int $userId) : void
    {
        $this->userApi->deleteTotpUri($userId);
    }

    public function getTOTPUri(int $userId) : ?string
    {
        return $this->userApi->findTOTPUri($userId);
    }

    public function getTOTPObject(int $userId) : ?TOTP
    {
        return Factory::loadFromProvisioningUri($this->getTOTPUri($userId));
    }

    public function isValidTOTPCookie($TOTPUri, $TOTPCookieValue) : bool
    {
        $TOTPObject = Factory::loadFromProvisioningUri($TOTPUri);
        if($TOTPObject->getSecret() !== $TOTPCookieValue) {
            return false;
        }
        return true;
    }

    public function updateTOTPUri(string $uri, int $userId) : void
    {
        $this->userApi->updateTotpUri($userId, $uri);
    }

    public function verifyTOTPUri(int $userId, int $userInput, ?string $uri = null) : bool
    {
        if($uri !== null) {
            $totp = Factory::loadFromProvisioningUri($uri);
        } else {
            $totp = Factory::loadFromProvisioningUri($this->userApi->findTOTPUri($userId));
        }
        return $totp->verify($userInput);
    }
}