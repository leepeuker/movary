<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\Util\Json;

class MostWatchedController
{
    public function __construct(private readonly Select $movieHistorySelectService)
    {
    }

    public function fetchMostWatched() : void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo Json::encode($this->movieHistorySelectService->fetchMoviesOrderedByMostWatchedDesc());
    }
}
