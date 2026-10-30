<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\ValueObject\Date;

class HistoryResponseMapper
{
    public function __construct(private readonly MovieResponseMapper $movieResponseMapper)
    {
    }

    public function mapPlayEntries(array $playEntriesData) : array
    {
        return array_map($this->mapPlayEntry(...), $playEntriesData);
    }

    public function mapPlayEntry(array $playEntryData) : array
    {
        return [
            'id' => (int)$playEntryData['playId'],
            'movie' => $this->movieResponseMapper->mapMovie($playEntryData),
            'watchedAt' => $playEntryData['watched_at'] === null ? null : Date::createFromString($playEntryData['watched_at']),
            'position' => (int)$playEntryData['position'],
            'comment' => $playEntryData['comment'],
            'locationId' => $playEntryData['locationId'] === null ? null : (int)$playEntryData['locationId'],
        ];
    }
}
