<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;

class ImportRatingsFileValidator
{
    public function isValid(string $targetFile) : bool
    {
        $watchDates = Reader::createFromPath($targetFile);
        $watchDates->setHeaderOffset(0);

        foreach ($watchDates->getRecords() as $watchDate) {
            if (empty($watchDate['Rating']) === true || empty($watchDate['Letterboxd URI']) === true || empty($watchDate['Name']) === true) {
                return false;
            }
        }

        return true;
    }
}
