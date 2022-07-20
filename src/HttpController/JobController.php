<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\Worker\Service;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly Service $workerService
    ) {
    }

    public function scheduleTraktHistorySync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->workerService->addTraktHistorySyncJob($this->authenticationService->getCurrentUserId());

        $_SESSION['scheduledTraktHistorySync'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function scheduleTraktRatingsSync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->workerService->addTraktRatingsSyncJob($this->authenticationService->getCurrentUserId());

        $_SESSION['scheduledTraktRatingsSync'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
