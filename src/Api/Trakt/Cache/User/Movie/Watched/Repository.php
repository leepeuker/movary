<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

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

    public function create(TraktId $traktId, DateTime $lastUpdatedAt) : void
    {
        $this->dbConnection->insert(
            'cache_trakt_user_movie_watched',
            [
                'trakt_id' => $traktId->asInt(),
                'last_updated_at' => (string)$lastUpdatedAt,
            ]
        );
    }

    /**
     * @return array<TraktId>
     */
    public function fetchAllUniqueTraktIds() : array
    {
        $rows = $this->dbConnection->fetchFirstColumn('SELECT DISTINCT trakt_id FROM `cache_trakt_user_movie_watched`');

        $traktIds = [];

        foreach ($rows as $row) {
            $traktIds[] = TraktId::createFromInt($row);
        }

        return $traktIds;
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `cache_trakt_user_movie_watched` WHERE trakt_id = ?', [$traktId->asInt()]);

        return $data === false ? null : Entity::createFromArray($data);
    }

    public function findLastUpdatedByTraktId(TraktId $traktId) : ?DateTime
    {
        $data = $this->dbConnection->fetchOne(
            'SELECT last_updated_at
            FROM cache_trakt_user_movie_watched
            WHERE trakt_id = ?',
            [$traktId->asInt()]
        );

        return $data === false ? null : DateTime::createFromString($data);
    }

    public function findLatestLastUpdatedAt() : ?DateTime
    {
        $data = $this->dbConnection->fetchOne(
            'SELECT last_updated_at
            FROM cache_trakt_user_movie_watched
            ORDER BY last_updated_at DESC
            LIMIT 1'
        );

        return $data === false ? null : DateTime::createFromString($data);
    }

    public function removeAllWithTraktId(TraktId $traktId) : void
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM `cache_trakt_user_movie_watched` WHERE trakt_id = ?',
            [$traktId->asInt()]
        );
    }
}
