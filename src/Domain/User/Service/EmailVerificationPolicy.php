<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\InvalidEmailVerificationConfiguration;
use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Config;

class EmailVerificationPolicy
{
    private const string CONFIGURATION_KEY = 'EMAIL_VERIFICATION_REQUIRED';

    public function __construct(
        private readonly Config $config,
        private readonly EmailSupport $emailSupport,
    ) {
    }

    public function isRequired() : bool
    {
        if ($this->config->getAsBool(self::CONFIGURATION_KEY, false) === false) {
            return false;
        }
        if ($this->emailSupport->isEmailVerificationAvailable() === false) {
            throw InvalidEmailVerificationConfiguration::create();
        }

        return true;
    }
}
