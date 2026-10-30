<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\LocationRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class LocationController
{
    public function __construct(
        private readonly MovieHistoryLocationApi $locationApi,
        private readonly UserApi $userApi,
        private readonly LocationRequestMapper $locationRequestMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function createLocation(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $location = $this->locationRequestMapper->mapLocation($request);
        $this->locationApi->createLocation($userId, $location['name'], $location['isCinema']);

        return Response::createNoContent();
    }

    public function deleteLocation(Request $request) : Response
    {
        $location = $this->findOwnedLocation($request);
        if ($location !== null) {
            $this->locationApi->deleteLocation($location->getId());
        }

        return Response::createNoContent();
    }

    public function getLocation(Request $request) : Response
    {
        $location = $this->findOwnedLocation($request);
        if ($location === null) {
            return Response::createNotFound();
        }

        return Response::createSensitiveJson(Json::encode($location));
    }

    public function getLocations(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();

        return Response::createSensitiveJson(Json::encode([
            'locations' => $this->locationApi->findLocationsByUserId($userId),
        ]));
    }

    public function getSettings(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();

        return Response::createSensitiveJson(Json::encode([
            'locationsEnabled' => $this->userApi->isLocationsEnabled($userId),
        ]));
    }

    public function updateLocation(Request $request) : Response
    {
        $location = $this->findOwnedLocation($request);
        if ($location === null) {
            return Response::createNotFound();
        }

        $data = $this->locationRequestMapper->mapLocation($request);
        $this->locationApi->updateLocation($location->getId(), $data['name'], $data['isCinema']);

        return Response::createNoContent();
    }

    public function updateSettings(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $this->userApi->updateLocationsEnabled($userId, $this->locationRequestMapper->mapLocationsEnabled($request));

        return Response::createNoContent();
    }

    private function findOwnedLocation(Request $request) : ?MovieHistoryLocationEntity
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $locationId = (int)$request->getRouteParameters()['locationId'];
        $location = $this->locationApi->findLocationById($locationId);

        return $location?->getUserId() === $userId ? $location : null;
    }
}
