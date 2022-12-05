<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\ImportService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ImportController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ImportService $importService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleCsvImport(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/login');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $exportType = $request->getRouteParameters()['exportType'];
        $fileParameters = $request->getFileParameters();

        try {
            match ($exportType) {
                'history' => $this->importHistory($userId, $fileParameters),
                'ratings' => $this->importRatings($userId, $fileParameters),
                default => throw new RuntimeException('Export type not handled: ' . $exportType)
            };
        } catch (Throwable $t) {
            $this->logger->error('Could not import: ' . $exportType, ['exception' => $t]);
            $_SESSION['importHistoryError'] = $exportType;
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    private function importHistory(int $userId, array $fileParameter) : void
    {
        if (empty($fileParameter['history']['tmp_name']) === true) {
            throw new RuntimeException('Import csv file missing');
        }

        $this->importService->importHistory($userId, $fileParameter['history']['tmp_name']);

        $_SESSION['importHistorySuccessful'] = true;
    }

    private function importRatings(int $userId, array $fileParameter) : void
    {
        if (empty($fileParameter['ratings']['tmp_name']) === true) {
            throw new RuntimeException('Import csv file missing');
        }

        $this->importService->importRatings($userId, $fileParameter['ratings']['tmp_name']);

        $_SESSION['importRatingsSuccessful'] = true;
    }
}
