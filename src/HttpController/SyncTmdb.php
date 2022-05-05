<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Service\Tmdb\SyncMovieDetails;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class SyncTmdb
{
    public function __construct(
        private readonly SyncMovieDetails $syncMovieDetails,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute() : Response
    {
        $this->syncMovieDetails->execute();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
