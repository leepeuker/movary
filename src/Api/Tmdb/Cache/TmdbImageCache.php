<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Cache;

use Doctrine\DBAL\Connection;
use Exception;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\JobQueue\JobEntity;
use Movary\Service\ImageCacheService;
use Movary\ValueObject\ResourceType;
use Psr\Log\LoggerInterface;

class TmdbImageCache
{
    public function __construct(
        private readonly Connection $dbConnection,
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
        $this->dbConnection->prepare('UPDATE movie SET poster_path = null')->executeQuery();

        $this->imageCacheService->deleteImagesByResourceType(ResourceType::createPerson());
        $this->dbConnection->prepare('UPDATE person SET poster_path = null')->executeQuery();
    }

    public function deletedOutdatedCache() : int
    {
        $deletionCounter = 0;

        $deletionCounter += $this->imageCacheService->deleteOutdatedImagesByResourceType(ResourceType::createMovie());
        $this->dbConnection->prepare('UPDATE movie SET poster_path = null')->executeQuery();

        $deletionCounter += $this->imageCacheService->deleteOutdatedImagesByResourceType(ResourceType::createPerson());
        $this->dbConnection->prepare('UPDATE person SET poster_path = null')->executeQuery();

        return $deletionCounter;
    }

    public function executeJob(JobEntity $job) : void
    {
        foreach ($job->getParameters()['movieIds'] ?? [] as $movieId) {
            $this->cacheAllImagesByMovieId($movieId);
        }

        $personIds = $job->getParameters()['personIds'] ?? [];
        if (count($personIds) > 0) {
            $this->cachePersonImagesByIds($personIds);
        }
    }

    private function cacheAllImagesByMovieId(int $movieId) : void
    {
        $this->cacheImages(ResourceType::createMovie(), false, [$movieId]);

        $statement = $this->dbConnection->prepare(
            "SELECT DISTINCT (id)
            FROM (
                SELECT id
                FROM person
                JOIN movie_cast mcast on person.id = mcast.person_id
                WHERE mcast.movie_id = ?
                UNION
                SELECT id
                FROM person
                JOIN movie_crew crew on person.id = crew.person_id
                WHERE crew.movie_id = ?
            ) personIdTable",
        );
        $dbResult = $statement->executeQuery([$movieId, $movieId]);

        $this->cacheImages(ResourceType::createPerson(), false, $dbResult->fetchFirstColumn());
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
        } catch (Exception $e) {
            $this->logger->warning('Could not cache ' . $resourceType . ' image: ' . $data['tmdb_poster_path'], ['exception' => $e]);
        }

        if ($cachedImagePublicPath === null) {
            return false;
        }

        $payload = [$cachedImagePublicPath, $data['id']];

        return $this->dbConnection->prepare("UPDATE $resourceType SET poster_path = ? WHERE id = ?")->executeStatement($payload) === 1;
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

        $queryResult = $this->dbConnection->prepare($query)->executeQuery($filerIds);

        foreach ($queryResult->iterateAssociative() as $imageDataBeforeUpdate) {
            if ($this->cacheImageDataByTableName($imageDataBeforeUpdate, $resourceType, $forceRefresh) === true) {
                if ($imageDataBeforeUpdate['poster_path'] !== null) {
                    $statement = $this->dbConnection->prepare("SELECT poster_path FROM $resourceType WHERE id = ?");

                    if ($statement->executeQuery([$imageDataBeforeUpdate['id']])->fetchOne() !== $imageDataBeforeUpdate['poster_path']) {
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
