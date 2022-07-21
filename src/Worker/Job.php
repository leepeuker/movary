<?php declare(strict_types=1);

namespace Movary\Worker;

use Ramsey\Uuid\Uuid;

class Job implements \JsonSerializable
{
    private const TYPE_LETTERBOXD_IMPORT_HISTORY = 'letterboxd_sync_history';

    private const TYPE_LETTERBOXD_IMPORT_RATINGS = 'letterboxd_sync_ratings';

    private const TYPE_TMDB_SYNC = 'tmdb_sync';

    private const TYPE_TRAKT_SYNC_HISTORY = 'trakt_sync_history';

    private const TYPE_TRAKT_SYNC_RATINGS = 'trakt_sync_ratings';

    private function __construct(
        private readonly string $uuid,
        private readonly string $type,
        private readonly array $parameters
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['uuid'],
            $data['type'],
            $data['parameters'],
        );
    }

    public static function createFromParameters(string $uuid, string $type, array $parameters) : self
    {
        return new self($uuid, $type, $parameters);
    }

    public static function createLetterboxImportHistorySync(int $userId, string $importFile) : self
    {
        return new self((string)Uuid::uuid4(), self::TYPE_LETTERBOXD_IMPORT_HISTORY, ['userId' => $userId, 'importFile' => $importFile]);
    }

    public static function createLetterboxImportRatings(int $userId, string $importFile) : self
    {
        return new self((string)Uuid::uuid4(), self::TYPE_LETTERBOXD_IMPORT_RATINGS, ['userId' => $userId, 'importFile' => $importFile]);
    }

    public static function createTmdbSync() : self
    {
        return new self((string)Uuid::uuid4(), self::TYPE_TMDB_SYNC, []);
    }

    public static function createTraktHistorySync(int $userId) : self
    {
        return new self((string)Uuid::uuid4(), self::TYPE_TRAKT_SYNC_HISTORY, ['userId' => $userId]);
    }

    public static function createTraktRatingsSync(int $userId) : self
    {
        return new self((string)Uuid::uuid4(), self::TYPE_TRAKT_SYNC_RATINGS, ['userId' => $userId]);
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function isOfTypeLetterboxdImportHistory() : bool
    {
        return $this->type === self::TYPE_LETTERBOXD_IMPORT_HISTORY;
    }

    public function isOfTypeLetterboxdImportRankings() : bool
    {
        return $this->type === self::TYPE_LETTERBOXD_IMPORT_RATINGS;
    }

    public function isOfTypeTmdbSync() : bool
    {
        return $this->type === self::TYPE_TMDB_SYNC;
    }

    public function isOfTypeTraktSyncHistory() : bool
    {
        return $this->type === self::TYPE_TRAKT_SYNC_HISTORY;
    }

    public function isOfTypeTraktSyncRankings() : bool
    {
        return $this->type === self::TYPE_TRAKT_SYNC_RATINGS;
    }

    public function jsonSerialize() : array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'parameters' => $this->parameters,
        ];
    }
}
