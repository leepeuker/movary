<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\Export\ExportService;
use Movary\Util\File;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class ExportController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ExportService $exportService,
        private readonly File $fileUtil,
    ) {
    }

    public function getCsvExport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/login');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $exportType = $request->getRouteParameters()['exportType'] ?? null;

        $exportCsvPath = match ($exportType) {
            'history' => $this->exportService->createExportHistoryCsv($userId),
            'ratings' => $this->exportService->createExportRatingsCsv($userId),
            'watchlist' => $this->exportService->createExportWatchlistCsv($userId),
            default => null
        };

        if ($exportCsvPath === null) {
            return Response::createNotFound();
        }

        $exportCsvContent = $this->fileUtil->readFile($exportCsvPath);

        $this->fileUtil->deleteFile($exportCsvPath);

        return Response::createCsv($exportCsvContent);
    }
}
