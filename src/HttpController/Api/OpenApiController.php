<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Service\ApplicationUrlService;
use Movary\Service\ServerSettings;
use Movary\Util\File;
use Movary\Util\Json;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class OpenApiController
{
    public function __construct(
        private readonly File $fileUtil,
        private readonly ServerSettings $serverSettings,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly string $docsPath,
    ) {
    }

    public function getSchema() : Response
    {
        $openapiData = Json::decode($this->fileUtil->readFile($this->docsPath . 'openapi.json'));

        $openapiData['info']['version'] = $this->serverSettings->getApplicationVersion();
        $openapiData['servers'][0]['url'] = $this->applicationUrlService->createApplicationUrl(RelativeUrl::create('/api'));

        return Response::createJson(Json::encode($openapiData));
    }
}
