<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class LocationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly MovieHistoryLocationApi $locationApi,
        private readonly UserApi $userApi,
    ) {
    }

    public function createLocation(Request $request) : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();
        $requestData = Json::decode($request->getBody());

        $this->locationApi->createLocation(
            $currentUserId,
            $requestData['name'],
            empty($requestData['isCinema']) === false,
        );

        return Response::createOk();
    }

    public function deleteLocation(Request $request) : Response
    {
        $locationId = (int)$request->getRouteParameters()['locationId'];
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();

        $location = $this->locationApi->findLocationById($locationId);

        if ($location === null) {
            return Response::createOk();
        }

        if ($location->getUserId() !== $currentUserId) {
            return Response::createForbidden();
        }

        $this->locationApi->deleteLocation($locationId);

        return Response::createOk();
    }

    public function fetchLocations() : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();

        $locations = $this->locationApi->findLocationsByUserId($currentUserId);

        return Response::createJson(Json::encode($locations));
    }

    public function fetchToggleFeature() : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();

        $isLocationsEnabled = $this->userApi->isLocationsEnabled($currentUserId);

        return Response::createJson(
            Json::encode(
                ['locationsEnabled' => $isLocationsEnabled],
            ),
        );
    }

    public function updateLocation(Request $request) : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();
        $locationId = (int)$request->getRouteParameters()['locationId'];
        $requestData = Json::decode($request->getBody());

        $location = $this->locationApi->findLocationById($locationId);

        if ($location === null) {
            return Response::createOk();
        }

        if ($location->getUserId() !== $currentUserId) {
            return Response::createForbidden();
        }

        $this->locationApi->updateLocation(
            $locationId,
            $requestData['name'],
            (bool)$requestData['isCinema'],
        );

        return Response::createOk();
    }

    public function updateToggleFeature(Request $request) : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();
        $requestData = Json::decode($request->getBody());

        $this->userApi->updateLocationsEnabled(
            $currentUserId,
            $requestData['locationsEnabled'],
        );

        return Response::createNoContent();
    }
}
