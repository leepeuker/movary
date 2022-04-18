<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;

class MovieHistoryController
{
    private Select $movieHistorySelectService;

    public function __construct(Select $movieHistorySelectService)
    {

        $this->movieHistorySelectService = $movieHistorySelectService;
    }

    public function fetchHistory() : void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($this->movieHistorySelectService->fetchHistoryOrderedByWatchedAtDesc());
    }
}
