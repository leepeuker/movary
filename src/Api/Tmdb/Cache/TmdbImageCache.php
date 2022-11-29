<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Application\Service\ImageCacheService;
use Movary\ValueObject\Job;

class TmdbImageCache
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly ImageCacheService $imageCacheService,
        private readonly TmdbUrlGenerator $tmdbUrlGenerator
    ) {
    }

    public function cacheAllImagesByMovieId(int $movieId, bool $forceRefresh = false) : void
    {
        $this->cacheImages('movie', $forceRefresh, [$movieId]);

        $statement = $this->pdo->prepare(
            "SELECT DISTINCT (id)
            FROM (
                SELECT id
                FROM person
                JOIN movie_cast cast on person.id = cast.person_id
                WHERE cast.movie_id = ?
                UNION
                SELECT id
                FROM person
                JOIN movie_crew crew on person.id = crew.person_id
                WHERE crew.movie_id = ?
            ) personIdTable"
        );
        $statement->execute([$movieId, $movieId]);

        $this->cacheImages('person', $forceRefresh, array_column($statement->fetchAll(), 'id'));
    }

    /**
     * @return int Count of newly cached images
     */
    public function cacheAllMovieImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages('movie', $forceRefresh);
    }

    /**
     * @return int Count of newly cached images
     */
    public function cacheAllPersonImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages('person', $forceRefresh);
    }

    public function deleteCache() : void
    {
        $this->imageCacheService->deleteImages();
        $this->pdo->prepare('UPDATE movie SET poster_path = null')->execute();
        $this->pdo->prepare('UPDATE person SET poster_path = null')->execute();
    }

    public function executeJob(Job $job) : void
    {
        $movieIds = $job->getParameters()['movieIds'] ?? [];

        foreach ($movieIds as $movieId) {
            $this->cacheAllImagesByMovieId($movieId);
        }
    }

    /**
     * @return bool True if image cache was re/generated, false otherwise
     */
    private function cacheImageDataByTableName(array $data, string $tableName, bool $forceRefresh = false) : bool
    {
        if ($data['tmdb_poster_path'] === null) {
            return false;
        }

        $cachedImagePublicPath = $this->imageCacheService->cacheImage(
            $this->tmdbUrlGenerator->generateImageUrl($data['tmdb_poster_path']),
            $forceRefresh
        );

        if ($cachedImagePublicPath === null) {
            return false;
        }

        $payload = [$cachedImagePublicPath, $data['id']];

        return $this->pdo->prepare("UPDATE $tableName SET poster_path = ? WHERE id = ?")->execute($payload);
    }

    /**
     * @return int Count of re/generated cached images
     */
    private function cacheImages(string $tableName, bool $forceRefresh, array $filerIds = []) : int
    {
        $cachedImages = 0;

        $query = "SELECT id, tmdb_poster_path FROM $tableName";
        if (count($filerIds) > 0) {
            $placeholders = str_repeat('?, ', count($filerIds));
            $query .= ' WHERE id IN (' . trim($placeholders, ', ') . ')';
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($filerIds);

        foreach ($statement->getIterator() as $row) {
            if ($this->cacheImageDataByTableName($row, $tableName, $forceRefresh) === true) {
                $cachedImages++;
            }
        }

        return $cachedImages;
    }
}
