<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\ExportService;
use Movary\Application\SessionService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class ExportController
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly ExportService $exportService
    ) {
    }

    public function getCsvExport(Request $request) : Response
    {
        if ($this->sessionService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/login');
        }

        $exportCsv = match ($request->getRouteParameters()['exportType']) {
            'history' => $this->exportService->getHistoryCsv(),
            'ratings' => $this->exportService->getRatingCsv(),
            default => null
        };

        if ($exportCsv === null) {
            return Response::createNotFound();
        }

        return Response::createCsv($exportCsv);
    }
}
