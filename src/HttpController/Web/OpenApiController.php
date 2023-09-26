<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Service\ServerSettings;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class OpenApiController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function renderPage() : Response
    {
        $openAiJsonUrl = '/api/openapi';

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        if ($applicationUrl !== null) {
            $openAiJsonUrl = trim($applicationUrl, '/') . $openAiJsonUrl;
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/api.html.twig', [
                'openAiJsonUrl' => $openAiJsonUrl
            ]),
        );
    }
}
