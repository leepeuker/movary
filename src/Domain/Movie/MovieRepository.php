<?php declare(strict_types=1);

namespace Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\Year;
use RuntimeException;

class MovieRepository
{
    public function __construct(
        private readonly Connection $dbConnection,
        private readonly \PDO $pdo,
    ) {
    }

    public function create(
        string $title,
        int $tmdbId,
        ?string $tagline = null,
        ?string $overview = null,
        ?string $originalLanguage = null,
        ?Date $releaseDate = null,
        ?int $runtime = null,
        ?float $tmdbVoteAverage = null,
        ?int $tmdbVoteCount = null,
        ?string $tmdbPosterPath = null,
        ?TraktId $traktId = null,
        ?string $imdbId = null,
    ) : MovieEntity {
        $this->dbConnection->insert(
            'movie',
            [
                'title' => $title,
                'tagline' => $tagline,
                'overview' => $overview,
                'original_language' => $originalLanguage,
                'release_date' => $releaseDate,
                'runtime' => $runtime,
                'tmdb_vote_average' => $tmdbVoteAverage,
                'tmdb_vote_count' => $tmdbVoteCount,
                'tmdb_poster_path' => $tmdbPosterPath,
                'trakt_id' => $traktId?->asInt(),
                'imdb_id' => $imdbId,
                'tmdb_id' => $tmdbId,
            ],
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function deleteAllUserRatings(int $userId) : void
    {
        $this->dbConnection->delete('movie_user_rating', ['user_id' => $userId]);
    }

    public function deleteUserRating(int $movieId, int $userId) : void
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM movie_user_rating WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],

        );
    }

    public function fetchAll() : MovieEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `movie`');

        return MovieEntityList::createFromArray($data);
    }

    public function fetchAllOrderedByLastUpdatedAtImdbAsc(?int $maxAgeInHours = null, ?int $limit = null) : MovieEntityList
    {
        $limitQuery = '';
        if ($limit !== null) {
            $limitQuery = " LIMIT $limit";
        }

        $data = $this->dbConnection->fetchAllAssociative(
            'SELECT * 
                FROM `movie` 
                WHERE updated_at_imdb IS NULL OR updated_at_imdb <= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY updated_at_imdb ASC' . $limitQuery,
            [(int)$maxAgeInHours],
        );

        return MovieEntityList::createFromArray($data);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null) : \Traversable
    {
        $query = 'SELECT * FROM `movie` ORDER BY updated_at_tmdb ASC';

        if ($limit !== null) {
            $query .= ' LIMIT ' . $limit;
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        return $statement->getIterator();
    }

    public function fetchAverageRuntime(int $userId) : float
    {
        return (float)$this->dbConnection->executeQuery(
            'SELECT AVG(runtime)
            FROM movie
            WHERE id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)',
            [$userId],
        )->fetchFirstColumn()[0];
    }

    public function fetchFirstHistoryWatchDate(int $userId) : ?Date
    {
        $stmt = $this->dbConnection->prepare(
            'SELECT watched_at FROM movie_user_watch_dates WHERE user_id = ? ORDER BY watched_at ASC',
        );

        $stmt->bindValue(1, $userId);
        $watchDate = $stmt->executeQuery()->fetchOne();

        if (empty($watchDate) === true) {
            return null;
        }

        return Date::createFromString($watchDate);
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );
    }

    public function fetchHistoryCount(int $userId, ?string $searchTerm = null) : int
    {
        if ($searchTerm !== null) {
            return $this->dbConnection->fetchFirstColumn(
                <<<SQL
                SELECT COUNT(*)
                FROM movie_user_watch_dates mh
                JOIN movie m on mh.movie_id = m.id
                WHERE m.title LIKE ? AND user_id = ?
                SQL,
                ["%$searchTerm%", $userId],
            )[0];
        }

        return $this->dbConnection->fetchFirstColumn(
            'SELECT COUNT(*) FROM movie_user_watch_dates WHERE user_id = ?',
            [$userId],
        )[0];
    }

    public function fetchHistoryOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.*, muwd.watched_at, muwd.plays
            FROM movie_user_watch_dates muwd
            JOIN movie m on muwd.movie_id = m.id
            WHERE muwd.user_id = ?
            ORDER BY watched_at DESC',
            [$userId],
        );
    }

    public function fetchHistoryPaginated(int $userId, int $limit, int $page, ?string $searchTerm) : array
    {
        $payload = [$userId, $userId];

        $offset = ($limit * $page) - $limit;

        $whereQuery = '';
        if ($searchTerm !== null) {
            $payload[] = "%$searchTerm%";
            $whereQuery .= 'WHERE  m.title LIKE ?';
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.*, mh.watched_at, mur.rating as userRating
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id and mur.user_id = ?
            $whereQuery
            ORDER BY watched_at DESC
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchLastPlays(int $userId) : array
    {
        return $this->dbConnection->executeQuery(
            'SELECT m.*, mh.watched_at, mur.rating as user_rating
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id and mur.user_id = ?
            ORDER BY watched_at DESC
            LIMIT 6',
            [$userId, $userId],
        )->fetchAllAssociative();
    }

    public function fetchMostWatchedActors(int $userId, int $page = 1, ?int $limit = null, ?Gender $gender = null, ?string $searchTerm = null) : array
    {
        $payload = [$userId];

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
            SELECT p.id, p.name, COUNT(DISTINCT m.id) as count, p.gender, p.tmdb_poster_path
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd on mc.movie_id = muwd.movie_id
            WHERE muwd.user_id = ? AND p.name != "Stan Lee" {$genderQuery} {$searchTermQuery}
            GROUP BY mc.person_id
            ORDER BY COUNT(DISTINCT m.id) DESC, p.name
            {$limitQuery}
            SQL,
            $payload,
        );
    }

    public function fetchMostWatchedActorsCount(int $userId, ?string $searchTerm) : int
    {
        $payload = [$userId];

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
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?) AND p.name != "Stan Lee" {$searchTermQuery}
            SQL,
            $payload,
        );

        if ($count === false) {
            throw new RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchMostWatchedDirectors(int $userId, int $page = 1, ?int $limit = null, ?string $searchTerm = null) : array
    {
        $limitQuery = '';
        if ($limit !== null) {
            $offset = ($limit * $page) - $limit;
            $limitQuery = "LIMIT $offset, $limit";
        }
        $payload = [$userId];
        $searchTermQuery = '';
        if ($searchTerm !== null) {
            $searchTermQuery = 'AND p.name LIKE ?';
            $payload[] = "%$searchTerm%";
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT p.id, p.name, COUNT(*) as count, p.gender, p.tmdb_poster_path
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?) {$searchTermQuery}
            GROUP BY mc.person_id
            ORDER BY COUNT(*) DESC, p.name
            {$limitQuery}
            SQL,
            $payload,
        );
    }

    public function fetchMostWatchedDirectorsCount(int $userId, ?string $searchTerm = null) : int
    {
        $payload = [$userId];

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
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?) {$searchTermQuery}
            SQL,
            $payload,
        );

        if ($count === false) {
            throw new RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchMostWatchedGenres(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT g.name, COUNT(*) as count
            FROM movie m
            JOIN movie_genre mg ON m.id = mg.movie_id
            JOIN genre g ON mg.genre_id = g.id
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)
            GROUP BY g.name
            ORDER BY COUNT(*) DESC, g.name',
            [$userId],
        );
    }

    public function fetchMostWatchedLanguages(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT original_language AS language, COUNT(*) AS count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?) AND m.original_language IS NOT NULL
            GROUP BY original_language
            ORDER BY COUNT(*) DESC, original_language',
            [$userId],
        );
    }

    public function fetchMostWatchedProductionCompanies(int $userId, ?int $limit = null) : array
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
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)
            GROUP BY mpc.company_id
            ORDER BY COUNT(*) DESC, c.name
            {$limitQuery}
            SQL,
            [$userId],
        );
    }

    public function fetchMostWatchedReleaseYears(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT year(release_date) as name, COUNT(*) as count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)
            GROUP BY year(release_date)
            ORDER BY COUNT(*) DESC, year(release_date) DESC
            SQL,
            [$userId],
        );
    }

    public function fetchMoviesByProductionCompany(int $productionCompanyId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.title 
            FROM movie m
            JOIN movie_production_company mpc ON m.id = mpc.movie_id
            WHERE mpc.company_id = ? AND m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)',
            [$productionCompanyId, $userId],
        );
    }

    public function fetchPersonalRating(int $userId) : float
    {
        return (float)$this->dbConnection->fetchFirstColumn(
            'SELECT AVG(rating)
            FROM movie_user_rating
            WHERE user_id = ?',
            [$userId],
        )[0];
    }

    public function fetchPlaysForMovieIdAtDate(int $movieId, int $userId, Date $watchedAt) : int
    {
        $result = $this->dbConnection->fetchOne(
            'SELECT plays FROM movie_user_watch_dates WHERE movie_id = ? AND watched_at = ? AND user_id = ?',
            [$movieId, $watchedAt, $userId],
        );

        if ($result === false) {
            return 0;
        }

        return $result;
    }

    public function fetchTotalMinutesWatched(int $userId) : int
    {
        return (int)$this->dbConnection->executeQuery(
            'SELECT SUM(m.runtime)
            FROM movie_user_watch_dates mh
            JOIN movie m ON mh.movie_id = m.id
            WHERE mh.user_id = ?',
            [$userId],
        )->fetchFirstColumn()[0];
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT g.name
            FROM movie_user_watch_dates mh
            JOIN movie m on mh.movie_id = m.id
            JOIN movie_genre mg on m.id = mg.movie_id
            JOIN genre g on mg.genre_id = g.id
            WHERE user_id = ?
            ORDER BY g.name
            SQL,
            [$userId],
        );
    }

    public function fetchUniqueMovieInHistoryCount(int $userId, ?string $searchTerm, ?Year $releaseYear, ?string $language, ?string $genre) : int
    {
        $payload = [$userId, "%$searchTerm%"];

        $whereQuery = 'WHERE m.title LIKE ? ';

        if (empty($releaseYear) === false) {
            $whereQuery .= 'AND YEAR(m.release_date) = ? ';
            $payload[] = (string)$releaseYear;
        }

        if (empty($language) === false) {
            $whereQuery .= 'AND m.original_language = ? ';
            $payload[] = $language;
        }

        if (empty($genre) === false) {
            $whereQuery .= 'AND g.name = ? ';
            $payload[] = $genre;
        }

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT COUNT(DISTINCT m.id)
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            JOIN movie_genre mg on m.id = mg.movie_id
            JOIN genre g on mg.genre_id = g.id
            $whereQuery
            SQL,
            $payload,
        )[0];
    }

    public function fetchUniqueMovieLanguages(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
                SELECT DISTINCT m.original_language
                FROM movie_user_watch_dates mh
                JOIN movie m on mh.movie_id = m.id
                WHERE user_id = ?
                ORDER BY original_language DESC
                SQL,
            [$userId],
        );
    }

    public function fetchUniqueMovieReleaseYears(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
                SELECT DISTINCT YEAR(m.release_date)
                FROM movie_user_watch_dates mh
                JOIN movie m on mh.movie_id = m.id
                WHERE user_id = ?
                ORDER BY YEAR(m.release_date) DESC
                SQL,
            [$userId],
        );
    }

    public function fetchUniqueMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        string $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
    ) : array {
        $payload = [$userId, $userId, "%$searchTerm%"];

        $offset = ($limit * $page) - $limit;

        $sortBySanitized = match ($sortBy) {
            'rating' => 'rating',
            'releaseDate' => 'release_date',
            'runtime' => 'runtime',
            default => 'title'
        };

        $whereQuery = 'WHERE m.title LIKE ? ';

        if (empty($releaseYear) === false) {
            $whereQuery .= 'AND YEAR(m.release_date) = ? ';
            $payload[] = (string)$releaseYear;
        }

        if (empty($language) === false) {
            $whereQuery .= 'AND original_language = ? ';
            $payload[] = $language;
        }

        if (empty($genre) === false) {
            $whereQuery .= 'AND g.name = ? ';
            $payload[] = $genre;
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.*, mur.rating as userRating
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id and mur.user_id = ?
            LEFT JOIN movie_genre mg on m.id = mg.movie_id
            LEFT JOIN genre g on mg.genre_id = g.id
            $whereQuery
            GROUP BY m.id, title, release_date, rating
            ORDER BY $sortBySanitized $sortOrder, title asc
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchWithActor(int $personId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT m.*, mur.rating as userRating
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd ON m.id = muwd.movie_id
            LEFT JOIN movie_user_rating mur ON muwd.movie_id = mur.movie_id and mur.user_id = ?
            WHERE p.id = ? AND m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh) AND muwd.user_id = ?
            ORDER BY m.title
            SQL,
            [$userId, $personId, $userId],
        );
    }

    public function fetchWithDirector(int $personId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT m.*, mur.rating as userRating
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd ON m.id = muwd.movie_id and muwd.user_id = ?
            LEFT JOIN movie_user_rating mur ON muwd.movie_id = mur.movie_id and mur.user_id = ?
            WHERE p.id = ? AND m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh)
            ORDER BY m.title
            SQL,
            [$userId, $userId, $personId],
        );
    }

    public function findById(int $movieId) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE id = ?', [$movieId]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findByLetterboxdId(string $letterboxdId) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE letterboxd_id = ?', [$letterboxdId]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE tmdb_id = ?', [$tmdbId]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findByTraktId(TraktId $traktId) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE trakt_id = ?', [$traktId->asInt()]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findPlaysForMovieIdAndDate(int $movieId, int $userId, Date $watchedAt) : ?int
    {
        $result = $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT plays
            FROM movie_user_watch_dates
            WHERE movie_id = ? AND watched_at = ? AND user_id = ?
            SQL,
            [$movieId, $watchedAt, $userId],
        );

        return $result[0] ?? null;
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        $userRating = $this->dbConnection->fetchFirstColumn(
            'SELECT rating FROM `movie_user_rating` WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        )[0] ?? null;

        return $userRating !== null ? PersonalRating::create($userRating) : null;
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
        ?string $tmdbPosterPath,
        ?string $imdbId,
    ) : MovieEntity {
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
                'tmdb_poster_path' => $tmdbPosterPath,
                'updated_at_tmdb' => (string)DateTime::create(),
                'imdb_id' => $imdbId,
            ],
            ['id' => $id],
        );

        return $this->fetchById($id);
    }

    public function updateImdbRating(int $id, ?float $imdbRating, ?int $imdbRatingVoteCount) : void
    {
        $this->dbConnection->update('movie', [
            'imdb_rating_average' => $imdbRating,
            'imdb_rating_vote_count' => $imdbRatingVoteCount,
            'updated_at_imdb' => (string)DateTime::create(),
        ], ['id' => $id]);
    }

    public function updateLetterboxdId(int $id, string $letterboxdId) : void
    {
        $this->dbConnection->update('movie', ['letterboxd_id' => $letterboxdId], ['id' => $id]);
    }

    public function updateTraktId(int $id, TraktId $traktId) : void
    {
        $this->dbConnection->update('movie', ['trakt_id' => $traktId->asInt()], ['id' => $id]);
    }

    public function updateUserRating(int $id, int $userId, PersonalRating $personalRating) : void
    {
        $this->dbConnection->executeQuery(
            'INSERT INTO movie_user_rating (movie_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating=?',
            [$id, $userId, $personalRating, $personalRating],
        );
    }

    private function fetchById(int $id) : MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No movie found by id: ' . $id);
        }

        return MovieEntity::createFromArray($data);
    }
}
