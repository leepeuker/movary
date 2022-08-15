<?php declare(strict_types=1);

namespace Movary\ValueObject;

class JobType
{
    private const TYPE_LETTERBOXD_IMPORT_HISTORY = 'letterboxd_import_history';

    private const TYPE_LETTERBOXD_IMPORT_RATINGS = 'letterboxd_import_ratings';

    private const TYPE_TMDB_SYNC = 'tmdb_sync';

    private const TYPE_TRAKT_IMPORT_HISTORY = 'trakt_import_history';

    private const TYPE_TRAKT_IMPORT_RATINGS = 'trakt_import_ratings';

    private function __construct(private readonly string $type)
    {
        if (in_array($this->type, [
                self::TYPE_LETTERBOXD_IMPORT_HISTORY,
                self::TYPE_LETTERBOXD_IMPORT_RATINGS,
                self::TYPE_TMDB_SYNC,
                self::TYPE_TRAKT_IMPORT_HISTORY,
                self::TYPE_TRAKT_IMPORT_RATINGS,
            ]) === false) {
            throw new \RuntimeException('Not supported job type: ' . $this->type);
        }
    }

    public static function createFromString(string $status) : self
    {
        return new self($status);
    }

    public static function createLetterboxdImportHistory() : self
    {
        return new self(self::TYPE_LETTERBOXD_IMPORT_HISTORY);
    }

    public static function createLetterboxdImportRatings() : self
    {
        return new self(self::TYPE_LETTERBOXD_IMPORT_RATINGS);
    }

    public static function createTmdbSync() : self
    {
        return new self(self::TYPE_TMDB_SYNC);
    }

    public static function createTraktImportHistory() : self
    {
        return new self(self::TYPE_TRAKT_IMPORT_HISTORY);
    }

    public static function createTraktImportRatings() : self
    {
        return new self(self::TYPE_TRAKT_IMPORT_RATINGS);
    }

    public function __toString() : string
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

    public function isOfTypeTraktImportHistory() : bool
    {
        return $this->type === self::TYPE_TRAKT_IMPORT_HISTORY;
    }

    public function isOfTypeTraktImportRatings() : bool
    {
        return $this->type === self::TYPE_TRAKT_IMPORT_RATINGS;
    }
}
