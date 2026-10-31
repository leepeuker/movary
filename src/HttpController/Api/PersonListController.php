<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Person\PersonApi;
use Movary\HttpController\Api\RequestMapper\PersonListRequestMapper;
use Movary\Service\ImageUrlService;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PersonListController
{
    public function __construct(
        private readonly PersonApi $personApi,
        private readonly PersonListRequestMapper $requestMapper,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly ImageUrlService $imageUrlService,
    ) {
    }

    public function getPersons(Request $request) : Response
    {
        $options = $this->requestMapper->mapRequest($request);
        $pagination = $this->paginationElementsCalculator->createPaginationElements(
            $this->personApi->countAll($options['search'], $options['gender'], $options['role']),
            $options['limit'],
            $options['page'],
        );

        $persons = [];
        foreach ($this->personApi->fetchAllPaginated(
            $options['limit'],
            $pagination->getOffset(),
            $options['search'],
            $options['gender'],
            $options['role'],
            $options['sortBy'],
            $options['sortOrder'],
        ) as $person) {
            $persons[] = [
                'id' => $person->getId(),
                'tmdbId' => $person->getTmdbId(),
                'name' => $person->getName(),
                'gender' => $person->getGender()->asInt(),
                'knownForDepartment' => $person->getKnownForDepartment(),
                'birthDate' => $person->getBirthDate()?->jsonSerialize(),
                'posterUrl' => $this->imageUrlService->generateImageSrcUrlFromParameters(
                    $person->getTmdbPosterPathEffective(),
                    $person->getPosterPath(),
                    $person->getName(),
                ),
            ];
        }

        return Response::createSensitiveJson(Json::encode([
            'persons' => $persons,
            'currentPage' => $pagination->getCurrentPage(),
            'maxPage' => $pagination->getMaxPage(),
            'totalCount' => $pagination->getTotalCount(),
        ]));
    }
}
