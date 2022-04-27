<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Letterboxd\SyncRatings;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;

class Letterboxd
{
    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly LoggerInterface $logger
    ) {
    }

    public function uploadRatingCsv(Request $httpRequest) : Response
    {
        $files = $httpRequest->getFileParameters();

        if ($files['letterboxedRating']['tmp_name'] === false) {
            throw new \RuntimeException('Uploaded csv missing in request.');
        }

        try {
            $this->syncRatings->execute($files['letterboxedRating']['tmp_name']);
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete letterboxd sync.', ['exception' => $t]);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            ['Location' => $_SERVER['HTTP_REFERER']]
        );
    }
}
