<?php declare(strict_types=1);

namespace Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use Movary\ValueObject\ImdbRating;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;
use Traversable;

class MovieRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
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
        ?string $tmdbBackdropPath = null,
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
                'tmdb_backdrop_path' => $tmdbBackdropPath,
                'trakt_id' => $traktId?->asInt(),
                'imdb_id' => $imdbId,
                'tmdb_id' => $tmdbId,
                'created_at' => (string)DateTime::create(),
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

    public function fetchActors(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Gender $gender,
        ?int $personFilterUserId,
    ) : array {
        $payload = [$userId];

        $personFilterJoin = '';
        if ($personFilterUserId !== null) {
            $personFilterJoin = 'LEFT JOIN user_person_settings ups ON ups.person_id = p.id AND ups.user_id = ?';
            $payload[] = $personFilterUserId;
        }

        $payload[] = "%$searchTerm%";

        $offset = ($limit * $page) - $limit;

        $sortBySanitized = match ($sortBy) {
            'uniqueAppearances' => 'COUNT(DISTINCT m.id) ',
            'totalAppearances' => 'COUNT(m.id) ',
            default => 'name'
        };

        $whereQuery = 'WHERE p.name LIKE ? ';

        if (empty($gender) === false) {
            $whereQuery .= 'AND p.gender = ? ';
            $payload[] = $gender;
        }
        if ($personFilterUserId !== null) {
            $whereQuery .= 'AND ups.is_hidden_in_top_lists IS NULL OR ups.is_hidden_in_top_lists != 1';
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT p.id, p.name, COUNT(DISTINCT m.id) as uniqueCount, COUNT(m.id) as totalCount, p.gender, p.tmdb_poster_path, p.poster_path
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd on mc.movie_id = muwd.movie_id and muwd.user_id = ?
            $personFilterJoin
            $whereQuery
            GROUP BY mc.person_id, name
            ORDER BY $sortBySanitized $sortOrder, name asc
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchActorsCount(int $userId, ?string $searchTerm, ?Gender $gender = null, ?int $personFilterUserId = null) : int
    {
        $payload = [$userId];

        $personFilterJoin = '';
        if ($personFilterUserId !== null) {
            $personFilterJoin = 'LEFT JOIN user_person_settings ups ON ups.person_id = p.id AND ups.user_id = ?';
            $payload[] = $personFilterUserId;
        }

        $payload[] = "%$searchTerm%";

        $whereQuery = 'WHERE p.name LIKE ? ';
        if (empty($gender) === false) {
            $whereQuery .= 'AND p.gender = ? ';
            $payload[] = $gender;
        }
        if ($personFilterUserId !== null) {
            $whereQuery .= 'AND ups.is_hidden_in_top_lists IS NULL OR ups.is_hidden_in_top_lists != 1';
        }

        $count = $this->dbConnection->fetchOne(
            <<<SQL
            SELECT COUNT(DISTINCT p.id)
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd on mc.movie_id = muwd.movie_id and muwd.user_id = ?
            $personFilterJoin
            $whereQuery
            SQL,
            $payload,
        );

        if ($count === false) {
            throw new RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchAll() : MovieEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `movie`');

        return MovieEntityList::createFromArray($data);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null, ?array $ids = null) : Traversable
    {
        $whereQuery = '';
        if ($ids !== null && count($ids) > 0) {
            $placeholders = str_repeat('?, ', count($ids));
            $whereQuery = ' WHERE id IN (' . trim($placeholders, ', ') . ')';
        }

        $query = "SELECT * FROM `movie` $whereQuery ORDER BY updated_at_tmdb, created_at";

        if ($limit !== null) {
            $query .= ' LIMIT ' . $limit;
        }

        return $this->dbConnection->prepare($query)->executeQuery($ids ?? [])->iterateAssociative();
    }

    public function fetchAveragePersonalRating(int $userId) : float
    {
        return (float)$this->dbConnection->fetchFirstColumn(
            'SELECT AVG(rating)
            FROM movie_user_rating
            WHERE user_id = ?',
            [$userId],
        )[0];
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

    public function fetchDirectors(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Gender $gender,
        ?int $personFilterUserId,
    ) : array {
        $payload = [$userId];

        $personFilterJoin = '';
        if ($personFilterUserId !== null) {
            $personFilterJoin = 'LEFT JOIN user_person_settings ups ON ups.person_id = p.id AND ups.user_id = ?';
            $payload[] = $personFilterUserId;
        }

        $payload[] = "%$searchTerm%";

        $offset = ($limit * $page) - $limit;

        $sortBySanitized = match ($sortBy) {
            'uniqueAppearances' => 'COUNT(DISTINCT m.id) ',
            'totalAppearances' => 'COUNT(m.id) ',
            default => 'name'
        };

        $whereQuery = 'WHERE p.name LIKE ? ';

        if (empty($gender) === false) {
            $whereQuery .= 'AND p.gender = ? ';
            $payload[] = $gender;
        }
        if ($personFilterUserId !== null) {
            $whereQuery .= 'AND ups.is_hidden_in_top_lists IS NULL OR ups.is_hidden_in_top_lists != 1';
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT p.id, p.name, COUNT(DISTINCT m.id) as uniqueCount, COUNT(m.id) as totalCount, p.gender, p.tmdb_poster_path, p.poster_path
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd on mc.movie_id = muwd.movie_id and muwd.user_id = ?
            $personFilterJoin
            $whereQuery
            GROUP BY mc.person_id, name
            ORDER BY $sortBySanitized $sortOrder, name asc
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchDirectorsCount(int $userId, ?string $searchTerm = null, ?Gender $gender = null, ?int $personFilterUserId = null) : int
    {
        $payload = [$userId];

        $personFilterJoin = '';
        if ($personFilterUserId !== null) {
            $personFilterJoin = 'LEFT JOIN user_person_settings ups ON ups.person_id = p.id AND ups.user_id = ?';
            $payload[] = $personFilterUserId;
        }

        $payload[] = "%$searchTerm%";

        $whereQuery = 'WHERE p.name LIKE ?';

        if (empty($gender) === false) {
            $whereQuery .= 'AND p.gender = ? ';
            $payload[] = $gender;
        }
        if ($personFilterUserId !== null) {
            $whereQuery .= 'AND ups.is_hidden_in_top_lists IS NULL OR ups.is_hidden_in_top_lists != 1';
        }

        $count = $this->dbConnection->fetchOne(
            <<<SQL
            SELECT COUNT(DISTINCT p.id)
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            JOIN movie_user_watch_dates muwd on mc.movie_id = muwd.movie_id and muwd.user_id = ?
            $personFilterJoin
            $whereQuery
            SQL,
            $payload,
        );

        if ($count === false) {
            throw new RuntimeException('Could not execute query.');
        }

        return (int)$count;
    }

    public function fetchFirstHistoryWatchDate(int $userId) : ?Date
    {
        $stmt = $this->dbConnection->prepare(
            'SELECT watched_at FROM movie_user_watch_dates WHERE user_id = ? AND watched_at IS NOT NULL ORDER BY watched_at ASC',
        );

        $stmt->bindValue(1, $userId);
        $watchDate = $stmt->executeQuery()->fetchOne();

        if (empty($watchDate) === true) {
            return null;
        }

        return Date::createFromString($watchDate);
    }

    public function fetchFromWatchlistWithActor(int $personId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT m.*, mur.rating as userRating
            FROM movie m
            JOIN movie_cast mc ON m.id = mc.movie_id
            JOIN person p ON mc.person_id = p.id
            JOIN watchlist wl ON m.id = wl.movie_id
            LEFT JOIN movie_user_rating mur ON wl.movie_id = mur.movie_id and mur.user_id = ?
            WHERE p.id = ? AND m.id IN (wl.movie_id) AND wl.user_id = ?
            ORDER BY LOWER(m.title)
            SQL,
            [$userId, $personId, $userId],
        );
    }

    public function fetchFromWatchlistWithDirector(int $personId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT m.*, mur.rating as userRating
            FROM movie m
            JOIN movie_crew mc ON m.id = mc.movie_id AND job = "Director"
            JOIN person p ON mc.person_id = p.id
            JOIN watchlist wl ON m.id = wl.movie_id and wl.user_id = ?
            LEFT JOIN movie_user_rating mur ON wl.movie_id = mur.movie_id and mur.user_id = ?
            WHERE p.id = ? AND m.id IN (wl.movie_id)
            ORDER BY LOWER(m.title)
            SQL,
            [$userId, $userId, $personId],
        );
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ? ORDER BY watched_at',
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
                JOIN movie m on mh.movie_id = m.id AND watched_at IS NOT NULL
                WHERE m.title LIKE ? AND user_id = ?
                SQL,
                ["%$searchTerm%", $userId],
            )[0];
        }

        return $this->dbConnection->fetchFirstColumn(
            'SELECT COUNT(*) FROM movie_user_watch_dates JOIN movie m on movie_id = m.id AND watched_at IS NOT NULL WHERE user_id = ?',
            [$userId],
        )[0];
    }

    public function fetchHistoryPaginated(int $userId, int $limit, int $page, SortOrder $sortOrder, ?string $searchTerm) : array
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
            SELECT m.*, mh.watched_at, mur.rating as userRating, mh.location_id as locationId
            FROM movie m
            JOIN movie_user_watch_dates mh ON mh.movie_id = m.id AND mh.user_id = ? AND mh.watched_at IS NOT NULL
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id AND mur.user_id = ?
            $whereQuery
            ORDER BY watched_at $sortOrder, mh.position $sortOrder
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchLastPlays(int $userId) : array
    {
        return $this->dbConnection->executeQuery(
            'SELECT m.*, mh.watched_at, mur.rating AS user_rating
            FROM movie m
            JOIN movie_user_watch_dates mh ON mh.movie_id = m.id AND mh.user_id = ? AND mh.watched_at IS NOT NULL
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id AND mur.user_id = ?
            ORDER BY watched_at DESC, mh.position DESC
            LIMIT 6',
            [$userId, $userId],
        )->fetchAllAssociative();
    }

    public function fetchLastPlaysCinema(int $userId) : array
    {
        return $this->dbConnection->executeQuery(
            'SELECT m.*, mh.watched_at, mur.rating AS user_rating
            FROM movie m
            JOIN movie_user_watch_dates mh ON mh.movie_id = m.id AND mh.user_id = ? AND mh.watched_at IS NOT NULL
            LEFT JOIN movie_user_rating mur ON mh.movie_id = mur.movie_id AND mur.user_id = ?
            JOIN location l on l.id = mh.location_id
            WHERE l.is_cinema = 1
            ORDER BY watched_at DESC, mh.position DESC
            LIMIT 6',
            [$userId, $userId],
        )->fetchAllAssociative();
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
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            return $this->dbConnection->fetchAllAssociative(
                <<<SQL
            SELECT strftime('%Y',release_date) as name, COUNT(*) as count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?) AND release_date IS NOT NULL
            GROUP BY strftime('%Y',release_date)
            ORDER BY COUNT(*) DESC, strftime('%Y',release_date) DESC
            SQL,
                [$userId],
            );
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT YEAR(release_date) as name, COUNT(*) as count
            FROM movie m
            WHERE m.id IN (SELECT DISTINCT movie_id FROM movie_user_watch_dates mh WHERE user_id = ?)
            GROUP BY YEAR(release_date)
            ORDER BY COUNT(*) DESC, YEAR(release_date) DESC
            SQL,
            [$userId],
        );
    }

    public function fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt(
        ?int $maxAgeInHours = null,
        ?int $limit = null,
        ?array $filterMovieIds = null,
        bool $onlyNeverSynced = false,
    ) : array {
        $limitQuery = '';
        if ($limit !== null) {
            $limitQuery = "LIMIT $limit";
        }

        $filterMovieIdsQuery = '';
        if ($filterMovieIds !== null) {
            $filterMovieIdsQuery = ' AND movie.id IN (' . implode(',', $filterMovieIds) . ')';
        }

        $syncedFilter = '';
        if ($onlyNeverSynced === false) {
            $syncedFilter = match ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
                true => 'OR updated_at_imdb <= datetime("now","-' . (int)$maxAgeInHours . ' hours")',
                false => 'OR updated_at_imdb <= DATE_SUB(NOW(), INTERVAL ' . (int)$maxAgeInHours . ' HOUR)',
            };
        }

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT movie.id
            FROM `movie` 
            WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL $syncedFilter) $filterMovieIdsQuery
            ORDER BY updated_at_imdb ASC $limitQuery
            SQL,
        );
    }

    public function fetchMovieIdsWithWatchDatesByUserId(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT movie_id
            FROM movie_user_watch_dates
            WHERE user_id = ?
            GROUP by movie_id
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

    public function fetchTmdbIdsToLastWatchDatesMap(int $userId, array $tmdbIds) : array
    {
        if (count($tmdbIds) === 0) {
            return [];
        }

        $placeholders = trim(str_repeat('?, ', count($tmdbIds)), ', ');

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT tmdb_id, MAX(watched_at) as latest_watched_at
            FROM movie_user_watch_dates
            JOIN movie m on m.id = movie_user_watch_dates.movie_id
            WHERE user_id = ? AND tmdb_id IN ($placeholders)
            GROUP by tmdb_id
            SQL,
            [
                $userId,
                ...$tmdbIds
            ],
        );
    }

    public function fetchTmdbIdsWithWatchDatesByUserIdAndMovieIds(int $userId, array $movieIds) : array
    {
        if (count($movieIds) === 0) {
            return [];
        }

        $placeholders = trim(str_repeat('?, ', count($movieIds)), ', ');

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT tmdb_id
            FROM movie_user_watch_dates
            JOIN movie m on movie_user_watch_dates.movie_id = m.id
            WHERE user_id = ? AND movie_id IN ($placeholders)
            SQL,
            [
                $userId,
                ...$movieIds,
            ],
        );
    }

    public function fetchTmdbIdsWithoutWatchDateByUserId(int $userId, array $movieIds) : array
    {
        if (count($movieIds) === 0) {
            return [];
        }

        $placeholders = trim(str_repeat('?, ', count($movieIds)), ', ');

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT tmdb_id
            FROM movie
            WHERE id IN ($placeholders) AND id NOT IN (
                SELECT DISTINCT id
                FROM movie_user_watch_dates
                JOIN movie m on movie_user_watch_dates.movie_id = m.id
                WHERE user_id = ? AND movie_id IN ($placeholders)
            )
            SQL,
            [
                ...$movieIds,
                $userId,
                ...$movieIds,
            ],
        );
    }

    public function fetchTopLocations(int $userId) : array
    {
        return $this->dbConnection->executeQuery(
            'SELECT location_id as id, name, COUNT(muwd.movie_id) AS count_plays
            FROM location
            JOIN movie_user_watch_dates muwd on location.id = muwd.location_id
            WHERE location.user_id = ?
            GROUP BY location_id
            ORDER BY count_plays DESC',
            [$userId],
        )->fetchAllAssociative();
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

    public function fetchTotalPlayCount(int $userId) : int
    {
        return (int)$this->dbConnection->fetchFirstColumn(
            'SELECT SUM(plays) FROM movie_user_watch_dates JOIN movie m on movie_id = m.id WHERE user_id = ?',
            [$userId],
        )[0];
    }

    public function fetchTotalPlayCountUnique(int $userId) : int
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT COUNT(DISTINCT m.id)
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            SQL,
            [$userId],
        )[0];
    }

    public function fetchTotalPlaysForMovieAndUserId(int $movieId, int $userId) : int
    {
        $result = $this->dbConnection->fetchOne(
            'SELECT SUM(plays) FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );

        if (empty($result) === true) {
            return 0;
        }

        return (int)$result;
    }

    public function fetchUniqueActorGenders(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT p.gender
            FROM movie_user_watch_dates mh
            JOIN movie m on mh.movie_id = m.id
            JOIN movie_cast mc on m.id = mc.movie_id
            JOIN person p on mc.person_id = p.id
            WHERE user_id = ?
            ORDER BY p.gender
            SQL,
            [$userId],
        );
    }

    public function fetchUniqueDirectorsGenders(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT p.gender
            FROM movie_user_watch_dates mh
            JOIN movie m on mh.movie_id = m.id
            JOIN movie_crew mc on m.id = mc.movie_id AND mc.job = "Director"
            JOIN person p on mc.person_id = p.id
            WHERE user_id = ?
            ORDER BY p.gender
            SQL,
            [$userId],
        );
    }

    public function fetchUniqueLocations(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT l.name, l.id
            FROM movie_user_watch_dates mh
            JOIN movie m on mh.movie_id = m.id
            JOIN location l on l.id = mh.location_id
            WHERE mh.user_id = ?
            ORDER BY l.name
            SQL,
            [$userId],
        );
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
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            return $this->dbConnection->fetchFirstColumn(
                <<<SQL
                SELECT DISTINCT strftime('%Y',release_date)
                FROM movie_user_watch_dates mh
                JOIN movie m on mh.movie_id = m.id
                WHERE user_id = ?
                ORDER BY strftime('%Y',release_date) DESC
                SQL,
                [$userId],
            );
        }

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

    public function fetchUniqueWatchedMoviesCount(
        int $userId,
        ?string $searchTerm,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
        ?int $locationId,
    ) : int {
        $payload = [$userId, $userId, "%$searchTerm%"];

        $whereQuery = 'WHERE m.title LIKE ? ';

        if (empty($releaseYear) === false) {
            if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
                $whereQuery .= 'AND strftime(\'%Y\', m.release_date) = ? ';
            } else {
                $whereQuery .= 'AND YEAR(m.release_date) = ? ';
            }
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

        if ($hasUserRating === false) {
            $whereQuery .= 'AND mur.rating IS NULL ';
        }
        if ($hasUserRating === true) {
            $whereQuery .= 'AND mur.rating BETWEEN ? AND ? ';
            $payload[] = $userRatingMin;
            $payload[] = $userRatingMax;
        }

        if ($locationId !== null) {
            $whereQuery .= 'AND mh.location_id = ? ';
            $payload[] = $locationId;
        }

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT COUNT(DISTINCT m.id)
            FROM movie m
            JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
            LEFT JOIN movie_user_rating mur on mh.movie_id = mur.movie_id and mh.user_id = ?
            LEFT JOIN movie_genre mg on m.id = mg.movie_id
            LEFT JOIN genre g on mg.genre_id = g.id
            $whereQuery
            SQL,
            $payload,
        )[0];
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function fetchUniqueWatchedMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
        ?int $locationId,
    ) : array {
        $payload = [$userId, $userId, "%$searchTerm%"];

        $offset = ($limit * $page) - $limit;

        $sortBySanitized = match ($sortBy) {
            'rating' => 'rating',
            'releaseDate' => 'release_date',
            'watchDate' => 'watched_at',
            'runtime' => 'runtime',
            default => 'LOWER(title)'
        };

        $sortByWatchDatePosition = '';
        if ($sortBySanitized === 'watched_at') {
            $sortByWatchDatePosition = "mh.position $sortOrder, ";
        }

        $whereQuery = 'WHERE m.title LIKE ? ';

        if (empty($releaseYear) === false) {
            if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
                $whereQuery .= 'AND strftime("%Y",m.release_date) = ? ';
            } else {
                $whereQuery .= 'AND YEAR(m.release_date) = ? ';
            }

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

        if ($hasUserRating === false) {
            $whereQuery .= 'AND mur.rating IS NULL ';
        }
        if ($hasUserRating === true) {
            $whereQuery .= 'AND mur.rating BETWEEN ? AND ? ';
            $payload[] = $userRatingMin;
            $payload[] = $userRatingMax;
        }

        if ($locationId !== null) {
            $whereQuery .= 'AND mh.location_id = ? ';
            $payload[] = $locationId;
        }

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT * FROM (
                SELECT m.*, mur.rating as userRating, ROW_NUMBER() OVER(PARTITION BY m.id) rn
                FROM movie m
                JOIN movie_user_watch_dates mh on mh.movie_id = m.id and mh.user_id = ?
                LEFT JOIN movie_user_rating mur on mh.movie_id = mur.movie_id and mh.user_id = ?
                LEFT JOIN movie_genre mg on m.id = mg.movie_id
                LEFT JOIN genre g on mg.genre_id = g.id
                $whereQuery
                GROUP BY m.id, title, release_date, watched_at, rating
                ORDER BY $sortBySanitized $sortOrder,$sortByWatchDatePosition LOWER(title) asc
            ) a
            WHERE rn = 1
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function fetchWatchDatesForMovieIds(int $userId, array $movieIds) : array
    {
        $placeholders = trim(str_repeat('?, ', count($movieIds)), ', ');

        return $this->dbConnection->fetchAllAssociative(
            "SELECT watched_at, plays, comment, movie_id
            FROM movie_user_watch_dates
            WHERE user_id = ? and movie_id in ($placeholders)
            ORDER BY watched_at DESC, position DESC",
            [
                $userId,
                ...$movieIds
            ],
        );
    }

    public function fetchWatchDatesOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT m.*, muwd.watched_at, muwd.plays, comment, l.name as location_name
            FROM movie_user_watch_dates muwd
            JOIN movie m on muwd.movie_id = m.id
            LEFT JOIN location l on muwd.location_id = l.id
            WHERE muwd.user_id = ?
            ORDER BY watched_at DESC, muwd.position DESC',
            [$userId],
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
            ORDER BY LOWER(m.title)
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
            ORDER BY LOWER(m.title)
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

    public function findByTitleAndYear(string $title, Year $releaseYear) : ?MovieEntity
    {
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE title = ? AND strftime(\'%Y\',release_date) = ?', [$title, $releaseYear]);
        } else {
            $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE title = ? AND YEAR(release_date) = ?', [$title, $releaseYear]);
        }

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findByTmdbId(int $movieIds) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE tmdb_id = ?', [$movieIds]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findByTmdbIds(array $tmdbIds) : array
    {
        if (count($tmdbIds) === 0) {
            return [];
        }

        $placeholders = trim(str_repeat('?, ', count($tmdbIds)), ', ');

        return $this->dbConnection->fetchAllAssociative("SELECT * FROM `movie` WHERE tmdb_id IN ($placeholders)", [...$tmdbIds]);
    }

    public function findByTraktId(TraktId $traktId) : ?MovieEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `movie` WHERE trakt_id = ?', [$traktId->asInt()]);

        return $data === false ? null : MovieEntity::createFromArray($data);
    }

    public function findHistoryEntryForMovieByUserOnDate(int $movieId, int $userId, ?Date $watchedAt) : ?MovieHistoryEntity
    {
        if ($watchedAt === null) {
            $result = $this->dbConnection->fetchAssociative(
                'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ? AND watched_at IS NULL',
                [$movieId, $userId],
            );
        } else {
            $result = $this->dbConnection->fetchAssociative(
                'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND watched_at = ? AND user_id = ?',
                [$movieId, $watchedAt, $userId],
            );
        }

        if ($result === false) {
            return null;
        }

        return MovieHistoryEntity::createFromArray($result);
    }

    public function findPersonalMovieRating(int $movieId, int $userId) : ?PersonalRating
    {
        $data = $this->dbConnection->fetchOne(
            'SELECT rating FROM `movie_user_rating` WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );

        return $data === false ? null : PersonalRating::create($data);
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        $userRating = $this->dbConnection->fetchFirstColumn(
            'SELECT rating FROM `movie_user_rating` WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        )[0] ?? null;

        return $userRating !== null ? PersonalRating::create($userRating) : null;
    }

    public function insertUserRating(int $movieId, int $userId, PersonalRating $rating) : void
    {
        $this->dbConnection->executeQuery(
            'INSERT INTO movie_user_rating (movie_id, user_id, rating, created_at) VALUES (?, ?, ?, ?)',
            [$movieId, $userId, $rating->asInt(), (string)DateTime::create()],
        );
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
        ?string $tmdbBackdropPath,
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
                'tmdb_backdrop_path' => $tmdbBackdropPath,
                'updated_at_tmdb' => (string)DateTime::create(),
                'imdb_id' => $imdbId,
                'updated_at' => (string)DateTime::create(),
            ],
            ['id' => $id],
        );

        return $this->fetchById($id);
    }

    public function updateImdbRating(int $id, ?ImdbRating $imdbRating) : void
    {
        $this->dbConnection->update('movie', [
            'imdb_rating_average' => $imdbRating?->getRating(),
            'imdb_rating_vote_count' => $imdbRating?->getVotesCount(),
            'updated_at_imdb' => (string)DateTime::create(),
            'updated_at' => (string)DateTime::create(),
        ], ['id' => $id]);
    }

    public function updateImdbTimestamp(int $id) : void
    {
        $this->dbConnection->update('movie', [
            'updated_at_imdb' => (string)DateTime::create(),
        ], ['id' => $id]);
    }

    public function updateLetterboxdId(int $id, string $letterboxdId) : void
    {
        $this->dbConnection->update('movie', ['letterboxd_id' => $letterboxdId, 'updated_at' => (string)DateTime::create()], ['id' => $id]);
    }

    public function updateTraktId(int $id, TraktId $traktId) : void
    {
        $this->dbConnection->update('movie', ['trakt_id' => $traktId->asInt(), 'updated_at' => (string)DateTime::create()], ['id' => $id]);
    }

    public function updateUserRating(int $movieId, int $userId, PersonalRating $rating) : void
    {
        $this->dbConnection->executeQuery(
            'UPDATE movie_user_rating SET rating = ?, updated_at = ? WHERE movie_id = ? AND user_id = ?',
            [$rating->asInt(), (string)DateTime::create(), $movieId, $userId],
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
