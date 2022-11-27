<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Movary\Application\Service\ImageCacheService;
use Movary\ValueObject\Url;

class TmdbImageCache
{
    const TMDB_BASE_URL = 'https://image.tmdb.org/t/p/w342/';

    public function __construct(
        private readonly \PDO $pdo,
        private readonly ImageCacheService $imageCacheService,
    ) {
    }

    /**
     * @return int Count of newly cached images
     */
    public function cacheMovieImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages('movie', $forceRefresh);
    }

    /**
     * @return int Count of newly cached images
     */
    public function cachePersonImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages('person', $forceRefresh);
    }

    public function deleteCache() : void
    {
        $this->imageCacheService->deleteImages();
    }

    /**
     * @return int Count of newly cached images
     */
    private function cacheImages(string $tableName, bool $forceRefresh) : int
    {
        $cachedImages = 0;

        $statement = $this->pdo->prepare("SELECT id, tmdb_poster_path FROM $tableName");
        $statement->execute();

        foreach ($statement->getIterator() as $row) {
            if ($row['tmdb_poster_path'] === null) {
                continue;
            }

            $tmdbPosterUrl = $this->createTmdbImageUrl($row['tmdb_poster_path']);
            $cachedImagePublicPath = $this->imageCacheService->cacheImage($tmdbPosterUrl, $forceRefresh);

            if ($cachedImagePublicPath === null) {
                continue;
            }

            $this->pdo
                ->prepare("UPDATE $tableName SET poster_path = ? WHERE id = ?")
                ->execute([$cachedImagePublicPath, $row['id']]);

            $cachedImages++;
        }

        return $cachedImages;
    }

    private function createTmdbImageUrl(mixed $tmdbImagePath) : Url
    {
        $trimmedImagePath = trim($tmdbImagePath, '/');

        return Url::createFromString(self::TMDB_BASE_URL . $trimmedImagePath);
    }
}
