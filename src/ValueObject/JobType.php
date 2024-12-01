<?php declare(strict_types=1);

namespace Movary\ValueObject;

use JsonSerializable;
use RuntimeException;

class JobType implements JsonSerializable
{
    private const string TYPE_TMDB_PERSON_SYNC = 'tmdb_person_sync';

    private const string TYPE_IMDB_SYNC = 'imdb_sync';

    private const string TYPE_LETTERBOXD_IMPORT_HISTORY = 'letterboxd_import_history';

    private const string TYPE_JELLYFIN_EXPORT_HISTORY = 'jellyfin_export_history';

    private const string TYPE_JELLYFIN_IMPORT_HISTORY = 'jellyfin_import_history';

    private const string TYPE_LETTERBOXD_IMPORT_RATINGS = 'letterboxd_import_ratings';

    private const string TYPE_TMDB_IMAGE_CACHE = 'tmdb_image_cache';

    private const string TYPE_TMDB_MOVIE_SYNC = 'tmdb_movie_sync';

    private const string TYPE_TRAKT_IMPORT_HISTORY = 'trakt_import_history';

    private const string TYPE_TRAKT_IMPORT_RATINGS = 'trakt_import_ratings';

    private const string TYPE_PLEX_IMPORT_WATCHLIST = 'plex_import_watchlist';

    private function __construct(private readonly string $type)
    {
        if (in_array($this->type, [
                self::TYPE_LETTERBOXD_IMPORT_HISTORY,
                self::TYPE_LETTERBOXD_IMPORT_RATINGS,
                self::TYPE_TMDB_IMAGE_CACHE,
                self::TYPE_TMDB_MOVIE_SYNC,
                self::TYPE_TMDB_PERSON_SYNC,
                self::TYPE_TRAKT_IMPORT_HISTORY,
                self::TYPE_TRAKT_IMPORT_RATINGS,
                self::TYPE_IMDB_SYNC,
                self::TYPE_PLEX_IMPORT_WATCHLIST,
                self::TYPE_JELLYFIN_EXPORT_HISTORY,
                self::TYPE_JELLYFIN_IMPORT_HISTORY,
            ]) === false) {
            throw new RuntimeException('Not supported job type: ' . $this->type);
        }
    }

    public static function createFromString(string $status) : self
    {
        return new self($status);
    }

    public static function createImdbSync() : self
    {
        return new self(self::TYPE_IMDB_SYNC);
    }

    public static function createJellyfinExportMovies() : self
    {
        return new self(self::TYPE_JELLYFIN_EXPORT_HISTORY);
    }

    public static function createJellyfinImportMovies() : self
    {
        return new self(self::TYPE_JELLYFIN_IMPORT_HISTORY);
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
        return new self(self::TYPE_TMDB_IMAGE_CACHE);
    }

    public static function createTmdbMovieSync() : self
    {
        return new self(self::TYPE_TMDB_MOVIE_SYNC);
    }

    public static function createTmdbPersonSync() : self
    {
        return new self(self::TYPE_TMDB_PERSON_SYNC);
    }

    public static function createTmdbPersonSyncJob() : self
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

    public function isOfTypeJellyfinExportMovies() : bool
    {
        return $this->type === self::TYPE_JELLYFIN_EXPORT_HISTORY;
    }

    public function isOfTypeJellyfinImportMovies() : bool
    {
        return $this->type === self::TYPE_JELLYFIN_IMPORT_HISTORY;
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
        return $this->type === self::TYPE_TMDB_IMAGE_CACHE;
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

    public function jsonSerialize() : string
    {
        return $this->type;
    }
}
