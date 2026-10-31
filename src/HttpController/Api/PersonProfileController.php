<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonProfileService;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PersonProfileController
{
    public function __construct(
        private readonly PersonApi $personApi,
        private readonly PersonProfileService $profileService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function fetchProfiles(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['personId']);
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

    public function selectProfile(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['personId']);
        if ($person === null) {
            return Response::createNotFound();
        }

        $body = $this->requestMapper->mapJsonObject($request);
        $profilePath = $body['profilePath'] ?? null;
        if (is_string($profilePath) === false || $profilePath === '') {
            throw InvalidApiRequest::invalidField('profilePath', 'is required and must be a non-empty string');
        }

        try {
            $this->profileService->select($person, $profilePath);
        } catch (InvalidArgumentException) {
            throw InvalidApiRequest::invalidField('profilePath', 'must identify an available TMDB profile');
        }

        return Response::createNoContent();
    }

    public function restoreDefault(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['personId']);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->profileService->restoreDefault($person);

        return Response::createNoContent();
    }
}
