<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Service\Netflix\NetflixActivityCsvParser;
use Movary\Service\Netflix\NetflixActivityItemsConverter;
use Movary\Service\Netflix\NetflixMovieImporter;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class NetflixController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TmdbApi $tmdbApi,
        private readonly NetflixActivityCsvParser $netflixCsvParser,
        private readonly NetflixActivityItemsConverter $netflixActivityConverter,
        private readonly NetflixMovieImporter $netflixMovieImporter,
    ) {
    }

    public function importNetflixData(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $items = Json::decode($request->getBody());

        $this->netflixMovieImporter->importWatchDates($userId, $items);

        return Response::create(StatusCode::createOk());
    }

    /**
     * Receives a CSV file with all the Netflix activity history and tries to process this.
     * It filters the movies out with regex patterns, and then compiles an array of all the movie items.
     */
    public function matchNetflixActivityCsvWithTmdbMovies(Request $request) : Response
    {
        $files = $request->getFileParameters();
        $postParameters = $request->getPostParameters();
        if (empty($files['netflixActivityCsv']) === true || empty($postParameters['netflixActivityCsvDateFormat']) === true) {
            return Response::createBadRequest();
        }

        $csv = $files['netflixActivityCsv'];
        if ($csv['size'] == 0) {
            return Response::createBadRequest();
        }

        if ($csv['type'] !== "application/vnd.ms-excel" && $csv['type'] !== 'text/csv') {
            return Response::createUnsupportedMediaType();
        }

        $activityItems = $this->netflixCsvParser->parseNetflixActivityCsv($csv['tmp_name'], $postParameters['netflixActivityCsvDateFormat']);
        $tmdbMatches = $this->netflixActivityConverter->convertActivityItemsToTmdbMatches($activityItems);

        if (count($activityItems) === 0) {
            return Response::createBadRequest();
        }

        return Response::createJson(Json::encode($tmdbMatches));
    }

    public function searchTmbd(Request $request) : Response
    {
        $input = Json::decode($request->getBody());

        // direct input of TMDB URL
        if (preg_match('#themoviedb.org/movie/(\\d+)($|-)#i', $input['query'], $tmdb_ids)) {
            $tmdb_id = intval($tmdb_ids[1]);
            $tmdbSearchResult = [
                "results"=>[
                    $this->tmdbApi->searchID($tmdb_id)
                ]
            ];
        } else { // search as normal
            $tmdbSearchResult = $this->tmdbApi->searchMovie($input['query']);
        }

        return Response::createJson(Json::encode($tmdbSearchResult['results']));
    }
}
