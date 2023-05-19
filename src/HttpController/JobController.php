<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
use Twig\Environment;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LetterboxdCsvValidator $letterboxdImportHistoryFileValidator,
        private readonly Environment $twig,
        private readonly SessionWrapper $sessionWrapper,
        private readonly string $appStorageDirectory,
    ) {
    }

    public function purgeAllJobs() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->jobQueueApi->purgeAllJobs();

        return Response::createSeeOther('/settings/server/jobs');
    }

    public function purgeProcessedJobs() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->jobQueueApi->purgeProcessedJobs();

        return Response::createSeeOther('/settings/server/jobs');
    }

    public function scheduleLetterboxdDiaryImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['diaryCsv']['tmp_name']) === true) {
            throw new RuntimeException('Missing ratings csv file');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-diary-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['diaryCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidDiaryCsv($targetFile) === false) {
            $this->sessionWrapper->set('letterboxdDiaryImportFileInvalid', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $this->jobQueueApi->addLetterboxdImportHistoryJob($userId, $targetFile);

        $this->sessionWrapper->set('letterboxdDiarySyncSuccessful', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleLetterboxdRatingsImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['ratingsCsv']['tmp_name']) === true) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileMissing', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['ratingsCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidRatingsCsv($targetFile) === false) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileInvalid', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $this->jobQueueApi->addLetterboxdImportRatingsJob($userId, $targetFile);

        $this->sessionWrapper->set('letterboxdRatingsSyncSuccessful', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleTraktHistorySync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->jobQueueApi->addTraktImportHistoryJob($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('scheduledTraktHistoryImport', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function scheduleTraktRatingsSync() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->jobQueueApi->addTraktImportRatingsJob($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('scheduledTraktRatingsImport', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
