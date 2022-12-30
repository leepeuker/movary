<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\ValueObject;

use Movary\ValueObject\Date;

class CsvLineDiary
{
    private function __construct(
        private readonly string $name,
        private readonly string $letterboxdUri,
        private readonly Date $date,
    ) {
    }

    public static function createFromCsvLine(array $csvLine) : self
    {
        return new self($csvLine['Name'], $csvLine['Letterboxd URI'], Date::createFromString($csvLine['Watched Date']));
    }

    public function getLetterboxdDiaryEntryUri() : string
    {
        return $this->letterboxdUri;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getWatchedDate() : Date
    {
        return $this->date;
    }
}
