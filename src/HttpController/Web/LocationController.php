<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class LocationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly MovieHistoryLocationApi $locationApi,
    ) {
    }

    public function createLocation(Request $request) : Response
    {
        $currentUser = $this->authenticationService->getCurrentUser();
        $requestData = Json::decode($request->getBody());

        $this->locationApi->createLocation(
            $currentUser->getId(),
            $requestData['name'],
        );

        return Response::createOk();
    }

    public function deleteLocation(Request $request) : Response
    {
        $locationId = (int)$request->getRouteParameters()['locationId'];
        $currentUser = $this->authenticationService->getCurrentUser();

        $location = $this->locationApi->findLocationById($locationId);

        if ($location === null) {
            return Response::createOk();
        }

        if ($location->getUserId() !== $currentUser->getId()) {
            return Response::createForbidden();
        }

        $this->locationApi->deleteLocation($locationId);

        return Response::createOk();
    }

    public function fetchLocations() : Response
    {
        $currentUser = $this->authenticationService->getCurrentUser();

        $locations = $this->locationApi->findLocationsByUserId($currentUser->getId());

        return Response::createJson(Json::encode($locations));
    }

    public function updateLocation(Request $request) : Response
    {
        $currentUser = $this->authenticationService->getCurrentUser();
        $locationId = (int)$request->getRouteParameters()['locationId'];
        $requestData = Json::decode($request->getBody());

        $location = $this->locationApi->findLocationById($locationId);

        if ($location === null) {
            return Response::createOk();
        }

        if ($location->getUserId() !== $currentUser->getId()) {
            return Response::createForbidden();
        }

        $this->locationApi->updateLocation(
            $locationId,
            $requestData['name'],
        );

        return Response::createOk();
    }
}
