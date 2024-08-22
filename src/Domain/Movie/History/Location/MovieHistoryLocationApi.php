<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

class MovieHistoryLocationApi
{
    public function __construct(private readonly MovieHistoryLocationRepository $locationRepository)
    {
    }

    public function createLocation(int $userId, string $name) : void
    {
        $this->locationRepository->createLocation($userId, $name);
    }

    public function deleteLocation(int $locationId) : void
    {
        $this->locationRepository->deleteLocation($locationId);
    }

    public function findLocationById(int $locationId) : ?MovieHistoryLocationEntity
    {
        return $this->locationRepository->findLocationById($locationId);
    }

    public function findLocationsByUserId(int $userId) : MovieHistoryLocationEntityList
    {
        return $this->locationRepository->findLocationsByUserId($userId);
    }

    public function updateLocation(int $locationId, string $name) : void
    {
        $this->locationRepository->updateLocation($locationId, $name);
    }
}
