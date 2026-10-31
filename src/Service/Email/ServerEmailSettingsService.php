<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Doctrine\DBAL\Connection;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Config;

class ServerEmailSettingsService
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
        private readonly SmtpConfigFactory $smtpConfigFactory,
        private readonly PasswordResetTokenService $passwordResetTokenService,
        private readonly Config $config,
        private readonly Connection $dbConnection,
    ) {
    }

    /** @return list<string> */
    public function getEnvironmentControlledFields() : array
    {
        $fields = [];
        if ($this->serverSettings->isEmailEnabledSetInEnvironment() === true) {
            $fields[] = 'emailEnabled';
        }
        if ($this->serverSettings->isSmtpHostSetInEnvironment() === true) {
            $fields[] = 'smtpHost';
        }
        if ($this->serverSettings->isSmtpPortSetInEnvironment() === true) {
            $fields[] = 'smtpPort';
        }
        if ($this->serverSettings->isSmtpFromAddressSetInEnvironment() === true) {
            $fields[] = 'smtpFromAddress';
        }
        if ($this->serverSettings->isSmtpEncryptionSetInEnvironment() === true) {
            $fields[] = 'smtpEncryption';
        }
        if ($this->serverSettings->isSmtpWithAuthenticationSetInEnvironment() === true) {
            $fields[] = 'smtpWithAuthentication';
        }
        if ($this->serverSettings->isSmtpUserSetInEnvironment() === true) {
            $fields[] = 'smtpUser';
        }
        if ($this->serverSettings->isSmtpPasswordSetInEnvironment() === true) {
            $fields[] = 'smtpPassword';
        }

        return $fields;
    }

    /** @param array<string, mixed> $requestData */
    public function update(array $requestData) : void
    {
        $emailEnabled = $this->serverSettings->isEmailEnabledSetInEnvironment() === true
            ? $this->serverSettings->isEmailEnabled()
            : (bool)($requestData['emailEnabled'] ?? $this->serverSettings->isEmailEnabled());
        if ($emailEnabled === false && $this->config->getAsBool('EMAIL_VERIFICATION_REQUIRED', false) === true) {
            throw new EmailSettingsConflict('Email cannot be disabled while email verification is required.');
        }

        $saveSmtp = $emailEnabled === true || array_diff(array_keys($requestData), ['emailEnabled']) !== [];
        if ($saveSmtp === true) {
            $this->smtpConfigFactory->create($requestData);
        }

        $this->dbConnection->transactional(function () use ($requestData, $emailEnabled, $saveSmtp) : void {
            if ($saveSmtp === true) {
                $this->saveSmtp($requestData);
            }
            $this->serverSettings->setEmailEnabled($emailEnabled);
            if ($emailEnabled === false) {
                $this->passwordResetTokenService->deleteAllTokens();
            }
        });
    }

    public function delete() : void
    {
        if ($this->getEnvironmentControlledFields() !== []) {
            throw new EmailSettingsConflict('Email settings controlled by environment variables cannot be removed.');
        }
        if ($this->config->getAsBool('EMAIL_VERIFICATION_REQUIRED', false) === true) {
            throw new EmailSettingsConflict('Email cannot be disabled while email verification is required.');
        }

        $this->dbConnection->transactional(function () : void {
            $this->serverSettings->clearSmtpConfiguration();
            $this->serverSettings->setEmailEnabled(false);
            $this->passwordResetTokenService->deleteAllTokens();
        });
    }

    /** @param array<string, mixed> $requestData */
    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function saveSmtp(array $requestData) : void
    {
        $smtpHost = isset($requestData['smtpHost']) === false ? null : $requestData['smtpHost'];
        $smtpPort = isset($requestData['smtpPort']) === false ? null : $requestData['smtpPort'];
        $smtpFromAddress = isset($requestData['smtpFromAddress']) === false ? null : $requestData['smtpFromAddress'];
        $smtpEncryption = isset($requestData['smtpEncryption']) === false ? null : $requestData['smtpEncryption'];
        $smtpWithAuthentication = isset($requestData['smtpWithAuthentication']) === false ? null : (bool)$requestData['smtpWithAuthentication'];
        $smtpUser = isset($requestData['smtpUser']) === false ? null : $requestData['smtpUser'];
        $smtpPassword = isset($requestData['smtpPassword']) === false ? null : $requestData['smtpPassword'];

        if ($smtpHost !== null) {
            $this->serverSettings->setSmtpHost($smtpHost);
        }
        if ($smtpPort !== null) {
            $this->serverSettings->setSmtpPort((int)$smtpPort);
        }
        if ($smtpFromAddress !== null) {
            $this->serverSettings->setSmtpFromAddress($smtpFromAddress);
        }
        if ($smtpEncryption !== null) {
            $this->serverSettings->setSmtpEncryption($smtpEncryption);
        }
        if ($smtpWithAuthentication !== null) {
            $this->serverSettings->setSmtpFromWithAuthentication($smtpWithAuthentication);
        }

        $smtpAuthenticationEnabled = $this->serverSettings->getSmtpWithAuthentication() === true;
        if ($smtpWithAuthentication === false
            && $this->serverSettings->isSmtpWithAuthenticationSetInEnvironment() === false
        ) {
            $this->serverSettings->clearSmtpAuthenticationCredentials();
        } elseif ($smtpAuthenticationEnabled === true) {
            if ($smtpUser !== null) {
                $this->serverSettings->setSmtpUser($smtpUser);
            }
            if ($smtpPassword !== null) {
                $this->serverSettings->setSmtpPassword($smtpPassword);
            }
        }
    }
}
