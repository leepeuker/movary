<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Service\ImageUrlService;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieListController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly RequestMapper $requestMapper,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly ImageUrlService $imageUrlService,
    ) {
    }

    public function getMovies(Request $request) : Response
    {
        $limit = $this->requestMapper->mapLimit($request);
        $page = $this->requestMapper->mapPage($request);
        $pagination = $this->paginationElementsCalculator->createPaginationElements(
            $this->movieApi->countAll(),
            $limit,
            $page,
        );

        $movies = [];
        foreach ($this->movieApi->fetchAllPaginated($limit, $pagination->getOffset()) as $movie) {
            $movies[] = [
                'id' => $movie->getId(),
                'tmdbId' => $movie->getTmdbId(),
                'title' => $movie->getTitle(),
                'releaseDate' => $movie->getReleaseDate()?->asDate(),
                'posterUrl' => $this->imageUrlService->generateImageSrcUrlFromParameters(
                    $movie->getTmdbPosterPathEffective(),
                    $movie->getPosterPath(),
                    $movie->getTitle(),
                ),
            ];
        }

        return Response::createSensitiveJson(Json::encode([
            'movies' => $movies,
            'currentPage' => $pagination->getCurrentPage(),
            'maxPage' => $pagination->getMaxPage(),
            'totalCount' => $pagination->getTotalCount(),
        ]));
    }
}
