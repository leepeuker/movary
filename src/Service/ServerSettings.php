<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;

class ServerSettings
{
    private const APPLICATION_URL = 'APPLICATION_URL';
    private const SMTP_HOST = 'SMTP_HOST';
    private const SMTP_HOST_SENDER_ADDRESS = 'SMTP_HOST_SENDER_ADDRESS';
    private const SMTP_PASSWORD = 'SMTP_PASSWORD';
    private const SMTP_PORT = 'SMTP_PORT';
    private const SMTP_USER = 'SMTP_USER';
    private const SMTP_FROM_ADDRESS = 'SMTP_FROM_ADDRESS';
    private const SMTP_ENCRYPTION = 'SMTP_ENCRYPTION';
    private const SMTP_WITH_AUTH = 'SMTP_WITH_AUTH';
    private const TMDB_API_KEY = 'TMDB_API_KEY';

    public function __construct(
        private readonly Config $config,
        private readonly Connection $dbConnection,
    ) {
    }

    public function getApplicationUrl() : ?string
    {
        try {
            $value = $this->config->getAsString(self::APPLICATION_URL);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::APPLICATION_URL);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getFromAddress() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_FROM_ADDRESS);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_FROM_ADDRESS);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpEncryption() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_ENCRYPTION);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_ENCRYPTION);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpHost() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_HOST);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_HOST);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpHostAddress() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_HOST_SENDER_ADDRESS);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_HOST_SENDER_ADDRESS);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpPassword() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_PASSWORD);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_PASSWORD);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpPort() : ?int
    {
        try {
            $value = $this->config->getAsString(self::SMTP_PORT);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_PORT);
        }

        return (string)$value === '' ? null : (int)$value;
    }

    public function getSmtpUser() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMTP_USER);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_USER);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpWithAuthentication() : ?bool
    {
        try {
            $value = $this->config->getAsString(self::SMTP_WITH_AUTH);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMTP_WITH_AUTH);
        }

        return (string)$value === '' ? null : (bool)$value;
    }

    public function getTmdbApiKey() : ?string
    {
        try {
            $value = $this->config->getAsString(self::TMDB_API_KEY);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::TMDB_API_KEY);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function isApplicationUrlSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::APPLICATION_URL);
    }

    public function isSetInEnvironment(string $key) : bool
    {
        try {
            $this->config->getAsString($key);
        } catch (\OutOfBoundsException) {
            return false;
        }

        return true;
    }

    public function isSmtpEncryptionSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_ENCRYPTION);
    }

    public function isSmtpFromAddressSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_FROM_ADDRESS);
    }

    public function isSmtpHostSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_HOST);
    }

    public function isSmtpPasswordSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_PASSWORD);
    }

    public function isSmtpPortSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_PORT);
    }

    public function isSmtpUserSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_USER);
    }

    public function isSmtpWithAuthenticationSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMTP_FROM_ADDRESS);
    }

    public function isTmdbApiKeySetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::TMDB_API_KEY);
    }

    public function setApplicationUrl(string $applicationUrl) : void
    {
        $this->updateValue(self::APPLICATION_URL, $applicationUrl);
    }

    public function setSmtpEncryption(string $smtpEncryption) : void
    {
        if ($smtpEncryption === '') {
            $smtpEncryption = null;
        }

        $this->updateValue(self::SMTP_ENCRYPTION, $smtpEncryption);
    }

    public function setSmtpFromAddress(string $smtpFromAddress) : void
    {
        $this->updateValue(self::SMTP_FROM_ADDRESS, $smtpFromAddress);
    }

    public function setSmtpFromWithAuthentication(bool $smtpFromWithAuthentication) : void
    {
        $this->updateValue(self::SMTP_WITH_AUTH, $smtpFromWithAuthentication);
    }

    public function setSmtpHost(string $smtpHost) : void
    {
        $this->updateValue(self::SMTP_HOST, $smtpHost);
    }

    public function setSmtpPassword(string $smtpPassword) : void
    {
        $this->updateValue(self::SMTP_PASSWORD, $smtpPassword);
    }

    public function setSmtpPort(string $smtpPort) : void
    {
        $this->updateValue(self::SMTP_PORT, $smtpPort);
    }

    public function setSmtpUser(string $smtpUser) : void
    {
        $this->updateValue(self::SMTP_USER, $smtpUser);
    }

    public function setTmdbApiKey(string $tmdbApiKey) : void
    {
        $this->updateValue(self::TMDB_API_KEY, $tmdbApiKey);
    }

    private function convertEnvironmentKeyToDatabaseKey(string $environmentKey) : string
    {
        return lcfirst(str_replace('_', '', ucwords(strtolower($environmentKey), '_')));
    }

    private function fetchValueFromDatabase(string $environmentKey) : ?string
    {
        $value = $this->dbConnection->fetchFirstColumn(
            'SELECT value FROM `server_setting` WHERE `key` = ?',
            [$this->convertEnvironmentKeyToDatabaseKey($environmentKey)],
        );

        return isset($value[0]) === false ? null : (string)$value[0];
    }

    private function updateValue(string $environmentKey, mixed $value) : void
    {
        $key = $this->convertEnvironmentKeyToDatabaseKey($environmentKey);

        $this->dbConnection->prepare('DELETE FROM `server_setting` WHERE `key` = ?')->executeStatement([$key]);
        $this->dbConnection->prepare('INSERT INTO `server_setting` (value, `key`) VALUES (?, ?)')->executeStatement([(string)$value, $key]);
    }
}
