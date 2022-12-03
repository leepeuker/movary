<?php declare(strict_types=1);

namespace Movary\Service;

use GuzzleHttp\Psr7\Request;
use Movary\Util\File;
use Movary\ValueObject\Url;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ImageCacheService
{
    private const CACHE_DIR_PERMISSIONS = 0755;

    public function __construct(
        private readonly File $fileUtil,
        private readonly LoggerInterface $logger,
        private readonly ClientInterface $httpClient,
        private readonly string $cacheDirectory,
        private readonly string $imageCacheBaseUrlPath,
    ) {
        $this->fileUtil->createDirectory($this->cacheDirectory, self::CACHE_DIR_PERMISSIONS);
    }

    /**
     * @return string|null Public path of image if file path was not already used
     */
    public function cacheImage(Url $imageUrl, bool $forceRefresh = false) : ?string
    {
        $imageFile = $this->cacheDirectory . trim((string)$imageUrl->getPath(), '/');

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
                throw new \RuntimeException('Could not fetch image for caching: ' . $imageUrl);
            }
        }

        $this->fileUtil->createFile($imageFile, $response->getBody()->getContents());
        $this->logger->debug('Cached image: ' . $imageUrl);

        return $this->imageCacheBaseUrlPath . trim((string)$imageUrl->getPath(), '/');
    }

    // TODO NOT WORKING
    public function deleteImage(string $posterPath) : void
    {
        $imageFile = $this->cacheDirectory .  '../../' . trim($posterPath, '/');

        $this->fileUtil->deleteFile($imageFile);
    }

    public function deleteImages() : void
    {
        $this->fileUtil->deleteDirectoryContent($this->cacheDirectory);
    }
}
