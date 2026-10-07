<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class MovieHistoryLocationRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createLocation(int $userId, string $name, bool $isCinema) : void
    {
        $timestamp = DateTime::create();

        $this->dbConnection->insert(
            'location',
            [
                'user_id' => $userId,
                'name' => $name,
                'is_cinema' => (int)$isCinema,
                'created_at' => (string)$timestamp,
                'updated_at' => (string)$timestamp,
            ],
        );
    }

    public function countLocationsByUserId(int $userId) : int
    {
        return (int)$this->dbConnection->fetchOne(
            'SELECT COUNT(*) FROM `location` WHERE user_id = ?',
            [$userId],
        );
    }

    public function deleteLocation(int $locationId) : void
    {
        $this->dbConnection->delete('location', ['id' => $locationId]);
    }

    public function findLocationById(int $locationId) : ?MovieHistoryLocationEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `location` WHERE `id` = ?', [$locationId]);

        if (empty($data) === true) {
            return null;
        }

        return MovieHistoryLocationEntity::createFromArray($data);
    }

    public function findLocationByName(int $userId, string $locationName) : ?MovieHistoryLocationEntity
    {
        $data = $this->dbConnection->fetchAssociative(
            'SELECT *
            FROM `location` 
            WHERE user_id = ? AND name = ?',
            [$userId, $locationName],
        );

        return $data === false ? null : MovieHistoryLocationEntity::createFromArray($data);
    }

    public function findLocationsByUserId(int $userId) : MovieHistoryLocationEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative(
            'SELECT *
            FROM `location` 
            WHERE user_id = ?
            ORDER BY name, id',
            [$userId],
        );

        return MovieHistoryLocationEntityList::createFromArray($data);
    }

    public function findLocationsByUserIdPaginated(
        int $userId,
        int $limit,
        int $offset,
    ) : MovieHistoryLocationEntityList {
        $data = $this->dbConnection->fetchAllAssociative(
            "SELECT `location`.*,
                (SELECT COALESCE(SUM(plays), 0)
                 FROM movie_user_watch_dates
                 WHERE location_id = `location`.id) AS plays
            FROM `location`
            WHERE `location`.user_id = ?
            ORDER BY `location`.name, `location`.id
            LIMIT $limit OFFSET $offset",
            [$userId],
        );

        return MovieHistoryLocationEntityList::createFromArray($data);
    }

    public function updateLocation(int $locationId, string $name, bool $isCinema) : void
    {
        $this->dbConnection->update(
            'location',
            [
                'name' => $name,
                'is_cinema' => (int)$isCinema,
                'updated_at' => (string)DateTime::create(),
            ],
            [
                'id' => $locationId,
            ],
        );
    }
}
