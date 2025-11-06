<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\JobType;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LetterboxdCsvValidator $letterboxdImportHistoryFileValidator,
        private readonly SessionWrapper $sessionWrapper,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly string $appStorageDirectory,
    ) {
    }

    public function getJobs(Request $request) : Response
    {
        $parameters = $request->getGetParameters();

        $jobType = JobType::createFromString($parameters['type']);

        $jobs = $this->jobQueueApi->find($this->authenticationService->getCurrentUserId(), $jobType);

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

    public function scheduleJellyfinExportHistory() : Response
    {
        $currentUserId = $this->authenticationService->getCurrentUserId();

        $this->jobQueueApi->addJellyfinExportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleJellyfinImportHistory() : Response
    {
        $currentUserId = $this->authenticationService->getCurrentUserId();

        $this->jobQueueApi->addJellyfinImportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleLetterboxdDiaryImport(Request $request) : Response
    {
        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['diaryCsv']['tmp_name']) === true) {
            throw new RuntimeException('Missing ratings csv file');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-diary-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['diaryCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidDiaryCsv($targetFile) === false) {
            $this->sessionWrapper->set('letterboxdDiaryImportFileInvalid', true);

            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $this->jobQueueApi->addLetterboxdImportHistoryJob($userId, $targetFile);

        $this->sessionWrapper->set('letterboxdDiarySyncSuccessful', true);

        return Response::createSeeOther(
            $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/settings/integrations/letterboxd'),
            ),
        );
    }

    public function scheduleLetterboxdRatingsImport(Request $request) : Response
    {
        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['ratingsCsv']['tmp_name']) === true) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileMissing', true);

            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['ratingsCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidRatingsCsv($targetFile) === false) {
            $this->sessionWrapper->set('letterboxdRatingsImportFileInvalid', true);

            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $this->jobQueueApi->addLetterboxdImportRatingsJob($userId, $targetFile);

        $this->sessionWrapper->set('letterboxdRatingsSyncSuccessful', true);

        return Response::createSeeOther(
            $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/settings/integrations/letterboxd'),
            ),
        );
    }

    public function schedulePlexWatchlistImport() : Response
    {
        $currentUser = $this->authenticationService->getCurrentUser();

        $this->jobQueueApi->addPlexImportWatchlistJob($currentUser->getId());

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
