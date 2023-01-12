<?php declare(strict_types=1);

namespace Movary\HttpController;

use Exception;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;
use Movary\Api\Tmdb\TmdbApi;

class NetflixController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
        private readonly TmdbApi $tmdbapi
    ){}

    /**
     * importNetflixActivity
     *
     * @param Request $request
     * @return Response HTTP response with either an error code or JSON 
     */
    public function importNetflixActivity(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $searchresults = [];

        $files = $request->getFileParameters();
        if(empty($files)) {
            return Response::createBadRequest();
        }
        $csv = $files['netflixviewactivity'];
        if($csv['size'] == 0) {
            return Response::createBadRequest();
        }
        // finfo_open is way more reliable to detect MIME type than 'type' from the $_FILES variable
        // It does however require the magic module, which may be a pain to set up.
        // https://www.php.net/manual/en/function.finfo-open.php
        if($csv['type'] != "application/vnd.ms-excel" && $csv['type'] != 'text/csv') {
            return Response::createUnsupportedMediaType();
        }
        $rows = $this->parseNetflixCSV($csv['tmp_name']);
        if($rows != false) {
            foreach($rows as $row) {
                $date = date_parse_from_format('d/m/Y', $row['Date']);
                $data = $this->checkMediaData($row['Title']);
                
                if($data != false) {
                    if($data['type'] == 'Movie') {
                        $search = $this->tmdbapi->searchMovie($data['movieName']);
                        $searchresults[$data['movieName']] = [
                            'result' => $search[0] ?? 'Unknown',
                            'date' => $date,
                            'originalname' => $data['movieName']
                        ];
                        $this->logger->info('Item is a movie: ' . $data['movieName']);
                    } else if($data['type'] == 'Show') {
                        // Importing TV shows will be added after TV show support is added
                        $this->logger->info('Item is a TV show, skipping...');
                    }
                }
            }
            $jsonresponse = json_encode($searchresults);
            return Response::createJson($jsonresponse);
        } else {
            return Response::createBadRequest();
        }
    }

    /**
     * parseNetflixCSV receives the layout of a Netflix viewing history CSV file and returns them as an associative array
     * @param string $filepath the filepath to the CSV file
     * @return array | bool
     */
    private function parseNetflixCSV(string $filepath) : Array | bool
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
    private function checkMediaData(string $title) : Array | bool
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
    private function checkpattern(string $pattern, string $input) : Array | bool
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