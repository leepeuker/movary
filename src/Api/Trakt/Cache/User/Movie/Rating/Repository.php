<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Rating;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\DateTime;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function clearCache() : void
    {
        $this->dbConnection->executeQuery('DELETE FROM `cache_trakt_user_movie_rating`');
    }

    public function create(TraktId $traktId, int $rating, DateTime $ratedAt) : void
    {
        $this->dbConnection->insert(
            'cache_trakt_user_movie_rating',
            [
                'trakt_id' => $traktId->asInt(),
                'rating' => $rating,
                'rated_at' => (string)$ratedAt,
            ]
        );
    }

    public function findByTraktId(TraktId $traktId) : ?int
    {
        $data = $this->dbConnection->fetchOne('SELECT rating FROM `cache_trakt_user_movie_rating` WHERE trakt_id = ?', [$traktId->asInt()]);

        return $data === false ? null : (int)$data;
    }
}
