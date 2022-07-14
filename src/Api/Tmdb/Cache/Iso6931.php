<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Tmdb\Client;

class Iso6931
{
    private array $languages = [];

    public function __construct(private readonly Connection $dbConnection, private readonly Client $client)
    {
    }

    public function getLanguageByCode(string $languageCode) : string
    {
        if ($this->languages === []) {
            $this->loadFromDatabase();
        }

        if ($this->languages === []) {
            $this->loadFromTmdb();
        }

        foreach ($this->languages as $iso6931 => $englishName) {
            if ($iso6931 === $languageCode) {
                return $englishName;
            }
        }

        throw new \RuntimeException('Language code not handled: ' . $languageCode);
    }

    private function loadFromDatabase() : bool
    {
        $this->languages = $this->dbConnection->fetchAllKeyValue('SELECT iso_639_1, english_name FROM cache_tmdb_languages');

        return empty($this->languages);
    }

    private function loadFromTmdb() : bool
    {
        $languages = $this->client->get('/configuration/languages');

        foreach ($languages as $language) {
            $this->dbConnection->insert('cache_tmdb_languages', ['iso_639_1' => $language['iso_639_1'], 'english_name' => $language['english_name']]);
        }

        $this->loadFromDatabase();

        return true;
    }
}
