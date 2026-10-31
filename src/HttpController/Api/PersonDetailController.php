<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Person\PersonApi;
use Movary\Service\ImageUrlService;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PersonDetailController
{
    public function __construct(
        private readonly PersonApi $personApi,
        private readonly ImageUrlService $imageUrlService,
    ) {
    }

    public function getPerson(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['personId']);
        if ($person === null) {
            return Response::createNotFound();
        }

        return Response::createJson(Json::encode([
            'id' => $person->getId(),
            'tmdbId' => $person->getTmdbId(),
            'imdbId' => $person->getImdbId(),
            'name' => $person->getName(),
            'gender' => $person->getGender()->asInt(),
            'knownForDepartment' => $person->getKnownForDepartment(),
            'biography' => $person->getBiography(),
            'birthDate' => $person->getBirthDate()?->jsonSerialize(),
            'deathDate' => $person->getDeathDate()?->jsonSerialize(),
            'placeOfBirth' => $person->getPlaceOfBirth(),
            'posterUrl' => $this->imageUrlService->generateImageSrcUrlFromParameters(
                $person->getTmdbPosterPathEffective(),
                $person->getPosterPath(),
                $person->getName(),
            ),
        ]));
    }
}
