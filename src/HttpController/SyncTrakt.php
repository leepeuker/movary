<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Trakt\Sync;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class SyncTrakt
{
    public function __construct(
        private readonly Sync $syncService
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute() : Response
    {
        $this->syncService->syncAll();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
