<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;

class ServerSettings
{
    private const APPLICATION_URL = 'APPLICATION_URL';
    private const SMPT_HOST = 'SMPT_HOST';
    private const SMPT_HOST_SENDER_ADDRESS = 'SMPT_HOST_SENDER_ADDRESS';
    private const SMPT_PASSWORD = 'SMPT_PASSWORD';
    private const SMPT_PORT = 'SMPT_PORT';
    private const SMPT_USER = 'SMPT_USER';
    private const SMPT_FROM_ADDRESS = 'SMPT_FROM_ADDRESS';
    private const SMPT_WITH_AUTH = 'SMPT_WITH_AUTH';
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
            $value = $this->config->getAsString(self::SMPT_FROM_ADDRESS);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_FROM_ADDRESS);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpHost() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMPT_HOST);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_HOST);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpHostAddress() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMPT_HOST_SENDER_ADDRESS);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_HOST_SENDER_ADDRESS);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpPassword() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMPT_PASSWORD);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_PASSWORD);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpPort() : ?int
    {
        try {
            $value = $this->config->getAsString(self::SMPT_PORT);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_PORT);
        }

        return (string)$value === '' ? null : (int)$value;
    }

    public function getSmtpUser() : ?string
    {
        try {
            $value = $this->config->getAsString(self::SMPT_USER);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_USER);
        }

        return (string)$value === '' ? null : (string)$value;
    }

    public function getSmtpWithAuthentication() : ?bool
    {
        try {
            $value = $this->config->getAsString(self::SMPT_WITH_AUTH);
        } catch (\OutOfBoundsException) {
            $value = $this->fetchValueFromDatabase(self::SMPT_WITH_AUTH);
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

    public function isSmtpFromAddressSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_FROM_ADDRESS);
    }

    public function isSmtpHostSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_HOST);
    }

    public function isSmtpPasswordSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_PASSWORD);
    }

    public function isSmtpPortSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_PORT);
    }

    public function isSmtpUserSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_USER);
    }

    public function isSmtpWithAuthenticationSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::SMPT_FROM_ADDRESS);
    }

    public function isTmdbApiKeySetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::TMDB_API_KEY);
    }

    public function setApplicationUrl(string $applicationUrl) : void
    {
        $this->updateValue(self::APPLICATION_URL, $applicationUrl);
    }

    public function setSmptFromAddress(string $smtpFromAddress) : void
    {
        $this->updateValue(self::SMPT_FROM_ADDRESS, $smtpFromAddress);
    }

    public function setSmptFromWithAuthentication(bool $smtpFromWithAuthentication) : void
    {
        $this->updateValue(self::SMPT_WITH_AUTH, $smtpFromWithAuthentication);
    }

    public function setSmptHost(string $smtpHost) : void
    {
        $this->updateValue(self::SMPT_HOST, $smtpHost);
    }

    public function setSmptPassword(string $smtpPassword) : void
    {
        $this->updateValue(self::SMPT_PASSWORD, $smtpPassword);
    }

    public function setSmptPort(string $smtpPort) : void
    {
        $this->updateValue(self::SMPT_PORT, $smtpPort);
    }

    public function setSmptUser(string $smtpUser) : void
    {
        $this->updateValue(self::SMPT_USER, $smtpUser);
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
