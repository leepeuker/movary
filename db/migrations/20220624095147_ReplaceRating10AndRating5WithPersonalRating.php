<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ReplaceRating10AndRating5WithPersonalRating extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie RENAME COLUMN personal_rating TO rating_10;
            ALTER TABLE movie ADD COLUMN rating_5 TINYINT DEFAULT NULL AFTER rating_10;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie RENAME COLUMN rating_10 TO personal_rating;
            ALTER TABLE movie DROP COLUMN rating_5;
            SQL
        );
    }
}
