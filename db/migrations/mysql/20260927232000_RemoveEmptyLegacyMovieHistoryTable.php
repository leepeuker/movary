<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveEmptyLegacyMovieHistoryTable extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('Removing the obsolete movie_history table is irreversible.');
    }

    public function up() : void
    {
        if ($this->hasTable('movie_history') === false) {
            return;
        }

        $row = $this->fetchRow('SELECT COUNT(*) row_count FROM movie_history');
        $rowCount = $row === false ? 0 : (int)$row['row_count'];
        if ($rowCount !== 0) {
            throw new RuntimeException(
                "Cannot remove the obsolete movie_history table because it contains $rowCount row(s). "
                . 'Back up the database and migrate or remove those rows manually before retrying.',
            );
        }

        $this->execute('DROP TABLE movie_history');
    }
}
