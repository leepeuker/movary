<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\DateTime;

class MovieHistoryLocationRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createLocation(int $userId, string $name) : void
    {
        $timestamp = DateTime::create();

        $this->dbConnection->insert(
            'location',
            [
                'user_id' => $userId,
                'name' => $name,
                'created_at' => (string)$timestamp,
                'updated_at' => (string)$timestamp,
            ],
        );
    }

    public function deleteLocation(int $locationId) : void
    {
        $this->dbConnection->delete('location', ['id' => $locationId]);
    }

    public function findLocationsByUserId(int $userId) : MovieHistoryLocationEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative(
            'SELECT *
            FROM `location` 
            WHERE user_id = ?
            ORDER BY name',
            [$userId],
        );

        return MovieHistoryLocationEntityList::createFromArray($data);
    }

    public function findLocationById(int $locationId) : ?MovieHistoryLocationEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `location` WHERE `id` = ?', [$locationId]);

        if (empty($data) === true) {
            return null;
        }

        return MovieHistoryLocationEntity::createFromArray($data);
    }

    public function updateLocation(int $locationId, string $name) : void
    {
        $this->dbConnection->update(
            'location',
            [
                'name' => $name,
                'updated_at' => (string)DateTime::create(),
            ],
            [
                'id' => $locationId,
            ],
        );
    }
}
