<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Movary\Util\File;
use Movary\Util\Gzip;
use Psr\Log\LoggerInterface;
use RuntimeException;

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

    public function downloadRatings(string $ratingsFile) : void
    {
        $compressedFile = $ratingsFile . '.gz';
        $tempFile = $compressedFile . '.tmp';

        if ($this->shouldDownload($ratingsFile) === false) {
            return;
        }

        try {
            $response = $this->httpClient->request('GET', self::URL_RATINGS, [
                'sink' => $tempFile,
                'timeout' => 300,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Failed to download IMDb ratings file: HTTP ' . $response->getStatusCode());
            }

            $this->fileUtil->rename($tempFile, $compressedFile);

            $this->logger->debug('IMDb: Ratings file downloaded successfully');

            $this->gzipUtil->extract($compressedFile, $ratingsFile);

            $this->logger->debug('IMDb: Ratings file extracted successfully');

            $this->fileUtil->deleteFile($compressedFile);
        } catch (Exception $e) {
            if ($this->fileUtil->fileExists($tempFile) === true) {
                $this->fileUtil->deleteFile($tempFile);
            }

            $this->logger->error('IMDb: Failed to download/extract ratings file', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function shouldDownload(string $ratingsFile) : bool
    {
        if ($this->fileUtil->fileExists($ratingsFile) === false) {
            $this->logger->debug('IMDb: Ratings file does not exist, downloading fresh copy');

            return true;
        }

        $modificationTime = filemtime($ratingsFile);

        if ($modificationTime === false) {
            $this->logger->warning('IMDb: Cannot determine file age, downloading fresh copy');

            return true;
        }

        $fileAge = time() - $modificationTime;
        $hoursOld = round($fileAge / 3600, 1);

        if ($fileAge > self::TWENTY_FOUR_HOURS_IN_SECONDS) {
            $this->logger->debug('IMDb: Ratings file is older than 24 hours (' . $hoursOld . ' hours), downloading fresh copy');

            return true;
        }

        $this->logger->debug('IMDb: Ratings file is less than 24 hours old (' . $hoursOld . ' hours), using existing file');

        return false;
    }
}
