<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use RuntimeException;

class MovieHistoryLocationApi
{
    public function __construct(private readonly MovieHistoryLocationRepository $locationRepository)
    {
    }

    public function createLocation(int $userId, string $name, bool $isCinema) : void
    {
        $this->locationRepository->createLocation($userId, $name, $isCinema);
    }

    public function createOrUpdate(int $userId, string $locationName) : int
    {
        $location = $this->findLocationByName($userId, $locationName);

        if ($location === null) {
            $this->createLocation($userId, $locationName, false);

            $location = $this->fetchLocationByName($userId, $locationName);
        }

        return $location->getId();
    }

    public function deleteLocation(int $locationId) : void
    {
        $this->locationRepository->deleteLocation($locationId);
    }

    public function fetchLocationByName(int $userId, string $locationName) : MovieHistoryLocationEntity
    {
        $location = $this->locationRepository->findLocationByName($userId, $locationName);

        if ($location === null) {
            throw new RuntimeException('Location not found: ' . $locationName);
        }

        return $location;
    }

    public function findLocationById(int $locationId) : ?MovieHistoryLocationEntity
    {
        return $this->locationRepository->findLocationById($locationId);
    }

    public function findLocationByName(int $userId, string $locationName) : ?MovieHistoryLocationEntity
    {
        return $this->locationRepository->findLocationByName($userId, $locationName);
    }

    public function findLocationsByUserId(int $userId) : MovieHistoryLocationEntityList
    {
        return $this->locationRepository->findLocationsByUserId($userId);
    }

    public function updateLocation(int $locationId, string $name, bool $isCinema) : void
    {
        $this->locationRepository->updateLocation($locationId, $name, $isCinema);
    }
}
