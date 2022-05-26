<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
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

    public function fetchAllOrderedByLastUpdatedAtTmdbDesc() : EntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `movie` ORDER BY updated_at_tmdb ASC');

        return EntityList::createFromArray($data);
    }

    public function fetchAverage10Rating() : float
    {
        return (float)$this->dbConnection->fetchFirstColumn(
            'SELECT AVG(rating_10)
            FROM movie
            WHERE id IN (SELECT DISTINCT movie_id FROM movie_history mh)'
        )[0];
    }

    public function fetchAverageRuntime() : float
    {
        return (float)$this->dbConnection->fetchFirstColumn(
            'SELECT AVG(runtime)
            FROM movie
            WHERE id IN (SELECT DISTINCT movie_id FROM movie_history mh)'
        )[0];
    }

    public function fetchFirstHistoryWatchDate() : ?Date
    {
        $data = $this->dbConnection->fetchFirstColumn(
            'SELECT watched_at FROM movie_history ORDER BY watched_at ASC'
        );

        if (empty($data[0]) === true) {
            return null;
        }

        return Date::createFromString($data[0]);
    }

    public function fetchHistoryCount(?string $searchTerm = null) : int
    {
        if ($searchTerm !== null) {
            return $this->dbConnection->fetchFirstColumn(
                <<<SQL
                SELECT COUNT(*)
                FROM movie_history mh
                JOIN movie m on mh.movie_id = m.id
                WHERE m.title LIKE "%$searchTerm%"
                SQL
            )[0];
        }

        return $this->dbConnection->fetchFirstColumn(
            'SELECT COUNT(*) FROM movie_history'
        )[0];
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

    public function fetchHistoryPaginated(int $limit, int $page, ?string $searchTerm) : array
    {
        $payload = [];
        $offset = ($limit * $page) - $limit;

        $whereQuery = '';
        if ($searchTerm !== null) {
            $payload[] = "%$searchTerm%";
            $whereQuery = 'WHERE m.title LIKE ?';
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.title, YEAR(m.release_date) as year , m.rating_10, mh.watched_at, m.poster_path
            FROM movie_history mh
            JOIN movie m on mh.movie_id = m.id
            $whereQuery
            ORDER BY watched_at DESC
            LIMIT $offset, $limit
            SQL,
            $payload
        );
    }

    public function fetchLastPlays() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.title, YEAR(m.release_date) as year , m.rating_10, mh.watched_at, m.poster_path
            FROM movie_history mh
            JOIN movie m on mh.movie_id = m.id
            ORDER BY watched_at DESC
            LIMIT 6'
        );
    }

    public function fetchMostWatchedActors(int $page = 1, ?int $limit = null, ?Gender $gender = null, ?string $searchTerm = null) : array
    {
        $payload = [];

        $limitQuery = '';
        if ($limit !== null) {
            $offset = ($limit * $page) - $limit;
            $limitQuery = "LIMIT $offset, $limit";
        }
        $genderQuery = '';
        if ($gender !== null) {
            $genderQuery = 'AND p.gender = ?';
            $payload[] = $gender;
        }
        $searchTermQuery = '';
        if ($searchTerm !== null) {
            $searchTermQuery = 'AND p.name LIKE ?';
            $payload[] = "%$searchTerm%";
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT p.id, p.name, COUNT(*) as count, p.gender, p.poster_path
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh) AND p.name != "Stan Lee" {$genderQuery} {$searchTermQuery}
            GROUP BY mc.person_id
            ORDER BY COUNT(*) DESC, p.name
            {$limitQuery}
            SQL,
            $payload
        );
    }

    public function fetchMostWatchedActorsCount(?string $searchTerm) : int
    {
        $payload = [];
        $searchTermQuery = '';
        if ($searchTerm !== null) {
            $searchTermQuery = 'AND p.name LIKE ?';
            $payload[] = "%$searchTerm%";
        }

        $count = $this->dbConnection->fetchOne(
            <<<SQL
            SELECT COUNT(DISTINCT p.id)
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh) AND p.name != "Stan Lee" {$searchTermQuery}
            SQL,
            $payload
        );

        if ($count === false) {
            throw new \RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchMostWatchedDirectors(int $page = 1, ?int $limit = null, ?string $searchTerm = null) : array
    {
        $limitQuery = '';
        if ($limit !== null) {
            $offset = ($limit * $page) - $limit;
            $limitQuery = "LIMIT $offset, $limit";
        }
        $payload = [];
        $searchTermQuery = '';
        if ($searchTerm !== null) {
            $searchTermQuery = 'AND p.name LIKE ?';
            $payload[] = "%$searchTerm%";
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT p.id, p.name, COUNT(*) as count, p.gender, p.poster_path
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh) {$searchTermQuery}
            GROUP BY mc.person_id
            ORDER BY COUNT(*) DESC, p.name
            {$limitQuery}
            SQL,
            $payload
        );
    }

    public function fetchMostWatchedDirectorsCount(?string $searchTerm = null) : int
    {
        $payload = [];
        $searchTermQuery = '';
        if ($searchTerm !== null) {
            $searchTermQuery = 'AND p.name LIKE ?';
            $payload[] = "%$searchTerm%";
        }

        $count = $this->dbConnection->fetchOne(
            <<<SQL
            SELECT COUNT(DISTINCT p.id)
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh) {$searchTermQuery}
            SQL,
            $payload
        );

        if ($count === false) {
            throw new \RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchMostWatchedGenres() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT g.name, COUNT(*) as count
            FROM movie m
            JOIN movie_genre mg ON m.id = mg.movie_id
            JOIN genre g ON mg.genre_id = g.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            GROUP BY g.name
            ORDER BY COUNT(*) DESC, g.name'
        );
    }

    public function fetchMostWatchedLanguages() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT original_language AS language, COUNT(*) AS count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            GROUP BY original_language
            ORDER BY COUNT(*) DESC, original_language'
        );
    }

    public function fetchMostWatchedProductionCompanies(?int $limit = null) : array
    {
        $limitQuery = '';
        if ($limit !== null) {
            $limitQuery = 'LIMIT ' . $limit;
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT c.id, c.name, COUNT(*) as count, c.origin_country
            FROM movie m
                     JOIN movie_production_company mpc ON m.id = mpc.movie_id
                     JOIN company c ON mpc.company_id = c.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            GROUP BY mpc.company_id
            ORDER BY COUNT(*) DESC, c.name
            {$limitQuery}
            SQL
        );
    }

    public function fetchMostWatchedReleaseYears() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT year(release_date) as name, COUNT(*) as count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_history mh)
            GROUP BY year(release_date)
            ORDER BY COUNT(*) DESC, year(release_date)
            SQL
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

    public function fetchPlaysForMovieIdAtDate(int $movieId, Date $watchedAt) : int
    {
        $result = $this->dbConnection->fetchOne(
            'SELECT plays FROM movie_history WHERE movie_id = ? AND watched_at = ?',
            [$movieId, $watchedAt]
        );

        if ($result === false) {
            return 0;
        }

        return $result;
    }

    public function fetchTotalMinutesWatched() : int
    {
        return (int)$this->dbConnection->fetchFirstColumn(
            'SELECT SUM(m.runtime)
            FROM movie_history mh
            JOIN movie m ON mh.movie_id = m.id'
        )[0];
    }

    public function fetchUniqueMovieInHistoryCount() : int
    {
        return $this->dbConnection->fetchFirstColumn(
            'SELECT COUNT(DISTINCT movie_id) FROM movie_history'
        )[0];
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
        ?int $tmdbVoteCount,
        ?string $posterPath,
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
                'poster_path' => $posterPath,
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
