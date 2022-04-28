<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use RuntimeException;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $title, ?int $rating10, ?int $rating5, TraktId $traktId, string $imdbId, int $tmdbId) : Entity
    {
        $this->dbConnection->insert(
            'movie',
            [
                'title' => $title,
                'rating_10' => $rating10,
                'rating_5' => $rating5,
                'trakt_id' => $traktId->asInt(),
                'imdb_id' => $imdbId,
                'tmdb_id' => $tmdbId,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function fetchAll() : EntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `movie`');

        return EntityList::createFromArray($data);
    }

    public function fetchHistoryOrderedByWatchedAtDesc() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.title, m.rating_10, m.rating_5, mh.watched_at
            FROM movie_history mh
            JOIN movie m on mh.movie_id = m.id
            ORDER BY watched_at DESC'
        );
    }

    public function fetchMostWatchedProductionCompany() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT c.id, c.name, COUNT(*) as count, c.origin_country
            FROM movie m
                     JOIN movie_production_company mpc ON m.id = mpc.movie_id
                     JOIN company c ON mpc.company_id = c.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            GROUP BY mpc.company_id
            ORDER BY COUNT(*) DESC, c.name'
        );
    }

    public function fetchMoviesByProductionCompany(int $id) : array
    {

        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.title 
            FROM movie m
            JOIN movie_production_company mpc ON m.id = mpc.movie_id
            WHERE mpc.company_id = ?',
            [$id]
        );
    }

    public function fetchMoviesOrderedByMostWatchedDesc() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.title, COUNT(*) AS views
            FROM movie_history mh
            JOIN movie m on mh.movie_id = m.id
            GROUP BY m.title
            ORDER BY COUNT(*) DESC, m.title'
        );
    }

    public function fetchMostWatchedActors() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT p.name, COUNT(*) as count, p.gender
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh) AND p.name != "Stan Lee"
            GROUP BY mc.person_id
            ORDER BY COUNT(*) DESC, p.name
            LIMIT 1000'
        );
    }

    public function findByLetterboxdId(string $letterboxdId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE letterboxd_id = ?', [$letterboxdId]);

        return $data === false ? null : Entity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE tmdb_id = ?', [$tmdbId]);

        return $data === false ? null : Entity::createFromArray($data);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE trakt_id = ?', [$traktId->asInt()]);

        return $data === false ? null : Entity::createFromArray($data);
    }

    public function updateDetails(
        int $id,
        ?string $tagline,
        ?string $overview,
        ?string $originalLanguage,
        ?DateTime $releaseDate,
        ?int $runtime,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount
    ) : Entity {
        $this->dbConnection->update(
            'movie',
            [
                'tagline' => $tagline,
                'overview' => $overview,
                'original_language' => $originalLanguage,
                'release_date' => $releaseDate === null ? null : Date::createFromDateTime($releaseDate),
                'runtime' => $runtime,
                'tmdb_vote_average' => $tmdbVoteAverage,
                'tmdb_vote_count' => $tmdbVoteCount,
                'updated_at_tmdb' => (string)DateTime::create(),
            ],
            ['id' => $id]
        );

        return $this->fetchById($id);
    }

    public function updateLetterboxdId(int $id, string $letterboxdId) : void
    {
        $this->dbConnection->update('movie', ['letterboxd_id' => $letterboxdId], ['id' => $id]);
    }

    public function updateRating10(int $id, ?int $rating10) : void
    {
        $this->dbConnection->update('movie', ['rating_10' => $rating10], ['id' => $id]);
    }

    public function updateRating5(int $id, ?int $rating5) : void
    {
        $this->dbConnection->update('movie', ['rating_5' => $rating5], ['id' => $id]);
    }

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No movie found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
