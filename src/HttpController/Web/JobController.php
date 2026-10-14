<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use InvalidArgumentException;
use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\Mapper\TmdbSyncJobRequestMapper;
use Movary\HttpController\Web\Mapper\JobScheduleRequestMapper;
use Movary\HttpController\Web\Mapper\TmdbImageCacheJobRequestMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobScheduleApi;
use Movary\JobQueue\TmdbSyncJobParameters;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\JobType;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;

class JobController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LetterboxdCsvValidator $letterboxdImportHistoryFileValidator,
        private readonly FlashMessageService $flashMessageService,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly TmdbSyncJobRequestMapper $tmdbSyncJobRequestMapper,
        private readonly TmdbImageCacheJobRequestMapper $tmdbImageCacheJobRequestMapper,
        private readonly JobScheduleRequestMapper $jobScheduleRequestMapper,
        private readonly JobScheduleApi $jobScheduleApi,
        private readonly string $appStorageDirectory,
    ) {
    }

    public function getJobs(Request $request) : Response
    {
        $parameters = $request->getGetParameters();

        $jobType = JobType::createFromString($parameters['type']);

        $jobs = $this->jobQueueApi->find($this->authenticationService->requireWebSession()->getUserId(), $jobType);

        return Response::createJson(Json::encode($jobs));
    }

    public function deleteJob(Request $request) : Response
    {
        $jobId = (int)$request->getRouteParameters()['jobId'];

        if ($this->jobQueueApi->deleteJob($jobId) === false) {
            return Response::createNotFound();
        }

        return Response::createNoContent();
    }

    public function deleteJobSchedule(Request $request) : Response
    {
        $scheduleId = (int)$request->getRouteParameters()['scheduleId'];

        return $this->jobScheduleApi->delete($scheduleId)
            ? Response::createNoContent()
            : Response::createNotFound();
    }

    public function deleteAllJobSchedules() : Response
    {
        $this->jobScheduleApi->deleteAll();

        return Response::createNoContent();
    }

    public function updateJobSchedule(Request $request) : Response
    {
        $requestParameters = $request->getPostParameters();

        try {
            $jobType = $this->mapSchedulableJobType($requestParameters['jobType'] ?? null);
            $configuration = $this->jobScheduleRequestMapper->map($request);
            if ($configuration === null) {
                throw new InvalidArgumentException('Schedule mode is invalid.');
            }
            $parameters = $this->mapJobScheduleParameters($request, $jobType);
            $enabled = $this->mapJobScheduleEnabled($requestParameters['enabled'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return $this->createJobJsonResponse(['error' => $exception->getMessage()], StatusCode::createBadRequest());
        }

        $scheduleId = (int)$request->getRouteParameters()['scheduleId'];

        return $this->jobScheduleApi->update($scheduleId, $jobType, $configuration, $parameters, $enabled)
            ? Response::createNoContent()
            : Response::createNotFound();
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
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();

        $this->jobQueueApi->addJellyfinExportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleJellyfinImportHistory() : Response
    {
        $currentUserId = $this->authenticationService->requireWebSession()->getUserId();

        $this->jobQueueApi->addJellyfinImportMoviesJob($currentUserId);

        return Response::createNoContent();
    }

    public function scheduleLetterboxdDiaryImport(Request $request) : Response
    {
        $fileParameters = $request->getFileParameters();

        if (empty($fileParameters['diaryCsv']['tmp_name']) === true) {
            throw new RuntimeException('Missing ratings csv file');
        }

        $userId = $this->authenticationService->requireWebSession()->getUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-diary-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['diaryCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidDiaryCsv($targetFile) === false) {
            $this->flashMessageService->add(FlashMessage::LETTERBOXD_DIARY_FILE_INVALID);

            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $this->jobQueueApi->addLetterboxdImportHistoryJob($userId, $targetFile);

        $this->flashMessageService->add(FlashMessage::LETTERBOXD_DIARY_SYNC_SCHEDULED);

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
            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $userId = $this->authenticationService->requireWebSession()->getUserId();

        $targetFile = $this->appStorageDirectory . 'letterboxd-ratings-' . $userId . '-' . time() . '.csv';
        move_uploaded_file($fileParameters['ratingsCsv']['tmp_name'], $targetFile);

        if ($this->letterboxdImportHistoryFileValidator->isValidRatingsCsv($targetFile) === false) {
            $this->flashMessageService->add(FlashMessage::LETTERBOXD_RATINGS_FILE_INVALID);

            return Response::createSeeOther(
                $this->applicationUrlService->createApplicationUrl(
                    RelativeUrl::create('/settings/integrations/letterboxd'),
                ),
            );
        }

        $this->jobQueueApi->addLetterboxdImportRatingsJob($userId, $targetFile);

        $this->flashMessageService->add(FlashMessage::LETTERBOXD_RATINGS_SYNC_SCHEDULED);

        return Response::createSeeOther(
            $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/settings/integrations/letterboxd'),
            ),
        );
    }

    public function schedulePlexWatchlistImport() : Response
    {
        $userId = $this->authenticationService->requireWebSession()->getUserId();
        $this->jobQueueApi->addPlexImportWatchlistJob($userId);

        return Response::createNoContent();
    }

    public function scheduleTmdbMovieSync(Request $request) : Response
    {
        return $this->scheduleTmdbSync($request, JobType::createTmdbMovieSync());
    }

    public function scheduleImdbRatingsSync(Request $request) : Response
    {
        try {
            $parameters = $this->tmdbSyncJobRequestMapper->map($request);
            $scheduleConfiguration = $this->jobScheduleRequestMapper->map($request);
            if ($parameters->requiresUnboundedSyncConfirmation() === true &&
                $this->tmdbSyncJobRequestMapper->isUnboundedSyncConfirmed($request) === false
            ) {
                return $this->createJobJsonResponse(
                    ['error' => 'Confirmation is required for a sync without IDs or a maximum record count.'],
                    StatusCode::createBadRequest(),
                );
            }
        } catch (InvalidArgumentException $exception) {
            return $this->createJobJsonResponse(['error' => $exception->getMessage()], StatusCode::createBadRequest());
        }

        $userId = $this->authenticationService->requireWebSession()->getUserId();
        if ($scheduleConfiguration !== null) {
            $scheduleId = $this->jobScheduleApi->addImdbSyncSchedule(
                $scheduleConfiguration->getCronExpression(),
                $parameters,
                $scheduleConfiguration->getNextRunAt(),
                $userId,
            );

            return $this->createJobJsonResponse(['scheduleId' => $scheduleId, 'userId' => $userId]);
        }

        $jobId = $this->jobQueueApi->addImdbSyncJobUnlessActive(
            movieIds: $parameters->getIds(),
            maxAgeInHours: $parameters->getMaxAgeInHours(),
            threshold: $parameters->getThreshold(),
            onlyNeverSynced: $parameters->isOnlyNeverSynced(),
            userId: $userId,
        );
        if ($jobId === null) {
            return $this->createJobJsonResponse(
                ['error' => 'A waiting or in-progress job of this type already exists.'],
                StatusCode::createConflict(),
            );
        }

        return $this->createJobJsonResponse(['jobId' => $jobId, 'userId' => $userId]);
    }

    public function scheduleTmdbImageCacheRefresh(Request $request) : Response
    {
        try {
            $parameters = $this->tmdbImageCacheJobRequestMapper->map($request);
            $scheduleConfiguration = $this->jobScheduleRequestMapper->map($request);
        } catch (InvalidArgumentException $exception) {
            return $this->createJobJsonResponse(['error' => $exception->getMessage()], StatusCode::createBadRequest());
        }

        $userId = $this->authenticationService->requireWebSession()->getUserId();
        if ($scheduleConfiguration !== null) {
            $scheduleId = $this->jobScheduleApi->addTmdbImageCacheRefreshSchedule(
                $scheduleConfiguration->getCronExpression(),
                $parameters,
                $scheduleConfiguration->getNextRunAt(),
                $userId,
            );

            return $this->createJobJsonResponse(['scheduleId' => $scheduleId, 'userId' => $userId]);
        }

        $jobId = $this->jobQueueApi->addTmdbImageCacheRefreshJobUnlessActive($parameters, $userId);
        if ($jobId === null) {
            return $this->createJobJsonResponse(
                ['error' => 'A waiting or in-progress job of this type already exists.'],
                StatusCode::createConflict(),
            );
        }

        return $this->createJobJsonResponse(['jobId' => $jobId, 'userId' => $userId]);
    }

    public function scheduleTmdbPersonSync(Request $request) : Response
    {
        return $this->scheduleTmdbSync($request, JobType::createTmdbPersonSync());
    }

    public function scheduleTraktHistorySync() : Response
    {
        $this->jobQueueApi->addTraktImportHistoryJob($this->authenticationService->requireWebSession()->getUserId());

        $this->flashMessageService->add(FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED);

        return Response::createNoContent();
    }

    public function scheduleTraktRatingsSync() : Response
    {
        $this->jobQueueApi->addTraktImportRatingsJob($this->authenticationService->requireWebSession()->getUserId());

        $this->flashMessageService->add(FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED);

        return Response::createNoContent();
    }

    private function scheduleTmdbSync(Request $request, JobType $jobType) : Response
    {
        try {
            $parameters = $this->tmdbSyncJobRequestMapper->map($request);
            $scheduleConfiguration = $this->jobScheduleRequestMapper->map($request);
            if ($parameters->requiresUnboundedSyncConfirmation() === true &&
                $this->tmdbSyncJobRequestMapper->isUnboundedSyncConfirmed($request) === false
            ) {
                return $this->createJobJsonResponse(
                    ['error' => 'Confirmation is required for a sync without IDs or a maximum record count.'],
                    StatusCode::createBadRequest(),
                );
            }
        } catch (InvalidArgumentException $exception) {
            return $this->createJobJsonResponse(['error' => $exception->getMessage()], StatusCode::createBadRequest());
        }

        $userId = $this->authenticationService->requireWebSession()->getUserId();
        if ($scheduleConfiguration !== null) {
            $scheduleId = $this->jobScheduleApi->addTmdbSyncSchedule(
                $jobType,
                $scheduleConfiguration->getCronExpression(),
                $parameters,
                $scheduleConfiguration->getNextRunAt(),
                $userId,
            );

            return $this->createJobJsonResponse(['scheduleId' => $scheduleId, 'userId' => $userId]);
        }

        $jobId = $this->enqueueTmdbSync($jobType, $parameters, $userId);
        if ($jobId === null) {
            return $this->createJobJsonResponse(
                ['error' => 'A waiting or in-progress job of this type already exists.'],
                StatusCode::createConflict(),
            );
        }

        return $this->createJobJsonResponse(['jobId' => $jobId, 'userId' => $userId]);
    }

    private function enqueueTmdbSync(JobType $jobType, TmdbSyncJobParameters $parameters, int $userId) : ?int
    {
        if ($jobType->isOfTypeTmdbMovieSync() === true) {
            return $this->jobQueueApi->addTmdbMovieSyncJobUnlessActive(
                movieIds: $parameters->getIds(),
                maxAgeInHours: $parameters->getMaxAgeInHours(),
                threshold: $parameters->getThreshold(),
                onlyNeverSynced: $parameters->isOnlyNeverSynced(),
                userId: $userId,
            );
        }

        return $this->jobQueueApi->addTmdbPersonSyncJobUnlessActive(
            personIds: $parameters->getIds(),
            maxAgeInHours: $parameters->getMaxAgeInHours(),
            threshold: $parameters->getThreshold(),
            onlyNeverSynced: $parameters->isOnlyNeverSynced(),
            userId: $userId,
        );
    }

    private function mapSchedulableJobType(mixed $value) : JobType
    {
        if (is_string($value) === false || in_array($value, [
            'tmdb_movie_sync',
            'tmdb_person_sync',
            'tmdb_image_cache',
            'imdb_sync',
        ], true) === false) {
            throw new InvalidArgumentException('This job type is not supported.');
        }

        return JobType::createFromString($value);
    }

    private function mapJobScheduleParameters(Request $request, JobType $jobType) : array
    {
        if ($jobType->isOfTypeTmdbImageCache()) {
            $parameters = $this->tmdbImageCacheJobRequestMapper->map($request);

            return [
                'movieIds' => $parameters->getMovieIds(),
                'personIds' => $parameters->getPersonIds(),
            ];
        }

        $parameters = $this->tmdbSyncJobRequestMapper->map($request);
        if ($parameters->requiresUnboundedSyncConfirmation() === true &&
            $this->tmdbSyncJobRequestMapper->isUnboundedSyncConfirmed($request) === false
        ) {
            throw new InvalidArgumentException(
                'Confirmation is required for a sync without IDs or a maximum record count.',
            );
        }

        return [
            'ids' => $parameters->getIds(),
            'maxAgeInHours' => $parameters->getMaxAgeInHours(),
            'threshold' => $parameters->getThreshold(),
            'onlyNeverSynced' => $parameters->isOnlyNeverSynced(),
        ];
    }

    private function mapJobScheduleEnabled(mixed $value) : bool
    {
        if ($value !== '0' && $value !== '1') {
            throw new InvalidArgumentException('Enabled must be a boolean.');
        }

        return $value === '1';
    }

    private function createJobJsonResponse(array $body, ?StatusCode $statusCode = null) : Response
    {
        return Response::createJson(Json::encode($body), $statusCode);
    }
}
