<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Http\Response;
use RuntimeException;

class ManifestController
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function render() : Response
    {
        $manifestContents = file_get_contents(dirname(__DIR__, 3) . '/public/manifest.json');
        if ($manifestContents === false) {
            throw new RuntimeException('Could not read the PWA manifest.');
        }

        $manifest = Json::decode($manifestContents);
        $applicationName = $this->serverSettings->getApplicationName() ?? 'Movary';
        $manifest['name'] = $applicationName;
        $manifest['short_name'] = $applicationName;

        return Response::createWebManifest(Json::encode($manifest));
    }
}
