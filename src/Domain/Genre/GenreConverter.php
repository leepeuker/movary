<?php declare(strict_types=1);

namespace Movary\Domain\Genre;

class GenreConverter
{
    public function convertGenreIdToHumanReadableText(string $genreId) : string
    {
        return match ($genreId) {
            '0' => 'Unknown',
            '1' => 'Female',
            '2' => 'Male',
            '3' => 'Non binary',
            default => throw new \RuntimeException('Unknown gender id: ' . $genreId)
        };
    }
}
