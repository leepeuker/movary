<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use Twig\Environment;

class OpenApiController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
    }

    public function renderPage() : Response
    {
        $applicationUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/api/openapi'),
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/api.html.twig', [
                'openApiJsonUrl' => $applicationUrl
            ]),
        );
    }
}
