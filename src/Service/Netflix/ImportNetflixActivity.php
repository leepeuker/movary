<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Movary\Domain\User\UserApi;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Psr\Log\LoggerInterface;
use Exception;

class ImportNetflixActivity
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
        private readonly TmdbApi $tmdbapi
    ){}


    /**
     * parseNetflixCSV receives the layout of a Netflix viewing history CSV file and returns them as an associative array
     * @param string $filepath the filepath to the CSV file
     * @return array | bool
     */
    public function parseNetflixCSV(string $filepath) : Array | bool
    {
        try {
            $rows = array_map('str_getcsv', file($filepath));
            $header = array_shift($rows);
            $csv = array();
            foreach($rows as $row) {
                $csv[] = array_combine($header, $row);
            }
            return $csv;
        } catch (Exception $e) {
            $this->logger->warning('Netflix Viewing Activity not readable', ['exception' => $e]);
            return false;
        }
    }

    /**
     * checkMediaData receives the title from the Netflix CSV file and tries to find out whether it's a show or a movie and what the show's name, season and episode is
     * 
     * @param  string $title The title to parse
     * @return array | bool
     */
    public function checkMediaData(string $title) : Array | bool
    {
        $mediadata = null;        
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
            if(($result = $this->checkpattern($tvpatterns[$i], $title)) != false) {
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
        } else if(trim($title) == "") {
            return false;
        }
        return $mediadata;
    }

    /**
     * checkpattern receives a pattern to match and tries to parse the show name, season and episode title
     * 
     * @param string $pattern the pattern to use
     * @param string $input the input to match it with
     * @return array | bool
     * 
     */
    public function checkpattern(string $pattern, string $input) : Array | bool
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
            return false;
        }
    }
}