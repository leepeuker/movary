<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use InvalidArgumentException;
use JsonException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonProfileService;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class PersonProfileController
{
    public function __construct(
        private readonly PersonApi $personApi,
        private readonly PersonProfileService $profileService,
    ) {
    }

    public function fetchProfiles(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['id']);
        if ($person === null) {
            return Response::createNotFound();
        }

        $profiles = array_map(
            static fn(TmdbProfile $profile) : array => $profile->asArray(),
            $this->profileService->fetchAvailableProfiles($person),
        );

        return Response::createJson(Json::encode([
            'profiles' => $profiles,
            'hasOverride' => $person->getTmdbPosterPathOverride() !== null,
        ]));
    }

    public function restoreDefault(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['id']);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->profileService->restoreDefault($person);

        return Response::createNoContent();
    }

    public function selectProfile(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['id']);
        if ($person === null) {
            return Response::createNotFound();
        }

        try {
            $body = Json::decode($request->getBody());
        } catch (JsonException) {
            return $this->createUnprocessableResponse();
        }

        $profilePath = $body['profilePath'] ?? null;
        if (is_string($profilePath) === false || $profilePath === '') {
            return $this->createUnprocessableResponse();
        }

        try {
            $this->profileService->select($person, $profilePath);
        } catch (InvalidArgumentException) {
            return $this->createUnprocessableResponse();
        }

        return Response::createNoContent();
    }

    private function createUnprocessableResponse() : Response
    {
        return Response::create(StatusCode::createUnprocessableEntity());
    }
}
