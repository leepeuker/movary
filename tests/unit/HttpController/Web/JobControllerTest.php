<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Web\JobController;
use Movary\HttpController\Web\Mapper\JobScheduleRequestMapper;
use Movary\HttpController\Web\Mapper\TmdbSyncJobRequestMapper;
use Movary\HttpController\Web\Mapper\TmdbImageCacheJobRequestMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobScheduleApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobController::class)]
#[AllowMockObjectsWithoutExpectations]
class JobControllerTest extends TestCase
{
    private ApplicationUrlService&MockObject $applicationUrlServiceMock;

    private Authentication&MockObject $authenticationMock;

    private FlashMessageService&MockObject $flashMessageServiceMock;

    private JobQueueApi&MockObject $jobQueueApiMock;

    private JobScheduleApi&MockObject $jobScheduleApiMock;

    private LetterboxdCsvValidator&MockObject $letterboxdCsvValidatorMock;

    private JobController $subject;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->jobQueueApiMock = $this->createMock(JobQueueApi::class);
        $this->jobScheduleApiMock = $this->createMock(JobScheduleApi::class);
        $this->letterboxdCsvValidatorMock = $this->createMock(LetterboxdCsvValidator::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new JobController(
            $this->authenticationMock,
            $this->jobQueueApiMock,
            $this->letterboxdCsvValidatorMock,
            $this->flashMessageServiceMock,
            $this->applicationUrlServiceMock,
            new TmdbSyncJobRequestMapper(),
            new TmdbImageCacheJobRequestMapper(),
            new JobScheduleRequestMapper(),
            $this->jobScheduleApiMock,
            '/tmp/',
        );
    }

    public static function provideDeleteResults() : array
    {
        return [
            'deleted' => [true, 204],
            'not found' => [false, 404],
        ];
    }

    #[DataProvider('provideDeleteResults')]
    public function testDeleteJobReturnsResultStatus(bool $deleted, int $expectedStatus) : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['jobId' => '7']);
        $this->jobQueueApiMock->expects(self::once())->method('deleteJob')->with(7)->willReturn($deleted);

        self::assertSame($expectedStatus, $this->subject->deleteJob($request)->getStatusCode()->getCode());
    }

    public function testDeleteAllJobSchedulesReturnsNoContent() : void
    {
        $this->jobScheduleApiMock->expects(self::once())->method('deleteAll');

        self::assertSame(204, $this->subject->deleteAllJobSchedules()->getStatusCode()->getCode());
    }

    public function testUpdateJobScheduleValidatesAndPersistsEditableFields() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['scheduleId' => '9']);
        $request->method('getPostParameters')->willReturn([
            'jobType' => 'tmdb_person_sync',
            'scheduleMode' => 'recurring',
            'cronExpression' => '*/5 * * * *',
            'maxAgeInHours' => '48',
            'threshold' => '100',
            'ids' => '7, 8',
            'onlyNeverSynced' => '1',
            'enabled' => '0',
        ]);
        $this->jobScheduleApiMock
            ->expects(self::once())
            ->method('update')
            ->with(
                9,
                self::callback(static fn($jobType) => (string)$jobType === 'tmdb_person_sync'),
                self::callback(static fn($configuration) =>
                    (string)$configuration->getCronExpression() === '*/5 * * * *'),
                [
                    'ids' => [7, 8],
                    'maxAgeInHours' => 48,
                    'threshold' => 100,
                    'onlyNeverSynced' => true,
                ],
                false,
            )
            ->willReturn(true);

        self::assertSame(204, $this->subject->updateJobSchedule($request)->getStatusCode()->getCode());
    }

    public function testUpdateJobScheduleRejectsUnsupportedJobType() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn(['jobType' => 'trakt_import_history']);
        $this->jobScheduleApiMock->expects(self::never())->method('update');

        $response = $this->subject->updateJobSchedule($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('{"error":"This job type is not supported."}', $response->getBody());
    }

    /** @param non-empty-string $jobMethod */
    #[DataProvider('provideTraktImports')]
    public function testScheduleTraktImportAddsMessage(string $jobMethod, FlashMessage $message) : void
    {
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock->expects(self::once())->method($jobMethod)->with(42);
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($message);

        $response = match ($message) {
            FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED => $this->subject->scheduleTraktHistorySync(),
            FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED => $this->subject->scheduleTraktRatingsSync(),
            default => self::fail('Unexpected Trakt message'),
        };

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    public function testMissingLetterboxdRatingsFileDoesNotAddDeadMessage() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getFileParameters')->willReturn([]);
        $this->flashMessageServiceMock->expects(self::never())->method('add');
        $this->jobQueueApiMock->expects(self::never())->method('addLetterboxdImportRatingsJob');
        $this->expectLetterboxdRedirect();

        $response = $this->subject->scheduleLetterboxdRatingsImport($request);

        self::assertSame(303, $response->getStatusCode()->getCode());
    }

    /**
     * @param non-empty-string $controllerMethod
     * @param non-empty-string $jobApiMethod
     */
    #[DataProvider('provideTmdbSyncTypes')]
    public function testScheduleTmdbSyncCreatesWaitingJob(
        string $controllerMethod,
        string $jobApiMethod,
    ) : void {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'maxAgeInHours' => '48',
            'threshold' => '100',
            'ids' => '7, 8',
            'onlyNeverSynced' => '1',
        ]);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method($jobApiMethod . 'UnlessActive')
            ->with([7, 8], 48, 100, true, 42)
            ->willReturn(23);

        $response = $this->subject->$controllerMethod($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"jobId":23,"userId":42}', $response->getBody());
    }

    public function testScheduleTmdbSyncRejectsInvalidParameters() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn(['ids' => '7,nope']);
        $this->jobQueueApiMock->expects(self::never())->method('addTmdbMovieSyncJobUnlessActive');

        $response = $this->subject->scheduleTmdbMovieSync($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
    }

    public function testScheduleTmdbSyncRequiresConfirmationForUnboundedJob() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([]);
        $this->jobQueueApiMock->expects(self::never())->method('addTmdbMovieSyncJobUnlessActive');

        $response = $this->subject->scheduleTmdbMovieSync($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
    }

    public function testScheduleTmdbSyncAcceptsConfirmedUnboundedJob() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn(['confirmUnbounded' => '1']);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addTmdbMovieSyncJobUnlessActive')
            ->with([], null, null, false, 42)
            ->willReturn(23);

        $response = $this->subject->scheduleTmdbMovieSync($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }

    public function testScheduleTmdbSyncRejectsDuplicateActiveJob() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn(['threshold' => '100']);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addTmdbMovieSyncJobUnlessActive')
            ->willReturn(null);

        $response = $this->subject->scheduleTmdbMovieSync($request);

        self::assertSame(409, $response->getStatusCode()->getCode());
    }

    public function testScheduleTmdbSyncCreatesRecurringSchedule() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'threshold' => '100',
            'scheduleMode' => 'recurring',
            'cronExpression' => '0 * * * *',
        ]);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock->expects(self::never())->method('addTmdbMovieSyncJobUnlessActive');
        $this->jobScheduleApiMock
            ->expects(self::once())
            ->method('addTmdbSyncSchedule')
            ->with(
                self::callback(static fn($type) => (string)$type === 'tmdb_movie_sync'),
                self::callback(static fn($cronExpression) => (string)$cronExpression === '0 * * * *'),
                self::anything(),
                self::anything(),
                42,
            )
            ->willReturn(9);

        $response = $this->subject->scheduleTmdbMovieSync($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"scheduleId":9,"userId":42}', $response->getBody());
    }

    public function testScheduleImdbRatingsSyncCreatesWaitingJob() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'maxAgeInHours' => '48',
            'threshold' => '100',
            'ids' => '7, 8',
            'onlyNeverSynced' => '1',
        ]);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addImdbSyncJobUnlessActive')
            ->with([7, 8], 48, 100, true, 42)
            ->willReturn(23);

        $response = $this->subject->scheduleImdbRatingsSync($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"jobId":23,"userId":42}', $response->getBody());
    }

    public function testScheduleTmdbImageCacheRefreshCreatesRecurringSchedule() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'movieIds' => '7, 8',
            'personIds' => '9',
            'scheduleMode' => 'recurring',
            'cronExpression' => '0 3 * * *',
        ]);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobQueueApiMock->expects(self::never())->method('addTmdbImageCacheRefreshJobUnlessActive');
        $this->jobScheduleApiMock
            ->expects(self::once())
            ->method('addTmdbImageCacheRefreshSchedule')
            ->with(
                self::callback(static fn($cronExpression) => (string)$cronExpression === '0 3 * * *'),
                self::callback(static fn($parameters) => $parameters->getMovieIds() === [7, 8]
                    && $parameters->getPersonIds() === [9]),
                self::anything(),
                42,
            )
            ->willReturn(9);

        $response = $this->subject->scheduleTmdbImageCacheRefresh($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"scheduleId":9,"userId":42}', $response->getBody());
    }

    public function testScheduleTmdbImageCacheRefreshCreatesWaitingJob() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'movieIds' => '7, 8',
            'personIds' => '9',
        ]);
        $this->authenticationMock
            ->method('requireWebSession')
            ->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->jobScheduleApiMock->expects(self::never())->method('addTmdbImageCacheRefreshSchedule');
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addTmdbImageCacheRefreshJobUnlessActive')
            ->with(
                self::callback(static fn($parameters) => $parameters->getMovieIds() === [7, 8]
                    && $parameters->getPersonIds() === [9]),
                42,
            )
            ->willReturn(23);

        $response = $this->subject->scheduleTmdbImageCacheRefresh($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"jobId":23,"userId":42}', $response->getBody());
    }

    /**
     * @param non-empty-string $validatorMethod
     * @param non-empty-string $jobMethod
     */
    #[DataProvider('provideInvalidLetterboxdImports')]
    public function testInvalidLetterboxdFileAddsMessage(
        string $fileField,
        string $validatorMethod,
        string $jobMethod,
        FlashMessage $invalidMessage,
    ) : void {
        $request = $this->createLetterboxdRequest($fileField);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->letterboxdCsvValidatorMock->expects(self::once())->method($validatorMethod)->willReturn(false);
        $this->jobQueueApiMock->expects(self::never())->method($jobMethod);
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($invalidMessage);
        $this->expectLetterboxdRedirect();

        $response = $this->scheduleLetterboxdImport($fileField, $request);

        self::assertSame(303, $response->getStatusCode()->getCode());
    }

    /**
     * @param non-empty-string $validatorMethod
     * @param non-empty-string $jobMethod
     */
    #[DataProvider('provideValidLetterboxdImports')]
    public function testValidLetterboxdFileSchedulesImport(
        string $fileField,
        string $validatorMethod,
        string $jobMethod,
        FlashMessage $successMessage,
    ) : void {
        $request = $this->createLetterboxdRequest($fileField);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $targetFile = null;
        $this->letterboxdCsvValidatorMock
            ->expects(self::once())
            ->method($validatorMethod)
            ->willReturnCallback(static function (string $file) use (&$targetFile) : bool {
                $targetFile = $file;

                return true;
            });
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method($jobMethod)
            ->with(42, self::callback(static function (string $file) use (&$targetFile) : bool {
                return $file === $targetFile;
            }));
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($successMessage);
        $this->expectLetterboxdRedirect();

        $response = $this->scheduleLetterboxdImport($fileField, $request);

        self::assertSame(303, $response->getStatusCode()->getCode());
    }

    /** @return iterable<string, array{non-empty-string, FlashMessage}> */
    public static function provideTraktImports() : iterable
    {
        yield 'history' => ['addTraktImportHistoryJob', FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED];
        yield 'ratings' => ['addTraktImportRatingsJob', FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED];
    }

    /** @return iterable<string, array{string, non-empty-string, non-empty-string, FlashMessage}> */
    public static function provideInvalidLetterboxdImports() : iterable
    {
        yield 'diary' => [
            'diaryCsv',
            'isValidDiaryCsv',
            'addLetterboxdImportHistoryJob',
            FlashMessage::LETTERBOXD_DIARY_FILE_INVALID,
        ];
        yield 'ratings' => [
            'ratingsCsv',
            'isValidRatingsCsv',
            'addLetterboxdImportRatingsJob',
            FlashMessage::LETTERBOXD_RATINGS_FILE_INVALID,
        ];
    }

    /** @return iterable<string, array{string, non-empty-string, non-empty-string, FlashMessage}> */
    public static function provideValidLetterboxdImports() : iterable
    {
        yield 'diary' => [
            'diaryCsv',
            'isValidDiaryCsv',
            'addLetterboxdImportHistoryJob',
            FlashMessage::LETTERBOXD_DIARY_SYNC_SCHEDULED,
        ];
        yield 'ratings' => [
            'ratingsCsv',
            'isValidRatingsCsv',
            'addLetterboxdImportRatingsJob',
            FlashMessage::LETTERBOXD_RATINGS_SYNC_SCHEDULED,
        ];
    }

    /** @return iterable<string, array{non-empty-string, non-empty-string}> */
    public static function provideTmdbSyncTypes() : iterable
    {
        yield 'movie' => ['scheduleTmdbMovieSync', 'addTmdbMovieSyncJob'];
        yield 'person' => ['scheduleTmdbPersonSync', 'addTmdbPersonSyncJob'];
    }

    private function createLetterboxdRequest(string $fileField) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getFileParameters')->willReturn([$fileField => ['tmp_name' => '/tmp/upload.csv']]);

        return $request;
    }

    private function expectLetterboxdRedirect() : void
    {
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/settings/integrations/letterboxd'))
            ->willReturn('/movary/settings/integrations/letterboxd');
    }

    private function scheduleLetterboxdImport(string $fileField, Request $request) : Response
    {
        return match ($fileField) {
            'diaryCsv' => $this->subject->scheduleLetterboxdDiaryImport($request),
            'ratingsCsv' => $this->subject->scheduleLetterboxdRatingsImport($request),
            default => self::fail('Unexpected Letterboxd file field'),
        };
    }
}
