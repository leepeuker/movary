<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\JobType;
use RuntimeException;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LetterboxdCsvValidator $letterboxdImportHistoryFileValidator,
        private readonly SessionWrapper $sessionWrapper,
        private readonly string $appStorageDirectory,
    ) {
    }

    public function getJobs(Request $request) : Response
    {
        $parameters = $request->getGetParameters();

        $jobType = JobType::createFromString($parameters['type']);
        $token = $this->authenticationService->getUserIdByApiToken($request);
        if(empty($token)) {
            return Response::createUnauthorized();
        }

        $jobs = $this->jobQueueApi->find($token, $jobType);

        return Response::createJson(Json::encode($jobs));
    }

    public function purgeAllJobs() : Response
    {
        $this->jobQueueApi->purgeAllJobs();

        return Response::createNoContent();
    }

    public function purgeProcessedJobs() : Response
    {
        $this->jobQueueApi->purgeProcessedJobs();

        return Response::createNoContent();
    }

    public function scheduleLetterboxdDiaryImport(Request $request) : Response
    {
        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['letterboxdDiaryCsv']['tmp_name']) === true) {
            return Response::createBadRequest(
                Json::encode([
                    "error" => "missingRatingsCsvFile",
                    "message" => "No CSV file has been uploaded"
                ])
            );
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-diary-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['letterboxdDiaryCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidDiaryCsv($targetFile) === false) {
            return Response::createBadRequest(
                Json::encode([
                    "error" => "letterboxdDiaryImportFileInvalid",
                    "message" => "Invalid Letterboxd diary has been uploaded"
                ])
            );
        }

        $this->jobQueueApi->addLetterboxdImportHistoryJob($userId, $targetFile);

        return Response::createNoContent();
    }

    public function scheduleLetterboxdRatingsImport(Request $request) : Response
    {
        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['letterboxdRatingsCsv']['tmp_name']) === true) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileMissing', true);

            return Response::createBadRequest(
                Json::encode([
                    "error" => "letterboxdRatingsImportFileMissing",
                    "message" => "No file has been uploaded"
                ])
            );
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['letterboxdRatingsCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidRatingsCsv($targetFile) === false) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileInvalid', true);

            return Response::createBadRequest(
                Json::encode([
                    "error" => "letterboxdRatingsImportFileInvalid",
                    "message" => "Invalid Letterboxd ratings has been uploaded"
                ])
            );
        }

        $this->jobQueueApi->addLetterboxdImportRatingsJob($userId, $targetFile);

        return Response::createNoContent();
    }

    public function schedulePlexWatchlistImport() : Response
    {
        $currentUser = $this->authenticationService->getCurrentUser();

        $this->jobQueueApi->addPlexImportWatchlistJob($currentUser->getId());

        return Response::createNoContent();
    }

    public function scheduleJellyfinImportHistory() : Response
    {
        $currentUserId = $this->authenticationService->getCurrentUserId();

        $this->jobQueueApi->addJellyfinImportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleJellyfinExportHistory() : Response
    {
        $currentUserId = $this->authenticationService->getCurrentUserId();

        $this->jobQueueApi->addJellyfinExportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleTraktHistorySync() : Response
    {
        $this->jobQueueApi->addTraktImportHistoryJob($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('scheduledTraktHistoryImport', true);

        return Response::createNoContent();
    }

    public function scheduleTraktRatingsSync() : Response
    {
        $this->jobQueueApi->addTraktImportRatingsJob($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('scheduledTraktRatingsImport', true);

        return Response::createNoContent();
    }
}
