<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Psr\Log\LoggerInterface;
use Exception;

class ImportNetflixActivity
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ){}


    /**
     * parseNetflixCSV receives the layout of a Netflix viewing history CSV file and returns them as an associative array
     * @psalm-suppress PossiblyInvalidArgument
     * @param string $filepath the filepath to the CSV file
     * @return array
     */
    public function parseNetflixCSV(string $filepath) : Array
    {
        try {
            if(is_bool(file($filepath))) {
                $this->logger->error('The file could not be opened');
                return [];
            }
            $rows = array_map('str_getcsv', file($filepath));
            $rows = array_slice($rows, 1);
            $csv = [];
            foreach($rows as $row) {
                $csv[] = array_combine(['Title', 'Date'], $row);
            }
            return $csv;
        } catch (Exception $e) {
            $this->logger->warning('Netflix Viewing Activity not readable', ['exception' => $e]);
            return [];
        }
    }

    /**
     * checkMediaData receives the title from the Netflix CSV file and tries to find out whether it's a show or a movie and what the show's name, season and episode is
     * 
     * @param  string $title The title to parse
     * @return array
     */
    public function checkMediaData(string $title) : Array
    {
        $mediadata = [];        
        $tvpatterns = [
            // check for a pattern TvShow : Season 1: EpisodeTitle
            // Example: Wednesday: Season 1: A Murder of Woes
            "(.+): .+ (\d{1,2}): (.*)",
            // Check for TvShow : Season 1 - Part A: EpisodeTitle
            "(.+): .+ (\d{1,2}) - .+: (.*)",
            // Check for TvShow : TvShow : Miniseries : EpisodeTitle
            "(.+): \w+: (.+)",
            // Check for TvShow: SeasonName : EpisodeTitle
            "(.+): (.+): (.+)",
            // Check for TvShow: EpisodeTitle
            "(.+): (.+)"
        ];

        for($i = 0; $i < count($tvpatterns); $i++) {
            if(($result = $this->checkpattern($tvpatterns[$i], $title)) != []) {
                $mediadata = $result;
                break;
            }
        }
        // The item is a movie
        if($mediadata == null && trim($title) != "") {
            $mediadata = [
                'movieName' => $title,
                'type' => 'Movie'
            ];
        } elseif(trim($title) == "") {
            return [];
        }
        return $mediadata;
    }

    /**
     * checkpattern receives a pattern to match and tries to parse the show name, season and episode title
     * 
     * @param string $pattern the pattern to use
     * @param string $input the input to match it with
     * @return array
     * 
     */
    public function checkpattern(string $pattern, string $input) : Array
    {
        $result = preg_match_all("/$pattern/m", $input, $matches);
        if($result != 0) {
            $showName = $matches[1];
            if(count($matches) > 3) {
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
        } else {
            return [];
        }
    }
}