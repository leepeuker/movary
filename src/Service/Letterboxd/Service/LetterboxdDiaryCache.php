<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\Service;

use Doctrine\DBAL\Connection;

class LetterboxdDiaryCache
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function findLetterboxdIdToDiaryUri(string $diaryId) : ?string
    {
        $result = $this->dbConnection->fetchFirstColumn('SELECT letterboxd_id FROM cache_letterboxd_diary WHERE diary_id = ?', [$diaryId]);

        return empty($result) === true ? null : $result[0];
    }

    public function setLetterboxdIdToDiaryUri(string $diaryId, string $letterboxdId) : void
    {
        $this->dbConnection->insert('cache_letterboxd_diary', ['diary_id' => $diaryId, 'letterboxd_id' => $letterboxdId]);
    }
}
