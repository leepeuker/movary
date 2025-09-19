<?php

declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PlaceholderImageController
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    public function renderPlaceholderImage(Request $request): Response
    {
        $name_encoded = (string)$request->getRouteParameters()['name_encoded'];
        $name = base64_decode($name_encoded);
        $name_safe = htmlspecialchars($name, ENT_XML1, "UTF-8");

        $renderedTemplate = $this->twig->render(
            'component/placeholder-image.svg.twig',
            [
                'name' => $name_safe,
            ],
        );

        return Response::createSVG(
            $renderedTemplate,
            StatusCode::createOk(),
            [Header::createCache(2419200)],
        );
    }
}
