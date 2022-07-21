<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
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

    public function scheduleLetterboxdHistoryImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['historyCsv']['tmp_name']) === true) {
            throw new \RuntimeException('Missing ratings csv file');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = __DIR__ . '/../../tmp/letterboxd-history-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['historyCsv']['tmp_name'], $targetFile);

        $this->workerService->addLetterboxdImportHistoryJob($userId, $targetFile);

        $_SESSION['letterboxdHistorySyncSuccessful'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function scheduleLetterboxdRatingsImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['ratingsCsv']['tmp_name']) === true) {
            throw new \RuntimeException('Missing ratings csv file');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = __DIR__ . '/../../tmp/letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['ratingsCsv']['tmp_name'], $targetFile);

        $this->workerService->addLetterboxdImportRatingsJob($userId, $targetFile);

        $_SESSION['letterboxdRatingsSyncSuccessful'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
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
