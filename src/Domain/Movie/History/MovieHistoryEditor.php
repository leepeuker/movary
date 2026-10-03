<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\MovieApi;
use Movary\ValueObject\Date;

class MovieHistoryEditor
{
    public function __construct(
        private readonly Connection $dbConnection,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function update(
        int $movieId,
        int $userId,
        ?Date $originalWatchDate,
        ?Date $newWatchDate,
        int $plays,
        int $position,
        ?string $comment,
        ?int $locationId,
        ?bool $postToMastodon,
    ) : void {
        $this->dbConnection->transactional(function () use (
            $movieId,
            $userId,
            $originalWatchDate,
            $newWatchDate,
            $plays,
            $position,
            $comment,
            $locationId,
            $postToMastodon,
        ) : void {
            if ($originalWatchDate == $newWatchDate) {
                $this->movieApi->updateHistoryComment($movieId, $userId, $newWatchDate, $comment);
                $this->movieApi->updateHistoryLocation($movieId, $userId, $newWatchDate, $locationId);
                $this->movieApi->replaceHistoryForMovieByDate($movieId, $userId, $newWatchDate, $plays, $position, postToMastodon: $postToMastodon);

                return;
            }

            $this->movieApi->addPlaysForMovieOnDate(
                $movieId,
                $userId,
                $newWatchDate,
                $plays,
                $position,
                comment: $comment,
                locationId: $locationId,
                postToMastodon: $postToMastodon,
            );
            $this->movieApi->deleteHistoryByIdAndDate($movieId, $userId, $originalWatchDate);
            $this->movieApi->updateHistoryComment($movieId, $userId, $newWatchDate, $comment);
            $this->movieApi->updateHistoryLocation($movieId, $userId, $newWatchDate, $locationId);
        });
    }
}
