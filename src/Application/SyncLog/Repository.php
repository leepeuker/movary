<?php declare(strict_types=1);

namespace Movary\Application\SyncLog;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class Repository
{
    private const TYPE_LETTERBOXD = 'letterboxed';

    private const TYPE_TMDB = 'tmdb';

    private const TYPE_TRAKT = 'trakt';

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function findLastLetterboxdSync() : ?DateTime
    {
        return $this->findLastDateForSyncByType(self::TYPE_LETTERBOXD);
    }

    public function findLastTmdbSync() : ?DateTime
    {
        return $this->findLastDateForSyncByType(self::TYPE_TMDB);
    }

    public function findLastTraktSync() : ?DateTime
    {
        return $this->findLastDateForSyncByType(self::TYPE_TRAKT);
    }

    public function insertLogForLetterboxdSync() : void
    {
        $this->insertLogForSyncByType(self::TYPE_LETTERBOXD);
    }

    public function insertLogForTmdbSync() : void
    {
        $this->insertLogForSyncByType(self::TYPE_TMDB);
    }

    public function insertLogForTraktSync() : void
    {
        $this->insertLogForSyncByType(self::TYPE_TRAKT);
    }

    private function findLastDateForSyncByType(string $type) : ?DateTime
    {
        $data = $this->dbConnection->fetchFirstColumn('SELECT created_at FROM `sync_log` WHERE type = ? ORDER BY created_at DESC', [$type]);

        if (count($data) === 0) {
            return null;
        }

        return DateTime::createFromString($data[0]);
    }

    private function insertLogForSyncByType(string $type) : void
    {
        $this->dbConnection->insert(
            'sync_log',
            [
                'type' => $type,
            ]
        );
    }
}
