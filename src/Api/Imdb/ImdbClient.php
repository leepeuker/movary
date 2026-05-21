<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use GuzzleHttp\Client as HttpClient;
use Movary\Util\File;
use Movary\Util\Gzip;
use Psr\Log\LoggerInterface;

class ImdbClient
{
    private const int TWENTY_FOUR_HOURS_IN_SECONDS = 24 * 60 * 60;

    private const string URL_RATINGS = 'https://datasets.imdbws.com/title.ratings.tsv.gz';

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly File $fileUtil,
        private readonly Gzip $gzipUtil,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function downloadRatings(string $ratingsFile): void
    {
        $compressedFile = $ratingsFile . '.gz';

        if ($this->shouldDownload($compressedFile) === false) {
            return;
        }

        $response = $this->httpClient->request('GET', self::URL_RATINGS);

        $this->fileUtil->createFile(
            $compressedFile,
            $response->getBody()->getContents(),
        );

        $this->logger->debug('IMDb: Ratings file downloaded successfully');

        $this->gzipUtil->extract($compressedFile, $ratingsFile);
        $this->fileUtil->deleteFile($compressedFile);

        $this->logger->debug('IMDb: Ratings file extracted successfully');
    }

    private function shouldDownload(string $compressedFile): bool
    {
        if ($this->fileUtil->fileExists($compressedFile) === false) {
            $this->logger->debug('IMDb: Ratings file does not exist, downloading fresh copy');
            return true;
        }

        $fileAge = time() - filemtime($compressedFile);
        $hoursOld = round($fileAge / 3600, 1);

        if ($fileAge > self::TWENTY_FOUR_HOURS_IN_SECONDS) {
            $this->logger->debug('IMDb: Ratings file is older than 24 hours (' . $hoursOld . ' hours old), downloading fresh copy');
            return true;
        }

        $this->logger->debug('IMDb: Ratings file is less than 24 hours old (' . $hoursOld . ' hours old), using existing file');
        return false;
    }
}
