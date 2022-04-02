<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\ValueObject\DateTime;

class SyncMovieDetails
{
    private const SYNC_VALIDITY_TIME_IN_DAYS = 7;

    private Tmdb\Api $api;

    private Genre\Service\Create $genreCreateService;

    private Genre\Service\Select $genreSelectService;

    private Movie\Service\Select $movieSelectService;

    private Movie\Service\Update $movieUpdateService;

    public function __construct(
        Tmdb\Api $api,
        Movie\Service\Select $movieSelectService,
        Movie\Service\Update $movieUpdateService,
        Genre\Service\Select $genreSelectService,
        Genre\Service\Create $genreCreateService
    ) {
        $this->api = $api;
        $this->movieSelectService = $movieSelectService;
        $this->movieUpdateService = $movieUpdateService;
        $this->genreSelectService = $genreSelectService;
        $this->genreCreateService = $genreCreateService;
    }

    public function execute() : void
    {
        $movies = $this->movieSelectService->fetchAll();

        foreach ($movies as $movie) {
            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb) === false) {
                continue;
            }

            $this->updateDetails($movie);
            $this->updateCredits($movie);
        }
    }

    public function getGenres(Tmdb\ValueObject\Movie $movieDetails) : Genre\EntityList
    {
        $genres = Genre\EntityList::create();

        foreach ($movieDetails->getGenres() as $tmdbGenre) {
            $genre = $this->genreSelectService->findByTmdbId($tmdbGenre->getId());

            if ($genre === null) {
                $genre = $this->genreCreateService->create($tmdbGenre->getName(), $tmdbGenre->getId());
            }

            $genres->add($genre);
        }

        return $genres;
    }

    public function updateCredits(Movie\Entity $movie) : void
    {
        $credits = $this->api->getMovieCredits($movie->getTmdbId());

        $this->movieUpdateService->updateCast($movie->getId(), $credits->getCast());
        $this->movieUpdateService->updateCrew($movie->getId(), $credits->getCrew());
    }

    public function updateDetails(Movie\Entity $movie) : void
    {
        $movieDetails = $this->api->getMovieDetails($movie->getTmdbId());

        $this->movieUpdateService->updateDetails(
            $movie->getId(),
            $movieDetails->getOverview(),
            $movieDetails->getOriginalLanguage(),
            $movieDetails->getReleaseDate(),
            $movieDetails->getRuntime(),
            $movieDetails->getVoteAverage(),
            $movieDetails->getVoteCount(),
        );

        $this->movieUpdateService->updateGenres($movie->getId(), $this->getGenres($movieDetails));
    }

    private function syncExpired(DateTime $updatedAtTmdb) : bool
    {
        return $updatedAtTmdb->diff(DateTime::create())->getDays() > self::SYNC_VALIDITY_TIME_IN_DAYS;
    }
}
