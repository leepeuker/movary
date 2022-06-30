<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Letterboxd\SyncRatings;
use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;

class Letterboxd
{
    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly LoggerInterface $logger,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function uploadRatingCsv(Request $httpRequest) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

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
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
