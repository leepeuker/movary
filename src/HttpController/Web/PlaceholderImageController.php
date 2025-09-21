<?php

declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PlaceholderImageController
{
    private const int CACHE_DURATION_IN_SECONDS = 2419200;

    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function renderPlaceholderImage(Request $request) : Response
    {
        $imageNameBase64Encoded = $request->getRouteParameters()['imageNameBase64Encoded'] ?? null;

        if ($imageNameBase64Encoded === null) {
            return Response::createBadRequest('Missing route parameter: imageNameBase64Encoded');
        }

        $imageNameBase64Decoded = base64_decode($imageNameBase64Encoded);
        $imageNameHtmlEncoded = htmlspecialchars($imageNameBase64Decoded, ENT_XML1, "UTF-8");

        $renderedTemplate = $this->twig->render(
            'component/placeholder-image.svg.twig',
            [
                'name' => $imageNameHtmlEncoded,
            ],
        );

        return Response::createSVG(
            $renderedTemplate,
            StatusCode::createOk(),
            self::CACHE_DURATION_IN_SECONDS,
        );
    }
}
