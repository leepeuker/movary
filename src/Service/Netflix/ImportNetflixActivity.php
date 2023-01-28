<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Exception;
use Psr\Log\LoggerInterface;

class ImportNetflixActivity
{
    private const SHOW_PATTERNS = [
        // Check for TvShow: Season 1: EpisodeTitle
        "(.+): .+ (\d{1,2}): (.*)",
        // Check for TvShow: Season 1 - Part A: EpisodeTitle
        "(.+): .+ (\d{1,2}) - .+: (.*)",
        // Check for TvShow: TvShow : Miniseries : EpisodeTitle
        "(.+): \w+: (.+)",
        // Check for TvShow: SeasonName : EpisodeTitle
        "(.+): (.+): (.+)",
        // Check for TvShow: EpisodeTitle
        "(.+): (.+)"
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function extractMediaDataFromTitle(string $title) : ?array
    {
        if (trim($title) === "") {
            return null;
        }

        foreach (self::SHOW_PATTERNS as $showPattern) {
            $showData = $this->extractShowDataFromTitleByPattern($title, $showPattern);

            if ($showData !== null) {
                return $showData;
            }
        }

        return [
            'movieName' => $title,
            'type' => 'Movie'
        ];
    }

    public function extractShowDataFromTitleByPattern(string $title, string $pattern) : ?array
    {
        $result = preg_match_all("/$pattern/m", $title, $matches);
        if ((int)$result < 1) {
            return null;
        }

        $showName = $matches[1];
        if (count($matches) > 3) {
            $seasonNumber = $matches[2];
            $episodeTitle = $matches[3];
        } else {
            $seasonNumber = 1;
            $episodeTitle = $matches[2];
        }

        return [
            'showName' => $showName,
            'seasonNumber' => $seasonNumber,
            'episodeTitle' => $episodeTitle,
            'type' => 'Show'
        ];
    }

    public function parseNetflixCsv(string $csvFilepath) : array
    {
        $csvData = [];

        try {
            $csvLinesAsStrings = file($csvFilepath);

            if ($csvLinesAsStrings === false) {
                $this->logger->error('The file could not be opened');

                return [];
            }

            $csvLinesAsArray = array_map('str_getcsv', $csvLinesAsStrings);
            $csvLinesAsArray = array_slice($csvLinesAsArray, 1);

            foreach ($csvLinesAsArray as $csvLine) {
                $csvData[] = array_combine(['Title', 'Date'], $csvLine);
            }
        } catch (Exception $e) {
            $this->logger->warning('Netflix Viewing Activity not readable', ['exception' => $e]);
        }

        return $csvData;
    }
}
