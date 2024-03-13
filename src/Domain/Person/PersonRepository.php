<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use RuntimeException;
use Traversable;

class PersonRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?string $biography = null,
        ?Date $birthDate = null,
        ?Date $deathDate = null,
        ?string $placeOfBirth = null,
        ?DateTime $updatedAtTmdb = null,
        ?string $imdbId = null,
    ) : PersonEntity {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'imdb_id' => $imdbId,
                'tmdb_poster_path' => $tmdbPosterPath,
                'biography' => $biography === null ? null : $biography,
                'birth_date' => $birthDate === null ? null : (string)$birthDate,
                'death_date' => $deathDate === null ? null : (string)$deathDate,
                'place_of_birth' => $placeOfBirth,
                'updated_at_tmdb' => $updatedAtTmdb === null ? null : (string)$updatedAtTmdb,
                'created_at' => (string)DateTime::create(),
            ],
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function deleteAllNotReferenced() : void
    {
        $this->dbConnection->executeQuery(
            'DELETE
            FROM person
            WHERE id NOT IN (
                SELECT person_id FROM movie_cast
                UNION
                SELECT person_id FROM movie_crew
            )',
        );
    }

    public function deleteById(int $id) : void
    {
        $this->dbConnection->delete('movie_cast', ['person_id' => $id]);
        $this->dbConnection->delete('movie_crew', ['person_id' => $id]);
        $this->dbConnection->delete('person', ['id' => $id]);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null, ?array $ids = null) : Traversable
    {
        $whereQuery = '';
        if ($ids !== null && count($ids) > 0) {
            $placeholders = str_repeat('?, ', count($ids));
            $whereQuery = ' WHERE id IN (' . trim($placeholders, ', ') . ')';
        }

        $query = "SELECT * FROM `person` $whereQuery ORDER BY updated_at_tmdb, created_at";

        if ($limit !== null) {
            $query .= ' LIMIT ' . $limit;
        }

        return $this->dbConnection->prepare($query)->executeQuery($ids ?? [])->iterateAssociative();
    }

    public function findByPersonId(int $personId) : ?PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$personId]);

        if ($data === false) {
            return null;
        }

        return PersonEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return PersonEntity::createFromArray($data);
    }

    public function update(
        int $id,
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?string $biography,
        ?Date $birthDate,
        ?Date $deathDate,
        ?string $placeOfBirth,
        ?DateTime $updatedAtTmdb,
        ?string $imdbId,
    ) : PersonEntity {
        $payload = [
            'name' => $name,
            'gender' => $gender->asInt(),
            'known_for_department' => $knownForDepartment,
            'tmdb_id' => $tmdbId,
            'imdb_id' => $imdbId,
            'tmdb_poster_path' => $tmdbPosterPath,
            'biography' => $biography === null ? null : $biography,
            'birth_date' => $birthDate === null ? null : (string)$birthDate,
            'death_date' => $deathDate === null ? null : (string)$deathDate,
            'place_of_birth' => $placeOfBirth,
            'updated_at' => (string)DateTime::create(),
        ];

        if ($updatedAtTmdb !== null) {
            $payload['updated_at_tmdb'] = (string)$updatedAtTmdb;
        }

        $this->dbConnection->update(
            'person', $payload, ['id' => $id],
        );

        return $this->fetchById($id);
    }

    public function updateHideInTopLists(int $userId, int $personId, bool $isHidden) : void
    {
        $this->dbConnection->executeQuery('DELETE FROM user_person_settings WHERE user_id = ? AND person_id = ?', [$userId, $personId]);

        if ($isHidden === false) {
            return;
        }

        $this->dbConnection->insert(
            'user_person_settings',
            [
                'user_id' => $userId,
                'person_id' => $personId,
                'is_hidden_in_top_lists' => 1,
                'updated_at' => (string)DateTime::create()
            ],
        );
    }

    public function updateWithTmdbCreditsData(
        int $id,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
    ) : PersonEntity {
        $updatedAt = (string)DateTime::create();

        $payload = [
            'name' => $name,
            'gender' => $gender->asInt(),
            'known_for_department' => $knownForDepartment,
            'tmdb_poster_path' => $tmdbPosterPath,
            'updated_at' => $updatedAt,
            'updated_at_tmdb' => $updatedAt,
        ];

        $this->dbConnection->update(
            'person', $payload, ['id' => $id],
        );

        return $this->fetchById($id);
    }

    private function fetchById(int $id) : PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return PersonEntity::createFromArray($data);
    }
}
