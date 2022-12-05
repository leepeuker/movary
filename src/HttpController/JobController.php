<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Letterboxd\ImportHistoryFileValidator;
use Movary\Service\Letterboxd\ImportRatingsFileValidator;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly ImportHistoryFileValidator $letterboxdImportHistoryFileValidator,
        private readonly ImportRatingsFileValidator $letterboxdImportRatingsFileValidator,
        private readonly Environment $twig,
    ) {
    }

    public function purgeAllJobs() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->jobQueueApi->purgeAllJobs();

        return Response::createFoundRedirect('/job-queue');
    }

    public function purgeProcessedJobs() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->jobQueueApi->purgeProcessedJobs();

        return Response::createFoundRedirect('/job-queue');
    }

    public function renderQueuePage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $jobs = $this->jobQueueApi->fetchJobsForStatusPage($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/job-queue.html.twig',
                ['jobs' => $jobs],
            ),
        );
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

        if ($this->letterboxdImportHistoryFileValidator->isValid($targetFile) === false) {
            $_SESSION['letterboxdHistoryImportFileInvalid'] = true;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $this->jobQueueApi->addLetterboxdImportHistoryJob($userId, $targetFile);

        $_SESSION['letterboxdHistorySyncSuccessful'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleLetterboxdRatingsImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['ratingsCsv']['tmp_name']) === true) {
            $_SESSION['letterboxdRatingsImportFileMissing'] = true;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = __DIR__ . '/../../tmp/letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['ratingsCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportRatingsFileValidator->isValid($targetFile) === false) {
            $_SESSION['letterboxdRatingsImportFileInvalid'] = true;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $this->jobQueueApi->addLetterboxdImportRatingsJob($userId, $targetFile);

        $_SESSION['letterboxdRatingsSyncSuccessful'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleTraktHistorySync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->jobQueueApi->addTraktImportHistoryJob($this->authenticationService->getCurrentUserId());

        $_SESSION['scheduledTraktHistoryImport'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleTraktRatingsSync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->jobQueueApi->addTraktImportRatingsJob($this->authenticationService->getCurrentUserId());

        $_SESSION['scheduledTraktRatingsImport'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
