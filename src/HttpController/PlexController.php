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
        $this->logger->debug($request->getBody());

        return Response::create(StatusCode::createOk());
    }
}
