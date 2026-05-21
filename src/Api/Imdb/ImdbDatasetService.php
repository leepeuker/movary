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

        // Skip header row
        fgetcsv($handle, 0, "\t");

        // Optimized linear search - read in chunks for better performance
        $imdbIdLength = strlen($imdbId);
        $bufferSize = 8192; // 8KB chunks
        
        while (!feof($handle)) {
            $chunk = fread($handle, $bufferSize);
            if ($chunk === false) {
                break;
            }
            
            // Split chunk into lines
            $lines = explode("\n", $chunk);
            
            // Process all lines except possibly the last incomplete one
            $lineCount = count($lines) - 1;
            for ($i = 0; $i < $lineCount; $i++) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }
                
                // Quick check: if line doesn't start with our IMDb ID prefix, skip
                if (strncmp($line, $imdbId, $imdbIdLength) !== 0) {
                    continue;
                }
                
                // Parse the line
                $row = str_getcsv($line, "\t");
                if (count($row) >= 3 && $row[0] === $imdbId) {
                    fclose($handle);

                    return ImdbRating::create((float)$row[1], (int)$row[2]);
                }
            }
        }

        fclose($handle);
        return null;
    }
}
