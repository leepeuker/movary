<?php declare(strict_types=1);

namespace Movary\Service;

use DirectoryIterator;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Request;
use Movary\Util\File;
use Movary\ValueObject\ResourceType;
use Movary\ValueObject\Url;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImageCacheService
{
    private const int CACHE_DIR_PERMISSIONS = 0755;

    public function __construct(
        private readonly File $fileUtil,
        private readonly LoggerInterface $logger,
        private readonly ClientInterface $httpClient,
        private readonly Connection $dbConnection,
        private readonly string $publicDirectory,
        private readonly string $imageBasePath,
    ) {
        $this->fileUtil->createDirectory($this->publicDirectory, self::CACHE_DIR_PERMISSIONS);
    }

    /**
     * @return string|null Public path of image if file path was not already used
     */
    public function cacheImage(Url $imageUrl, int $resourceId, ResourceType $resourceType, bool $forceRefresh) : ?string
    {
        $imageFile = $this->generateStorageFilePath($resourceId, $resourceType, $imageUrl);

        if ($forceRefresh === false && $this->fileUtil->fileExists($imageFile) === true) {
            return null;
        }

        $this->fileUtil->createDirectory(dirname($imageFile), self::CACHE_DIR_PERMISSIONS);

        $request = new Request('GET', (string)$imageUrl);

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            sleep(1);

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Could not fetch image for caching: ' . $imageUrl);
            }
        }

        $this->fileUtil->createFile($imageFile, $response->getBody()->getContents());
        $this->logger->debug('Cached image: ' . $imageUrl);

        return str_replace($this->publicDirectory, '/', $imageFile);
    }

    public function deleteImageByPosterPath(string $posterPath) : void
    {
        $imageFile = $this->publicDirectory . trim($posterPath, '/');

        if ($this->fileUtil->fileExists($imageFile) === false) {
            return;
        }

        $this->fileUtil->deleteFile($imageFile);
    }

    public function deleteImagesByResourceType(ResourceType $resourceType) : void
    {
        $this->fileUtil->deleteDirectoryContent($this->generateStorageDirectory($resourceType));
    }

    public function deleteOutdatedImagesByResourceType(ResourceType $resourceType) : int
    {
        $iterator = new DirectoryIterator($this->generateStorageDirectory($resourceType));

        $deletionCounter = 0;

        foreach ($iterator as $file) {
            if ($file->isDir() === true) {
                continue;
            }
            $resourceId = pathinfo($file->getPathname(), PATHINFO_FILENAME);

            $result = $this->dbConnection->executeQuery("SELECT id FROM $resourceType WHERE id = ?", [$resourceId]);

            if (count($result->fetchAll()) > 0) {
                continue;
            }

            $this->fileUtil->deleteFile($file->getPathname());

            $deletionCounter++;
        }

        return $deletionCounter;
    }

    public function posterPathExists(string $posterPath) : bool
    {
        $imageFile = $this->publicDirectory . trim($posterPath, '/');

        return $this->fileUtil->fileExists($imageFile);
    }

    private function generateStorageDirectory(ResourceType $resourceType) : string
    {
        $pathId = match (true) {
            $resourceType->isMovie() => 'movie',
            $resourceType->isPerson() => 'person',

            default => throw new RuntimeException('Not handled resource type: ' . $resourceType)
        };

        return $this->publicDirectory . trim($this->imageBasePath, '/') . '/' . $pathId . '/';
    }

    private function generateStorageFilePath(int $resourceId, ResourceType $resourceType, Url $imageUrl) : string
    {
        $imageUrlPath = $imageUrl->getPath();

        if ($imageUrlPath == null) {
            throw new RuntimeException('Could not get url path from image url: ' . $imageUrl);
        }

        $imageFileExtension = pathinfo($imageUrlPath, PATHINFO_EXTENSION);

        return $this->generateStorageDirectory($resourceType) . $resourceId . '.' . $imageFileExtension;
    }
}
