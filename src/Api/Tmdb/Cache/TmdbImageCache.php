<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Application\Service\ImageCacheService;

class TmdbImageCache
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly ImageCacheService $imageCacheService,
        private readonly TmdbUrlGenerator $tmdbUrlGenerator
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

            $cachedImagePublicPath = $this->imageCacheService->cacheImage(
                $this->tmdbUrlGenerator->generateImageUrl($row['tmdb_poster_path']),
                $forceRefresh
            );

            if ($cachedImagePublicPath === null) {
                continue;
            }

            $payload = [$cachedImagePublicPath, $row['id']];
            $this->pdo->prepare("UPDATE $tableName SET poster_path = ? WHERE id = ?")->execute($payload);

            $cachedImages++;
        }

        return $cachedImages;
    }
}
