<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\HistoryEntryDto;
use Movary\HttpController\Api\Dto\HistoryEntryDtoList;
use Movary\ValueObject\Date;

class HistoryResponseMapper
{
    public function __construct(private readonly MovieResponseMapper $movieResponseMapper)
    {
    }

    public function mapHistoryEntries(array $historyEntriesData) : HistoryEntryDtoList
    {
        $historyEntries = HistoryEntryDtoList::create();

        foreach ($historyEntriesData as $historyEntryData) {
            $historyEntry = $this->mapHistoryEntry($historyEntryData);

            $historyEntries->add($historyEntry);
        }

        return $historyEntries;
    }

    private function mapHistoryEntry(array $historyEntryData) : HistoryEntryDto
    {
        $movie = $this->movieResponseMapper->mapMovie($historyEntryData);
        $watchedAt = Date::createFromString($historyEntryData['watched_at']);

        return HistoryEntryDto::create($movie, $watchedAt);
    }
}
