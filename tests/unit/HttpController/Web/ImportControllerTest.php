<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Web\ImportController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\ImportService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(ImportController::class)]
#[AllowMockObjectsWithoutExpectations]
class ImportControllerTest extends TestCase
{
    private Authentication|MockObject $authenticationMock;

    private FlashMessageService|MockObject $flashMessageServiceMock;

    private ImportService|MockObject $importServiceMock;

    private LoggerInterface|MockObject $loggerMock;

    private ImportController $subject;

    private ApplicationUrlService|MockObject $urlServiceMock;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->importServiceMock = $this->createMock(ImportService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->urlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new ImportController(
            $this->authenticationMock,
            $this->importServiceMock,
            $this->loggerMock,
            $this->flashMessageServiceMock,
            $this->urlServiceMock,
        );
    }

    #[DataProvider('provideSuccessfulImports')]
    public function testSuccessfulImportAddsMessage(
        string $exportType,
        string $importMethod,
        FlashMessage $successMessage,
    ) : void {
        $request = $this->createRequest($exportType, [$exportType => ['tmp_name' => '/tmp/import.csv']]);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->importServiceMock
            ->expects(self::once())
            ->method($importMethod)
            ->with(42, '/tmp/import.csv');
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($successMessage);
        $this->loggerMock->expects(self::never())->method('error');
        $this->expectDataPageRedirect();

        $response = $this->subject->handleCsvImport($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/settings/account/data', (string)$response->getHeaders()[0]);
    }

    #[DataProvider('provideFailedImports')]
    public function testFailedImportAddsTypeSpecificMessage(
        string $exportType,
        string $importMethod,
        FlashMessage $failureMessage,
    ) : void {
        $request = $this->createRequest($exportType, []);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->importServiceMock->expects(self::never())->method($importMethod);
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($failureMessage);
        $this->loggerMock->expects(self::once())->method('error');
        $this->expectDataPageRedirect();

        $response = $this->subject->handleCsvImport($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
    }

    public function testUnknownImportTypeDoesNotAddMessage() : void
    {
        $request = $this->createRequest('unknown', []);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->flashMessageServiceMock->expects(self::never())->method('add');
        $this->loggerMock->expects(self::once())->method('error');
        $this->expectDataPageRedirect();

        $response = $this->subject->handleCsvImport($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
    }

    /** @return iterable<string, array{string, string, FlashMessage}> */
    public static function provideSuccessfulImports() : iterable
    {
        yield 'history' => [
            'history',
            'importHistory',
            FlashMessage::IMPORT_HISTORY_SUCCESSFUL,
        ];
        yield 'ratings' => [
            'ratings',
            'importRatings',
            FlashMessage::IMPORT_RATINGS_SUCCESSFUL,
        ];
        yield 'watchlist' => [
            'watchlist',
            'importWatchlist',
            FlashMessage::IMPORT_WATCHLIST_SUCCESSFUL,
        ];
    }

    /** @return iterable<string, array{string, string, FlashMessage}> */
    public static function provideFailedImports() : iterable
    {
        yield 'history' => [
            'history',
            'importHistory',
            FlashMessage::IMPORT_HISTORY_FAILED,
        ];
        yield 'ratings' => [
            'ratings',
            'importRatings',
            FlashMessage::IMPORT_RATINGS_FAILED,
        ];
        yield 'watchlist' => [
            'watchlist',
            'importWatchlist',
            FlashMessage::IMPORT_WATCHLIST_FAILED,
        ];
    }

    /** @param array<string, array<string, string>> $files */
    private function createRequest(string $exportType, array $files) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['exportType' => $exportType]);
        $request->method('getFileParameters')->willReturn($files);

        return $request;
    }

    private function expectDataPageRedirect() : void
    {
        $this->urlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/settings/account/data'))
            ->willReturn('/movary/settings/account/data');
    }
}
