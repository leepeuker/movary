<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Trakt\SyncRatings;
use Movary\Application\Service\Trakt\SyncWatchedMovies;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class SyncTrakt
{
    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly SyncWatchedMovies $syncWatchedMovies,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute() : Response
    {
        $this->syncWatchedMovies->execute();
        $this->syncRatings->execute();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
