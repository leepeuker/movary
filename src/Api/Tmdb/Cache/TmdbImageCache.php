<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Service\ImageCacheService;
use Movary\ValueObject\Job;
use Movary\ValueObject\ResourceType;
use PDO;
use Psr\Log\LoggerInterface;

class TmdbImageCache
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ImageCacheService $imageCacheService,
        private readonly TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return int Count of newly cached images
     */
    public function cacheAllMovieImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages(ResourceType::createMovie(), $forceRefresh);
    }

    /**
     * @return int Count of newly cached images
     */
    public function cacheAllPersonImages(bool $forceRefresh = false) : int
    {
        return $this->cacheImages(ResourceType::createPerson(), $forceRefresh);
    }

    public function deleteCompleteCache() : void
    {
        $this->imageCacheService->deleteImagesByResourceType(ResourceType::createMovie());
        $this->pdo->prepare('UPDATE movie SET poster_path = null')->execute();

        $this->imageCacheService->deleteImagesByResourceType(ResourceType::createPerson());
        $this->pdo->prepare('UPDATE person SET poster_path = null')->execute();
    }

    public function deletedOutdatedCache() : void
    {
        $this->imageCacheService->deleteOutdatedImagesByResourceType(ResourceType::createMovie());
        $this->pdo->prepare('UPDATE movie SET poster_path = null')->execute();

        $this->imageCacheService->deleteOutdatedImagesByResourceType(ResourceType::createPerson());
        $this->pdo->prepare('UPDATE person SET poster_path = null')->execute();
    }

    public function executeJob(Job $job) : void
    {
        foreach ($job->getParameters()['movieIds'] ?? [] as $movieId) {
            $this->cacheAllImagesByMovieId($movieId);
        }

        $this->cachePersonImagesByIds($job->getParameters()['personIds']);
    }

    private function cacheAllImagesByMovieId(int $movieId) : void
    {
        $this->cacheImages(ResourceType::createMovie(), false, [$movieId]);

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
            ) personIdTable",
        );
        $statement->execute([$movieId, $movieId]);

        $this->cacheImages(ResourceType::createPerson(), false, array_column($statement->fetchAll(), 'id'));
    }

    /**
     * @return bool True if image cache was re/generated, false otherwise
     */
    private function cacheImageDataByTableName(array $data, ResourceType $resourceType, bool $forceRefresh = false) : bool
    {
        $cachedImagePublicPath = null;

        if ($data['tmdb_poster_path'] === null) {
            return false;
        }

        try {
            $cachedImagePublicPath = $this->imageCacheService->cacheImage(
                $this->tmdbUrlGenerator->generateImageUrl($data['tmdb_poster_path']),
                $data['id'],
                $resourceType,
                $data['poster_path'] === null ? true : $forceRefresh,
            );
        } catch (\Exception $e) {
            $this->logger->warning('Could not cache ' . $resourceType . 'image: ' . $data['tmdb_poster_path'], ['exception' => $e]);
        }

        if ($cachedImagePublicPath === null) {
            return false;
        }

        $payload = [$cachedImagePublicPath, $data['id']];

        return $this->pdo->prepare("UPDATE $resourceType SET poster_path = ? WHERE id = ?")->execute($payload);
    }

    /**
     * @return int Count of re/generated cached images
     */
    private function cacheImages(ResourceType $resourceType, bool $forceRefresh, array $filerIds = []) : int
    {
        $cachedImages = 0;

        $query = "SELECT id, poster_path, tmdb_poster_path FROM $resourceType";
        if (count($filerIds) > 0) {
            $placeholders = str_repeat('?, ', count($filerIds));
            $query .= ' WHERE id IN (' . trim($placeholders, ', ') . ')';
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($filerIds);

        foreach ($statement as $imageDataBeforeUpdate) {
            if ($this->cacheImageDataByTableName($imageDataBeforeUpdate, $resourceType, $forceRefresh) === true) {
                if ($imageDataBeforeUpdate['poster_path'] !== null) {
                    $statement = $this->pdo->prepare("SELECT poster_path FROM $resourceType WHERE id = ?");
                    $statement->execute([$imageDataBeforeUpdate['id']]);

                    $imageDataAfterUpdate = $statement->fetch();
                    if ($imageDataAfterUpdate['poster_path'] !== $imageDataBeforeUpdate['poster_path']) {
                        $this->imageCacheService->deleteImageByPosterPath($imageDataBeforeUpdate['poster_path']);
                    }
                }

                $cachedImages++;
            }
        }

        return $cachedImages;
    }

    private function cachePersonImagesByIds(array $personIds) : void
    {
        $this->cacheImages(ResourceType::createPerson(), false, $personIds);
    }
}
