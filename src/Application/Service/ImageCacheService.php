<?php declare(strict_types=1);

namespace Movary\Application\Service;

use Movary\Util\File;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

class ImageCacheService
{
    private const CACHE_DIR = __DIR__ . '/../../../public/images/cached/';

    private const CACHE_DIR_PERMISSIONS = 0755;

    public function __construct(
        private readonly File $fileUtil,
        private readonly LoggerInterface $logger
    ) {
        $this->fileUtil->createDirectory(self::CACHE_DIR, self::CACHE_DIR_PERMISSIONS);
    }

    /**
     * @return string|null Public path of image if file path was not already used
     */
    public function cacheImage(Url $imageUrl, bool $forceRefresh = false) : ?string
    {
        $imageFile = self::CACHE_DIR . trim((string)$imageUrl->getPath(), '/');

        if ($forceRefresh === false && $this->fileUtil->fileExists($imageFile) === true) {
            return null;
        }

        $this->fileUtil->createDirectory(dirname($imageFile), self::CACHE_DIR_PERMISSIONS);

        $imageData = file_get_contents((string)$imageUrl);
        if ($imageData === false) {
            sleep(1);

            $imageData = file_get_contents((string)$imageUrl);
            if ($imageData === false) {
                throw new \RuntimeException('Could not fetch image for caching: ' . $imageUrl);
            }
        }

        $this->fileUtil->createFile($imageFile, $imageData);
        $this->logger->debug('Cached image: ' . $imageUrl);

        return str_replace(__DIR__ . '/../../../public/', '', $imageFile);
    }

    public function deleteImages() : void
    {
        $this->fileUtil->deleteDirectoryRecursively(self::CACHE_DIR);
    }
}
