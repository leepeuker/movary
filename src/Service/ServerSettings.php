<?php declare(strict_types=1);

namespace Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Config;

class ServerSettings
{
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

        $tmdbApiKey = $this->dbConnection->fetchFirstColumn('SELECT tmdb_api_key FROM `server_setting`');

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
}
