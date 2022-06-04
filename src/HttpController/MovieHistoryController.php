<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\Api;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class MovieHistoryController
{
    public function __construct(private readonly Api $movieApi)
    {
    }

    public function fetchHistory() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->movieApi->fetchHistoryOrderedByWatchedAtDesc()),
            [Header::createContentTypeJson()]
        );
    }
}
