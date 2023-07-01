<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Watchlist;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

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
                'added_at' => $addedAt ?: (string)DateTime::create()
            ],
        );
    }

    public function fetchWatchlistCount(int $userId, ?string $searchTerm) : int
    {
        if ($searchTerm !== null) {
            return $this->dbConnection->fetchFirstColumn(
                <<<SQL
                SELECT COUNT(*)
                FROM watchlist
                JOIN movie m on movie_id = m.id
                WHERE m.title LIKE ? AND user_id = ?
                SQL,
                ["%$searchTerm%", $userId],
            )[0];
        }

        return $this->dbConnection->fetchFirstColumn(
            'SELECT COUNT(*) FROM watchlist JOIN movie m on movie_id = m.id WHERE user_id = ?',
            [$userId],
        )[0];
    }

    public function fetchWatchlistPaginated(int $userId, int $limit, int $page, ?string $searchTerm) : array
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
            SELECT m.*, mur.rating as userRating
            FROM movie m
            JOIN watchlist wl on wl.movie_id = m.id and wl.user_id = ?
            LEFT JOIN movie_user_rating mur ON wl.movie_id = mur.movie_id and mur.user_id = ?
            $whereQuery
            ORDER BY added_at DESC
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
