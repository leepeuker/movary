<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\PlayedEntryDto;
use Movary\HttpController\Api\Dto\PlayedEntryDtoList;

class PlayedResponseMapper
{
    public function __construct(private readonly MovieResponseMapper $movieResponseMapper)
    {
    }

    public function mapPlayedEntries(array $playedEntriesData) : PlayedEntryDtoList
    {
        $playedEntries = PlayedEntryDtoList::create();

        foreach ($playedEntriesData as $playedEntryData) {
            $playedEntry = $this->mapPlayedEntry($playedEntryData);

            $playedEntries->add($playedEntry);
        }

        return $playedEntries;
    }

    private function mapPlayedEntry(array $playedEntryData) : PlayedEntryDto
    {
        $movie = $this->movieResponseMapper->mapMovie($playedEntryData);

        return PlayedEntryDto::create($movie);
    }
}
