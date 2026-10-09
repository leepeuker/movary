<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\ImportService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ImportController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ImportService $importService,
        private readonly LoggerInterface $logger,
        private readonly FlashMessageService $flashMessageService,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    public function handleCsvImport(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $exportType = $request->getRouteParameters()['exportType'];
        $fileParameters = $request->getFileParameters();

        try {
            match ($exportType) {
                'history' => $this->importHistory($userId, $fileParameters),
                'ratings' => $this->importRatings($userId, $fileParameters),
                'watchlist' => $this->importWatchlist($userId, $fileParameters),
                default => throw new RuntimeException('Export type not handled: ' . $exportType)
            };
        } catch (Throwable $t) {
            $this->logger->error('Could not import: ' . $exportType, ['exception' => $t]);

            $failureMessage = $this->getFailureMessage($exportType);
            if ($failureMessage !== null) {
                $this->flashMessageService->add($failureMessage);
            }
        }

        $redirectUrl = $this->urlService->createApplicationUrl(RelativeUrl::create('/settings/account/data'));

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($redirectUrl)],
        );
    }

    private function importHistory(int $userId, array $fileParameter) : void
    {
        if (empty($fileParameter['history']['tmp_name']) === true) {
            throw new RuntimeException('Import csv file missing');
        }

        $this->importService->importHistory($userId, $fileParameter['history']['tmp_name']);

        $this->flashMessageService->add(FlashMessage::IMPORT_HISTORY_SUCCESSFUL);
    }

    private function importRatings(int $userId, array $fileParameter) : void
    {
        if (empty($fileParameter['ratings']['tmp_name']) === true) {
            throw new RuntimeException('Import csv file missing');
        }

        $this->importService->importRatings($userId, $fileParameter['ratings']['tmp_name']);

        $this->flashMessageService->add(FlashMessage::IMPORT_RATINGS_SUCCESSFUL);
    }

    private function importWatchlist(int $userId, array $fileParameter) : void
    {
        if (empty($fileParameter['watchlist']['tmp_name']) === true) {
            throw new RuntimeException('Import csv file missing');
        }

        $this->importService->importWatchlist($userId, $fileParameter['watchlist']['tmp_name']);

        $this->flashMessageService->add(FlashMessage::IMPORT_WATCHLIST_SUCCESSFUL);
    }

    private function getFailureMessage(string $exportType) : ?FlashMessage
    {
        return match ($exportType) {
            'history' => FlashMessage::IMPORT_HISTORY_FAILED,
            'ratings' => FlashMessage::IMPORT_RATINGS_FAILED,
            'watchlist' => FlashMessage::IMPORT_WATCHLIST_FAILED,
            default => null,
        };
    }
}
