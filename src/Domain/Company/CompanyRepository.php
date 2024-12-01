<?php declare(strict_types=1);

namespace Movary\Domain\Company;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;
use RuntimeException;

class CompanyRepository
{
    private const string TABLE_NAME = 'company';

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : array
    {
        $this->dbConnection->insert(
            self::TABLE_NAME,
            [
                'name' => $name,
                'origin_country' => $originCountry,
                'tmdb_id' => $tmdbId,
                'created_at' => (string)DateTime::create(),
            ],
        );

        $lastInsertId = (int)$this->dbConnection->lastInsertId();

        return $this->fetchById($lastInsertId);
    }

    public function delete(int $tmdbId) : void
    {
        $this->dbConnection->delete(self::TABLE_NAME, ['tmdb_id' => $tmdbId]);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?array
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE name = ? AND origin_country = ?', [$name, $originCountry]);

        return $data === false ? null : $data;
    }

    public function findByTmdbId(int $tmdbId) : ?array
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE tmdb_id = ?', [$tmdbId]);

        return $data === false ? null : $data;
    }

    public function update(int $id, string $name, ?string $originCountry) : array
    {
        $this->dbConnection->update(
            self::TABLE_NAME,
            [
                'name' => $name,
                'origin_country' => $originCountry,
            ],
            [
                'id' => $id,
            ],
        );

        return $this->fetchById($id);
    }

    private function fetchById(int $id) : array
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No company found by id: ' . $id);
        }

        return $data;
    }
}
