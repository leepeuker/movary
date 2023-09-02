<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Service\ServerSettings;
use Movary\Util\File;
use Movary\Util\Json;
use Movary\ValueObject\Http\Response;

class OpenApiController
{
    public function __construct(
        private readonly File $fileUtil,
        private readonly ServerSettings $serverSettings,
        private readonly string $docsPath,
    ) {
    }

    public function getSchema() : Response
    {
        $openapiData = Json::decode($this->fileUtil->readFile($this->docsPath . 'openapi.json'));

        $openapiData['info']['version'] = $this->serverSettings->getApplicationVersion();

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        if ($applicationUrl !== null) {
            $openapiData['servers'][0]['url'] = trim($applicationUrl, '/') . '/api';
        }

        return Response::createJson(Json::encode($openapiData));
    }
}
