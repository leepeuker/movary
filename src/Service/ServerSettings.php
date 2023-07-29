<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;
use Movary\ValueObject\Exception\ConfigNotSetException;
use function PHPUnit\Framework\assertNotNull;

class ServerSettings
{
    PRIVATE CONST JELLYFIN_DEVICEID = 'JELLYFIN_DEVICEID';

    private const PLEX_APP_NAME = 'PLEX_APP_NAME';

    private const PLEX_IDENTIFIER = 'PLEX_IDENTIFIER';

    private const APPLICATION_URL = 'APPLICATION_URL';

    private const APPLICATION_VERSION = 'APPLICATION_VERSION';

    private const SMTP_HOST = 'SMTP_HOST';

    private const SMTP_SENDER_ADDRESS = 'SMTP_SENDER_ADDRESS';

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

    public function getAppName() : string
    {
        return $this->getByKey(self::PLEX_APP_NAME) ?? 'Movary';
    }

    public function getApplicationUrl() : ?string
    {
        return $this->getByKey(self::APPLICATION_URL);
    }

    public function getApplicationVersion() : ?string
    {
        return $this->getByKey(self::APPLICATION_VERSION);
    }

    public function getFromAddress() : ?string
    {
        return $this->getByKey(self::SMTP_FROM_ADDRESS);
    }

    public function getPlexIdentifier() : ?string
    {
        return $this->getByKey(self::PLEX_IDENTIFIER);
    }

    public function getJellyfinDeviceId() : ?string
    {
        return $this->getByKey(self::JELLYFIN_DEVICEID);
    }

    public function getSmtpEncryption() : ?string
    {
        return $this->getByKey(self::SMTP_ENCRYPTION);
    }

    public function getSmtpHost() : ?string
    {
        return $this->getByKey(self::SMTP_HOST);
    }

    public function getSmtpPassword() : ?string
    {
        return $this->getByKey(self::SMTP_PASSWORD);
    }

    public function getSmtpPort() : ?int
    {
        return (int)$this->getByKey(self::SMTP_PORT);
    }

    public function getSmtpSenderAddress() : ?string
    {
        return $this->getByKey(self::SMTP_SENDER_ADDRESS);
    }

    public function getSmtpUser() : ?string
    {
        return $this->getByKey(self::SMTP_USER);
    }

    public function getSmtpWithAuthentication() : ?bool
    {
        return (bool)$this->getByKey(self::SMTP_WITH_AUTH);
    }

    public function getTmdbApiKey() : ?string
    {
        return (string)$this->getByKey(self::TMDB_API_KEY);
    }

    public function isApplicationUrlSetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::APPLICATION_URL);
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
        return $this->isSetInEnvironment(self::SMTP_WITH_AUTH);
    }

    public function isTmdbApiKeySetInEnvironment() : bool
    {
        return $this->isSetInEnvironment(self::TMDB_API_KEY);
    }

    public function requireApplicationUrl() : string
    {
        $value = $this->getByKey(self::APPLICATION_URL, true);
        if ($value === null) {
            throw ConfigNotSetException::create(self::APPLICATION_URL);
        }

        return $value;
    }

    public function requirePlexIdentifier() : string
    {
        $value = $this->getByKey(self::PLEX_IDENTIFIER, true);
        if ($value === null) {
            throw ConfigNotSetException::create(self::PLEX_IDENTIFIER);
        }

        return $value;
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

    public function setSmtpPort(int $smtpPort) : void
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

    private function getByKey(string $key, bool $required = false) : ?string
    {
        try {
            $value = $this->config->getAsString($key);
        } catch (ConfigNotSetException $e) {
            $value = $this->fetchValueFromDatabase($key);

            if (empty($value) === true && $required === true) {
                throw $e;
            }
        }

        return (string)$value === '' ? null : (string)$value;
    }

    private function isSetInEnvironment(string $key) : bool
    {
        try {
            $this->config->getAsString($key);
        } catch (ConfigNotSetException) {
            return false;
        }

        return true;
    }

    private function updateValue(string $environmentKey, mixed $value) : void
    {
        $key = $this->convertEnvironmentKeyToDatabaseKey($environmentKey);

        $this->dbConnection->prepare('DELETE FROM `server_setting` WHERE `key` = ?')->executeStatement([$key]);
        $this->dbConnection->prepare('INSERT INTO `server_setting` (value, `key`) VALUES (?, ?)')->executeStatement([(string)$value, $key]);
    }
}
