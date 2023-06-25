<?php declare(strict_types=1);

namespace Movary\ValueObject;

use RuntimeException;

class JobType
{
    private const TYPE_PLEX_IMPORT_WATCHLIST = 'plex_import_watchlist';

    private const TYPE_TMDB_PERSON_SYNC = 'tmdb_person_sync';

    private const TYPE_IMDB_SYNC = 'imdb_sync';

    private const TYPE_LETTERBOXD_IMPORT_HISTORY = 'letterboxd_import_history';

    private const TYPE_LETTERBOXD_IMPORT_RATINGS = 'letterboxd_import_ratings';

    private const TYPE_TMDB_IMAGE_CHACHE = 'tmdb_image_cache';

    private const TYPE_TMDB_MOVIE_SYNC = 'tmdb_movie_sync';

    private const TYPE_TRAKT_IMPORT_HISTORY = 'trakt_import_history';

    private const TYPE_TRAKT_IMPORT_RATINGS = 'trakt_import_ratings';

    private function __construct(private readonly string $type)
    {
        if (in_array($this->type, [
                self::TYPE_LETTERBOXD_IMPORT_HISTORY,
                self::TYPE_LETTERBOXD_IMPORT_RATINGS,
                self::TYPE_TMDB_IMAGE_CHACHE,
                self::TYPE_TMDB_MOVIE_SYNC,
                self::TYPE_TMDB_PERSON_SYNC,
                self::TYPE_TRAKT_IMPORT_HISTORY,
                self::TYPE_TRAKT_IMPORT_RATINGS,
                self::TYPE_IMDB_SYNC,
                self::TYPE_PLEX_IMPORT_WATCHLIST,
            ]) === false) {
            throw new RuntimeException('Not supported job type: ' . $this->type);
        }
    }

    public static function addTmdbPersonSyncJob() : self
    {
        return new self(self::TYPE_TMDB_PERSON_SYNC);
    }

    public static function createFromString(string $status) : self
    {
        return new self($status);
    }

    public static function createImdbSync() : self
    {
        return new self(self::TYPE_IMDB_SYNC);
    }

    public static function createLetterboxdImportHistory() : self
    {
        return new self(self::TYPE_LETTERBOXD_IMPORT_HISTORY);
    }

    public static function createLetterboxdImportRatings() : self
    {
        return new self(self::TYPE_LETTERBOXD_IMPORT_RATINGS);
    }

    public static function createPlexImportWatchlist() : self
    {
        return new self(self::TYPE_PLEX_IMPORT_WATCHLIST);
    }

    public static function createTmdbImageCache() : self
    {
        return new self(self::TYPE_TMDB_IMAGE_CHACHE);
    }

    public static function createTmdbMovieSync() : self
    {
        return new self(self::TYPE_TMDB_MOVIE_SYNC);
    }

    public static function createTmdbPersonSync() : self
    {
        return new self(self::TYPE_TMDB_PERSON_SYNC);
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

    public function isOfTypePlexImportWatchlist() : bool
    {
        return $this->type === self::TYPE_PLEX_IMPORT_WATCHLIST;
    }

    public function isOfTypeTmdbImageCache() : bool
    {
        return $this->type === self::TYPE_TMDB_IMAGE_CHACHE;
    }

    public function isOfTypeTmdbMovieSync() : bool
    {
        return $this->type === self::TYPE_TMDB_MOVIE_SYNC;
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
