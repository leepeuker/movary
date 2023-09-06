<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\WatchlistEntryDto;
use Movary\HttpController\Api\Dto\WatchlistEntryDtoList;
use Movary\ValueObject\DateTime;

class WatchlistResponseMapper
{
    public function __construct(private readonly MovieResponseMapper $movieResponseMapper)
    {
    }

    public function mapWatchlistEntries(array $watchlistEntriesData) : WatchlistEntryDtoList
    {
        $watchlistEntries = WatchlistEntryDtoList::create();

        foreach ($watchlistEntriesData as $watchlistEntryData) {
            $watchlistEntry = $this->mapWatchlistEntry($watchlistEntryData);

            $watchlistEntries->add($watchlistEntry);
        }

        return $watchlistEntries;
    }

    private function mapWatchlistEntry(array $watchlistEntryData) : WatchlistEntryDto
    {
        $movie = $this->movieResponseMapper->mapMovie($watchlistEntryData);
        $addedAt = DateTime::createFromString($watchlistEntryData['added_at']);

        return WatchlistEntryDto::create($movie, $addedAt);
    }
}
