<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Rating;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\DateTime;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function clearCache(int $userId) : void
    {
        $this->dbConnection->executeQuery('DELETE FROM `cache_trakt_user_movie_rating` WHERE user_id = ?', [$userId]);
    }

    public function create(int $userId, TraktId $traktId, int $rating, DateTime $ratedAt) : void
    {
        $this->dbConnection->insert(
            'cache_trakt_user_movie_rating',
            [
                'trakt_id' => $traktId->asInt(),
                'user_id' => $userId,
                'rating' => $rating,
                'rated_at' => (string)$ratedAt,
            ],
        );
    }

    public function findByTraktId(int $userId, TraktId $traktId) : ?int
    {
        $data = $this->dbConnection->fetchOne('SELECT rating FROM `cache_trakt_user_movie_rating` WHERE trakt_id = ? AND user_id = ?', [$traktId->asInt(), $userId]);

        return $data === false ? null : (int)$data;
    }
}
