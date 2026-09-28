<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ApplicationUrlService;
use Movary\Service\ServerSettings;

class EmailSupport
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
        private readonly SmtpConfigFactory $smtpConfigFactory,
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
    }

    public function isEnabled() : bool
    {
        return $this->serverSettings->isEmailEnabled();
    }

    public function isPasswordResetAvailable() : bool
    {
        return $this->isEnabled()
            && $this->isSmtpConfigured()
            && $this->applicationUrlService->hasApplicationUrl();
    }

    public function isSmtpConfigured() : bool
    {
        try {
            $this->smtpConfigFactory->create();
        } catch (InvalidSmtpConfigException) {
            return false;
        }

        return true;
    }
}
