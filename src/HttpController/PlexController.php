<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;

class PlexController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function handlePlexWebhook(Request $request) : Response
    {
        $webHook = Json::decode($request->getPostParameters()['payload']);

        if ($webHook['event'] !== 'media.scrobble' || $webHook['owner'] !== true) {
            return Response::create(StatusCode::createOk());
        }

        var_dump($webHook);

        exit;
        foreach ($webHook['Metadata']['Guid'] as $guid) {
        }

        $this->logger->debug(Json::encode($webHook));

        return Response::create(StatusCode::createOk());
    }
}
