<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Service\Netflix\ImportNetflixActivity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class NetflixController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly LoggerInterface $logger,
        private readonly TmdbApi $tmdbApi,
        private readonly ImportNetflixActivity $importActivity,
    ) {
    }

    public function importNetflixData(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $items = Json::decode($request->getBody());

        foreach ($items as $item) {
            if (isset($item['watchDate'], $item['tmdbId'], $item['dateFormat']) === false) {
                throw new RuntimeException('Missing parameters');
            }
            $watchDate = Date::createFromStringAndFormat($item['watchDate'], $item['dateFormat']);

            $tmdbId = (int)$item['tmdbId'];
            $personalRating = $item['personalRating'] === 0 ? null : PersonalRating::create((int)$item['personalRating']);

            $movie = $this->movieApi->findByTmdbId($tmdbId);

            if ($movie === null) {
                $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
            }

            $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);

            $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);
            $this->logger->info('Movie has been logged: ' . $tmdbId);
        }

        $this->logger->info('All the movies from Netflix have been imported');

        return Response::create(StatusCode::createOk());
    }

    /**
     * Receives a CSV file with all the Netflix activity history and tries to process this.
     * It filters the movies out with regex patterns, and then compiles an array of all the movie items.
     */
    public function processNetflixActivity(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $files = $request->getFileParameters();
        if (empty($files['netflixviewactivity']) === true) {
            return Response::createBadRequest();
        }

        $csv = $files['netflixviewactivity'];
        if ($csv['size'] == 0) {
            return Response::createBadRequest();
        }

        if ($csv['type'] !== "application/vnd.ms-excel" && $csv['type'] !== 'text/csv') {
            return Response::createUnsupportedMediaType();
        }

        $rows = $this->importActivity->parseNetflixCsv($csv['tmp_name']);

        if (count($rows) === 0) {
            return Response::createBadRequest();
        }

        $tmdbSearchResults = [];
        foreach ($rows as $row) {
            $mediaData = $this->importActivity->extractMediaDataFromTitle($row['Title']);

            if ($mediaData === null || $mediaData['type'] !== 'Movie') {
                continue;
            }

            $movieName = $mediaData['movieName'];

            $search = $this->tmdbApi->searchMovie($movieName);
            $tmdbSearchResults[$movieName] = [
                'result' => $search[0] ?? 'Unknown',
                'date' => date_parse_from_format('d/m/Y', $row['Date']),
                'originalname' => $movieName
            ];

            $this->logger->info('Item is a movie: ' . $movieName);
        }

        return Response::createJson(Json::encode($tmdbSearchResults));
    }

    public function searchTmbd(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $input = Json::decode($request->getBody());

        $tmdbSearchResult = $this->tmdbApi->searchMovie($input['query']);

        return Response::createJson(Json::encode($tmdbSearchResult));
    }
}
