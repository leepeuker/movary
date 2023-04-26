<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;

class ServerSettings
{
    private const TMDB_API_KEY = 'tmdbApiKey';

    public function __construct(
        private readonly Config $config,
        private readonly Connection $dbConnection,
    ) {
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

        return empty ($tmdbApiKey) === true ? null : $tmdbApiKey[0];
    }

    public function isTmdbApiKeySetInEnvironment() : bool
    {
        try {
            $tmdbApiKey = $this->config->getAsString('TMDB_API_KEY');
        } catch (\OutOfBoundsException) {
            return false;
        }

        return empty($tmdbApiKey) === false;
    }

    public function setTmdbApiKey(string $tmdbApiKey) : void
    {
        if ($this->getTmdbApiKey() === null) {
            $this->dbConnection->prepare('INSERT INTO `server_setting` (value, `key`) VALUES (?, ?)')->executeStatement([$tmdbApiKey, self::TMDB_API_KEY]);

            return;
        }

        $this->dbConnection->prepare('UPDATE `server_setting` SET value = ? WHERE `key` = ?')->executeStatement([$tmdbApiKey, self::TMDB_API_KEY]);
    }
}
