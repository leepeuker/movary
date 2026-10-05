<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\JobController;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobController::class)]
#[AllowMockObjectsWithoutExpectations]
class JobControllerTest extends TestCase
{
    private JobQueueApi&MockObject $jobQueueApiMock;

    private JobController $subject;

    protected function setUp() : void
    {
        $this->jobQueueApiMock = $this->createMock(JobQueueApi::class);
        $this->subject = new JobController(
            $this->createMock(Authentication::class),
            $this->jobQueueApiMock,
            $this->createMock(LetterboxdCsvValidator::class),
            $this->createMock(SessionWrapper::class),
            $this->createMock(ApplicationUrlService::class),
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
}
