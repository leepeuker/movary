<?php declare(strict_types=1);

namespace Movary\Util;

class Gzip
{
    public function __construct(
        private readonly File $fileUtil,
    ) {
    }

    public function extract(string $sourceFile, string $destinationFile): void
    {
        if ($this->fileUtil->fileExists($sourceFile) === false) {
            throw new \RuntimeException("Source file not found: {$sourceFile}");
        }

        $compressedData = $this->fileUtil->readFile($sourceFile);

        $uncompressedData = gzdecode($compressedData);

        if ($uncompressedData === false) {
            throw new \RuntimeException("Failed to decompress file: $sourceFile");
        }

        $this->fileUtil->createFile($destinationFile, $uncompressedData);
    }
}
