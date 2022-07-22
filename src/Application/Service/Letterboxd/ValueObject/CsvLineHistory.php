<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd\ValueObject;

use Movary\ValueObject\Date;

class CsvLineHistory
{
    private function __construct(
        private readonly string $name,
        private readonly string $letterboxdUri,
        private readonly Date $date,
    ) {
    }

    public static function createFromCsvLine(array $csvLine) : self
    {
        return new self($csvLine['Name'], $csvLine['Letterboxd URI'], Date::createFromString($csvLine['Date']));
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    public function getLetterboxdUri() : string
    {
        return $this->letterboxdUri;
    }

    public function getName() : string
    {
        return $this->name;
    }
}
