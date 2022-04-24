<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\DateTime;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
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

    public function removeAllWithTraktId(TraktId $traktId) : void
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM `cache_trakt_user_movie_watched` WHERE trakt_id = ?',
            [$traktId->asInt()]
        );
    }
}
