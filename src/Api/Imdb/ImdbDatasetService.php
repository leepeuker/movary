<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use Movary\Util\File;
use Movary\ValueObject\ImdbRating;

class ImdbDatasetService
{
    public function __construct(
        private readonly File $fileUtil,
    ) {
    }

    public function findRating(string $ratingsFile, string $imdbId) : ?ImdbRating
    {
        if ($this->fileUtil->fileExists($ratingsFile) === false) {
            return null;
        }

        $handle = fopen($ratingsFile, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            $this->skipHeaderRow($handle);

            return $this->searchForRatingInChunks($handle, $imdbId);
        } finally {
            fclose($handle);
        }
    }

    private function findRatingInLines(array $lines, string $imdbId, string $imdbIdPrefix, int $prefixLength) : ?ImdbRating
    {
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            /** @psalm-suppress ArgumentTypeCoercion */
            if (strncmp($line, $imdbIdPrefix, $prefixLength) !== 0) {
                continue;
            }

            $rating = $this->parseRatingFromLine($line, $imdbId);
            if ($rating !== null) {
                return $rating;
            }
        }

        return null;
    }

    private function parseRatingFromLine(string $line, string $imdbId) : ?ImdbRating
    {
        $row = str_getcsv($line, "\t");

        if (count($row) >= 3 && $row[0] === $imdbId) {
            return ImdbRating::create((float)$row[1], (int)$row[2]);
        }

        return null;
    }

    /** @param resource $handle */
    private function searchForRatingInChunks($handle, string $imdbId) : ?ImdbRating
    {
        $imdbIdPrefix = $imdbId . "\t";
        $prefixLength = strlen($imdbIdPrefix);
        $bufferSize = 65536;
        $remainder = '';

        while (feof($handle) === false) {
            $chunk = fread($handle, $bufferSize);
            if ($chunk === false) {
                break;
            }

            [$completeLines, $remainder] = $this->splitChunkIntoCompleteLines($chunk, $remainder);

            $rating = $this->findRatingInLines($completeLines, $imdbId, $imdbIdPrefix, $prefixLength);
            if ($rating !== null) {
                return $rating;
            }
        }

        return $this->findRatingInLines([$remainder], $imdbId, $imdbIdPrefix, $prefixLength);
    }

    /** @param resource $handle */
    private function skipHeaderRow($handle) : void
    {
        fgets($handle);
    }

    private function splitChunkIntoCompleteLines(string $chunk, string $remainder) : array
    {
        $chunk = $remainder . $chunk;
        $lastNewlinePos = strrpos($chunk, "\n");

        if ($lastNewlinePos === false) {
            return [[], $chunk];
        }

        $newRemainder = substr($chunk, $lastNewlinePos + 1);
        $completeChunk = substr($chunk, 0, $lastNewlinePos);
        $lines = explode("\n", $completeChunk);

        return [$lines, $newRemainder];
    }
}
