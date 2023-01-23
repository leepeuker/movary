<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Util\Json;
use Movary\Service\Netflix\ImportNetflixActivity;
use Psr\Log\LoggerInterface;

class NetflixController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
        private readonly TmdbApi $tmdbapi,
        private readonly ImportNetflixActivity $importActivity
    ){}

    /**
     * importNetflixActivity receives a CSV file with all the Netflix activity history and tries to process this. 
     * It filters the movies out with regex patterns, and then compiles an array of all the movie items.
     *
     * @param Request $request the CSV file containing the Netflix data
     * @return Response HTTP response with either an error code or JSON object containing the TMDB results 
     */
    public function processNetflixActivity(Request $request) : Response
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
        $rows = $this->importActivity->parseNetflixCSV($csv['tmp_name']);
        if($rows != false) {
            foreach($rows as $row) {
                $date = date_parse_from_format('d/m/Y', $row['Date']);
                $data = $this->importActivity->checkMediaData($row['Title']);
                
                if($data != false) {
                    if($data['type'] == 'Movie') {
                        $search = $this->tmdbapi->searchMovie($data['movieName']);
                        $searchresults[$data['movieName']] = [
                            'result' => $search[0] ?? 'Unknown',
                            'date' => $date,
                            'originalname' => $data['movieName']
                        ];
                        $this->logger->info('Item is a movie: ' . $data['movieName']);
                    }
                }
            }
            $jsonresponse = Json::encode($searchresults);
            return Response::createJson($jsonresponse);
        } else {
            return Response::createBadRequest();
        }
    }

    /**
     * searchTMDB receives an HTTP POST request and searches for the TMDB item. It returns a JSON object with all the results from TMDB
     *
     * @param Request $request The HTTP POST request
     * @return Response the JSON object with the TMDB data
     */
    public function searchTMDB(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $jsondata = $request->getBody();
        $input = Json::decode($jsondata);
        $tmdbresults = $this->tmdbapi->searchMovie($input['query']);
        $response = Json::encode($tmdbresults);
        return Response::createJson($response);
    }

    /**
     * importNetflixData receives an HTTP POST request containing an array with TMDB items matches with Netflix data. It imports this data with the importNetflixActivity service
     *
     * @param Request $request The HTTP POST request
     * @return Response 
     */
    public function importNetflixData(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $userId = $this->authenticationService->getCurrentUserId();
        $items = Json::decode($request->getBody());
        foreach($items as $item) {

        }
    }
}