<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\Person\PersonApi;
use Movary\Service\SlugifyService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PersonSlugRedirector implements MiddlewareInterface
{
    public function __construct(
        private readonly PersonApi $personApi,
        private readonly SlugifyService $slugifyService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $requestedUsername = (string)$request->getRouteParameters()['username'];
        $nameSlugSuffix = (string)$request->getRouteParameters()['nameSlugSuffix'];

        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);

        if ($person === null) {
            return Response::createNotFound();
        }

        $personNameSlug = $this->slugifyService->slugify($person->getName());
        if ($nameSlugSuffix == "" || $nameSlugSuffix != $personNameSlug) {
            return Response::createMovedPermanently(
                "/users/"
                . $requestedUsername
                . "/persons/"
                . $personId
                . "-"
                . $personNameSlug,
            );
        }

        return null;
    }
}
