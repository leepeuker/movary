<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Gender;
use RuntimeException;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : Entity
    {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'poster_path' => $posterPath,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function findByPersonId(int $personId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$personId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findWatchedMoviesActedBy(int $personId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.title, YEAR(m.release_date) AS year
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            WHERE p.id = ? AND m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            ORDER BY m.title
            SQL,
            [$personId]
        );
    }

    public function findWatchedMoviesDirectedBy(int $personId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.title, YEAR(m.release_date) AS year
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            WHERE p.id = ? AND m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            ORDER BY m.title
            SQL,
            [$personId]
        );
    }

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : void
    {
        $this->dbConnection->update(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'poster_path' => $posterPath,
            ],
            [
                'id' => $id,
            ]
        );
    }

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
