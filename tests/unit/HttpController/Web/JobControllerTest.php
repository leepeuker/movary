<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\JobController;
use Movary\JobQueue\JobQueueApi;
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

    private LetterboxdCsvValidator&MockObject $letterboxdCsvValidatorMock;

    private JobController $subject;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->jobQueueApiMock = $this->createMock(JobQueueApi::class);
        $this->letterboxdCsvValidatorMock = $this->createMock(LetterboxdCsvValidator::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new JobController(
            $this->authenticationMock,
            $this->jobQueueApiMock,
            $this->letterboxdCsvValidatorMock,
            $this->flashMessageServiceMock,
            $this->applicationUrlServiceMock,
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

    /** @param non-empty-string $jobMethod */
    #[DataProvider('provideTraktImports')]
    public function testScheduleTraktImportAddsMessage(string $jobMethod, FlashMessage $message) : void
    {
        $this->authenticationMock->method('getCurrentUserId')->willReturn(42);
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
        $this->authenticationMock->method('getCurrentUserId')->willReturn(42);
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
        $this->authenticationMock->method('getCurrentUserId')->willReturn(42);
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
