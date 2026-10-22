<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Web\ExportController;
use Movary\Service\Export\ExportService;
use Movary\Util\File;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportController::class)]
class ExportControllerTest extends TestCase
{
    public function testGetCsvExportCreatesReviewExport() : void
    {
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('requireWebSession')->willReturn(
            AuthenticatedUser::create(42, CredentialType::WEB_SESSION),
        );
        $exportService = $this->createMock(ExportService::class);
        $exportService
            ->expects(self::once())
            ->method('createExportReviewsCsv')
            ->with(42)
            ->willReturn('/tmp/reviews.csv');
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('readFile')->with('/tmp/reviews.csv')->willReturn("review\n");
        $file->expects(self::once())->method('deleteFile')->with('/tmp/reviews.csv');
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['exportType' => 'reviews']);

        $response = (new ExportController($authentication, $exportService, $file))->getCsvExport($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame("review\n", $response->getBody());
    }
}
