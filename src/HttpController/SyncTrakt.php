<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Trakt\Sync;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class SyncTrakt
{
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute() : Response
    {
        exec(
            sprintf(
                "%s 2>&1",
                'cd ' . __DIR__ . '/../../ && php bin/console.php app:sync-trakt',
            )
        );

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
