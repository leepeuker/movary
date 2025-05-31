<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\PlayedEntryDto;
use Movary\HttpController\Api\Dto\PlayedEntryDtoList;
use Movary\HttpController\Api\Dto\WatchDateDto;
use Movary\HttpController\Api\Dto\WatchDateDtoList;
use Movary\ValueObject\Date;

class PlayedResponseMapper
{
    public function __construct(private readonly MovieResponseMapper $movieResponseMapper)
    {
    }

    public function mapPlayedEntries(array $playedEntriesData, array $watchDatesData) : PlayedEntryDtoList
    {
        $playedEntries = PlayedEntryDtoList::create();

        foreach ($playedEntriesData as $playedEntryData) {
            $playedEntry = $this->mapPlayedEntry($playedEntryData, $watchDatesData);

            $playedEntries->add($playedEntry);
        }

        return $playedEntries;
    }

    private function mapPlayedEntry(array $playedEntryData, array $watchDatesData) : PlayedEntryDto
    {
        $movie = $this->movieResponseMapper->mapMovie($playedEntryData);
        $watchDates = $this->mapWatchDates($movie->getId(), $watchDatesData);

        return PlayedEntryDto::create($movie, $watchDates);
    }

    private function mapWatchDates(int $movieId, array $watchDatesData) : WatchDateDtoList
    {
        $watchDates = WatchDateDtoList::create();

        foreach ($watchDatesData[$movieId] as $watchDate => $watchDateData) {
            $watchDate = WatchDateDto::create(
                empty($watchDate) === false ? Date::createFromString($watchDate) : null,
                $watchDateData['plays'],
                $watchDateData['comment'],
                $watchDateData['locationId'],
            );

            $watchDates->add($watchDate);
        }

        return $watchDates;
    }
}
