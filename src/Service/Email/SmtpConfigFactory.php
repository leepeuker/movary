<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ServerSettings;

class SmtpConfigFactory
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
    ) {
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function create(array $overrides = []) : SmtpConfig
    {
        $host = $this->serverSettings->isSmtpHostSetInEnvironment() || isset($overrides['smtpHost']) === false
            ? $this->serverSettings->getSmtpHost()
            : $overrides['smtpHost'];
        $port = $this->serverSettings->isSmtpPortSetInEnvironment() || isset($overrides['smtpPort']) === false
            ? $this->serverSettings->getSmtpPort()
            : $overrides['smtpPort'];
        $fromAddress = $this->serverSettings->isSmtpFromAddressSetInEnvironment()
            || isset($overrides['smtpFromAddress']) === false
                ? $this->serverSettings->getFromAddress()
                : $overrides['smtpFromAddress'];
        $encryption = $this->serverSettings->isSmtpEncryptionSetInEnvironment()
            || isset($overrides['smtpEncryption']) === false
                ? $this->serverSettings->getSmtpEncryption()
                : $overrides['smtpEncryption'];
        $withAuthentication = $this->serverSettings->isSmtpWithAuthenticationSetInEnvironment()
            || isset($overrides['smtpWithAuthentication']) === false
                ? $this->serverSettings->getSmtpWithAuthentication()
                : $overrides['smtpWithAuthentication'];
        $user = $this->serverSettings->isSmtpUserSetInEnvironment() || isset($overrides['smtpUser']) === false
            ? $this->serverSettings->getSmtpUser()
            : $overrides['smtpUser'];
        $password = $this->serverSettings->isSmtpPasswordSetInEnvironment()
            || isset($overrides['smtpPassword']) === false
                ? $this->serverSettings->getSmtpPassword()
                : $overrides['smtpPassword'];

        return SmtpConfig::create(
            (string)$host,
            (int)$port,
            (string)$fromAddress,
            $encryption === null ? null : (string)$encryption,
            (bool)$withAuthentication,
            $user === null ? null : (string)$user,
            $password === null ? null : (string)$password,
        );
    }
}
