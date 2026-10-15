<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\ValueObject\Config;

class EmailVerificationPolicy
{
    private const string CONFIGURATION_KEY = 'EMAIL_VERIFICATION_REQUIRED';

    public function __construct(private readonly Config $config)
    {
    }

    public function isRequired() : bool
    {
        return $this->config->getAsBool(self::CONFIGURATION_KEY, false);
    }
}
