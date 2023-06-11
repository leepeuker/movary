<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;

class ServerSettings
{
    private const TMDB_API_KEY = 'tmdbApiKey';

    private const APPLICATION_URL_KEY = 'applicationUrl';

    public function __construct(
        private readonly Config $config,
        private readonly Connection $dbConnection,
    ) {
    }

    public function getApplicationUrl() : ?string
    {
        if ($this->isApplicationUrlSetInEnvironment() === true) {
            return $this->config->getAsString('APPLICATION_URL');
        }

        $applicationUrl = $this->dbConnection->fetchFirstColumn(
            'SELECT value FROM `server_setting` WHERE `key` = ?',
            [self::APPLICATION_URL_KEY],
        );

        return empty($applicationUrl[0]) === true ? null : $applicationUrl[0];
    }

    public function getTmdbApiKey() : ?string
    {
        if ($this->isTmdbApiKeySetInEnvironment() === true) {
            return $this->config->getAsString('TMDB_API_KEY');
        }

        $tmdbApiKey = $this->dbConnection->fetchFirstColumn(
            'SELECT value FROM `server_setting` WHERE `key` = ?',
            [self::TMDB_API_KEY],
        );

        return empty($tmdbApiKey[0]) === true ? null : $tmdbApiKey[0];
    }

    public function isApplicationUrlSetInEnvironment() : bool
    {
        try {
            $tmdbApiKey = $this->config->getAsString('APPLICATION_URL');
        } catch (\RuntimeException) {
            return false;
        }

        return empty($tmdbApiKey) === false;
    }

    public function isTmdbApiKeySetInEnvironment() : bool
    {
        try {
            $tmdbApiKey = $this->config->getAsString('TMDB_API_KEY');
        } catch (\RuntimeException) {
            return false;
        }

        return empty($tmdbApiKey) === false;
    }

    public function setApplicationUrl(string $applicationUrl) : void
    {
        $this->dbConnection->prepare('DELETE FROM `server_setting` WHERE `key` = ?')->executeStatement([self::APPLICATION_URL_KEY]);
        $this->dbConnection->prepare('INSERT INTO `server_setting` (value, `key`) VALUES (?, ?)')->executeStatement([$applicationUrl, self::APPLICATION_URL_KEY]);
    }

    public function setTmdbApiKey(string $tmdbApiKey) : void
    {
        $this->dbConnection->prepare('DELETE FROM `server_setting` WHERE `key` = ?')->executeStatement([self::TMDB_API_KEY]);
        $this->dbConnection->prepare('INSERT INTO `server_setting` (value, `key`) VALUES (?, ?)')->executeStatement([$tmdbApiKey, self::TMDB_API_KEY]);
    }
}
