<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Watchlist;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class MovieWatchlistRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function addMovieToWatchlist(int $userId, int $movieId, ?DateTime $addedAt = null) : void
    {
        $this->dbConnection->insert(
            'watchlist',
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
                'added_at' => $addedAt === null ? (string)DateTime::create() : (string)$addedAt
            ],
        );
    }

    public function fetchAllWatchlistItems(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT title, tmdb_id, imdb_id, added_at FROM watchlist JOIN movie m ON movie_id = m.id WHERE user_id = ?', [$userId],
        );
    }

    public function fetchTmdbIdsToWatchlistMap(int $userId, array $tmdbIds) : array
    {
        if (count($tmdbIds) === 0) {
            return [];
        }

        $placeholders = trim(str_repeat('?, ', count($tmdbIds)), ', ');

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT tmdb_id
            FROM watchlist
            JOIN movie m on m.id = watchlist.movie_id
            WHERE user_id = ? AND tmdb_id IN ($placeholders)
            GROUP by tmdb_id
            SQL,
            [
                $userId,
                ...$tmdbIds
            ],
        );
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT DISTINCT g.name
            FROM watchlist w
            JOIN movie m on w.movie_id = m.id
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
                FROM watchlist mh
                JOIN movie m on mh.movie_id = m.id
                WHERE user_id = ?
                ORDER BY original_language DESC
                SQL,
            [$userId],
        );
    }

    public function fetchUniqueMovieProductionCountries(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT DISTINCT mpc.iso_3166_1, c.english_name
            FROM watchlist w
            JOIN movie m on w.movie_id = m.id
            JOIN movie_production_countries mpc on mpc.movie_id = m.id
            JOIN country c on c.iso_3166_1 = mpc.iso_3166_1
            WHERE user_id = ?
            ORDER BY c.english_name
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
                FROM watchlist w
                JOIN movie m on w.movie_id = m.id
                WHERE user_id = ?
                ORDER BY strftime('%Y',release_date) DESC
                SQL,
                [$userId],
            );
        }

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
                SELECT DISTINCT YEAR(m.release_date)
                FROM watchlist w
                JOIN movie m on w.movie_id = m.id
                WHERE user_id = ?
                ORDER BY YEAR(m.release_date) DESC
                SQL,
            [$userId],
        );
    }

    public function fetchWatchlistCount(
        int $userId,
        ?string $searchTerm,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?string $productionCountryCode = null,
    ) : int {
        $payload = [$userId];

        $joinProductionCountry = '';
        if (empty($productionCountryCode) === false) {
            $joinProductionCountry = 'JOIN movie_production_countries pc on pc.movie_id = m.id AND pc.iso_3166_1 = ? ';
            $payload[] = $productionCountryCode;
        }

        $whereQuery = 'WHERE m.title LIKE ? ';
        $payload[] = "%$searchTerm%";

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

        return $this->dbConnection->fetchFirstColumn(
            <<<SQL
            SELECT COUNT(DISTINCT m.id)
            FROM movie m
            JOIN watchlist w on w.movie_id = m.id and w.user_id = ?
            LEFT JOIN movie_genre mg on m.id = mg.movie_id
            LEFT JOIN genre g on mg.genre_id = g.id
            $joinProductionCountry
            $whereQuery
            SQL,
            $payload,
        )[0];
    }

    public function fetchWatchlistPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?string $productionCountryCode,
    ) : array {
        $payload = [$userId, $userId];

        $offset = ($limit * $page) - $limit;

        $sortBySanitized = match ($sortBy) {
            'rating' => 'rating',
            'releaseDate' => 'release_date',
            'addedAt' => 'added_at',
            'runtime' => 'runtime',
            default => 'title'
        };

        $joinProductionCountry = '';
        if (empty($productionCountryCode) === false) {
            $joinProductionCountry = 'JOIN movie_production_countries pc on pc.movie_id = m.id AND pc.iso_3166_1 = ? ';
            $payload[] = $productionCountryCode;
        }

        $whereQuery = 'WHERE m.title LIKE ? ';
        $payload[] = "%$searchTerm%";

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

        return $this->dbConnection->fetchAllAssociative(
            <<<SQL
            SELECT m.*, mur.rating as userRating, added_at
            FROM movie m
            JOIN watchlist wl on wl.movie_id = m.id and wl.user_id = ?
            LEFT JOIN movie_user_rating mur ON wl.movie_id = mur.movie_id and mur.user_id = ?
            LEFT JOIN movie_genre mg on m.id = mg.movie_id
            LEFT JOIN genre g on mg.genre_id = g.id
            $joinProductionCountry
            $whereQuery
            GROUP BY m.id, title, release_date, added_at, rating
            ORDER BY $sortBySanitized $sortOrder, title asc
            LIMIT $offset, $limit
            SQL,
            $payload,
        );
    }

    public function hasMovieInWatchlist(int $userId, int $movieId) : bool
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `watchlist` WHERE movie_id = ? AND user_id = ?', [$movieId, $userId]);

        return count($data) > 0;
    }

    public function removeMovieFromWatchlist(int $userId, int $movieId) : void
    {
        $this->dbConnection->delete(
            'watchlist',
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
            ],
        );
    }
}
