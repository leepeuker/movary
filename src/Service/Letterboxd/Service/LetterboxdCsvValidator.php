<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\Service;

use League\Csv\Reader;
use Movary\Api;

class LetterboxdCsvValidator
{
    public function isValidDiaryCsv(string $targetFile) : bool
    {
        $watchDates = Reader::createFromPath($targetFile);
        $watchDates->setHeaderOffset(0);

        foreach ($watchDates->getRecords() as $watchDate) {
            if (empty($watchDate['Name']) === true ||
                empty($watchDate['Letterboxd URI']) === true ||
                empty($watchDate['Watched Date']) === true) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function isValidRatingsCsv(string $targetFile) : bool
    {
        $watchDates = Reader::createFromPath($targetFile);
        $watchDates->setHeaderOffset(0);

        foreach ($watchDates->getRecords() as $watchDate) {
            if (empty($watchDate['Rating']) === true ||
                empty($watchDate['Letterboxd URI']) === true ||
                empty($watchDate['Name']) === true) {
                return false;
            }

            return true;
        }

        return false;
    }
}
