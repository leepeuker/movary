<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\ValueObject;

class CsvLineRating
{
    private function __construct(
        private readonly string $name,
        private readonly string $letterboxdUri,
        private readonly ?int $rating,
    ) {
    }

    public static function createFromCsvLine(array $csvLine) : self
    {
        return new self($csvLine['Name'], $csvLine['Letterboxd URI'], empty($csvLine['Rating']) === true ? null : (int)$csvLine['Rating']);
    }

    public function getLetterboxdUri() : string
    {
        return $this->letterboxdUri;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getRating() : ?int
    {
        return $this->rating;
    }
}
