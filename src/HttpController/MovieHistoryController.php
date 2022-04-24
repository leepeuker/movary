<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\Util\Json;

class MovieHistoryController
{
    public function __construct(private readonly Select $movieHistorySelectService)
    {
    }

    public function fetchHistory() : void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo Json::encode($this->movieHistorySelectService->fetchHistoryOrderedByWatchedAtDesc());
    }
}
