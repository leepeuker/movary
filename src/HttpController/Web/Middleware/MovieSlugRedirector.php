<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\Movie\MovieApi;
use Movary\Service\SlugifyService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieSlugRedirector implements MiddlewareInterface
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly SlugifyService $slugifyService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $requestedUsername = (string)$request->getRouteParameters()['username'];
        $nameSlugSuffix = (string)$request->getRouteParameters()['nameSlugSuffix'];

        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findByIdFormatted($movieId);

        if ($movie === null) {
            return Response::createNotFound();
        }

        $movieTitleSlug = $this->slugifyService->slugify($movie['title']);
        if ($nameSlugSuffix == "" || $nameSlugSuffix != $movieTitleSlug) {
            return Response::createMovedPermanently(
                "/users/"
                . $requestedUsername
                . "/movies/"
                . $movieId
                . "-"
                . $movieTitleSlug,
            );
        }

        return null;
    }
}
