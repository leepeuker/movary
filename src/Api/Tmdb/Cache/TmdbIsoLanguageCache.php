<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Tmdb\TmdbClient;
use RuntimeException;

class TmdbIsoLanguageCache
{
    private array $languages = [];

    public function __construct(
        private readonly Connection $dbConnection,
        private readonly TmdbClient $client,
    ) {
    }

    public function delete() : void
    {
        $this->dbConnection->executeQuery('DELETE FROM cache_tmdb_languages');

        $this->languages = [];
    }

    public function getLanguageByCode(string $languageCode) : string
    {
        $this->fetchAll();

        foreach ($this->languages as $iso6931 => $englishName) {
            if ($iso6931 === $languageCode) {
                return $englishName;
            }
        }

        throw new RuntimeException('Language code not handled: ' . $languageCode);
    }

    public function fetchAll() : array
    {
        if ($this->languages === []) {
            $this->loadFromDatabase();
        }

        if ($this->languages === []) {
            $this->loadFromTmdb();
        }

        return $this->languages;
    }

    public function loadFromTmdb() : bool
    {
        $languages = $this->client->get('/configuration/languages');

        $this->dbConnection->beginTransaction();

        $existingIsoCodes = $this->dbConnection->fetchFirstColumn('SELECT iso_639_1 FROM cache_tmdb_languages');

        foreach ($languages as $language) {
            if (in_array($language['iso_639_1'], $existingIsoCodes, true) === true) {
                continue;
            }

            $this->dbConnection->insert('cache_tmdb_languages', ['iso_639_1' => $language['iso_639_1'], 'english_name' => $language['english_name']]);
        }

        $this->dbConnection->commit();

        $this->loadFromDatabase();

        return true;
    }

    private function loadFromDatabase() : bool
    {
        $this->languages = $this->dbConnection->fetchAllKeyValue('SELECT iso_639_1, english_name FROM cache_tmdb_languages ORDER BY english_name');

        return empty($this->languages);
    }
}
