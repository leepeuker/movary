<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSecondRatingType extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie RENAME COLUMN rating_10 TO rating;
            ALTER TABLE movie DROP COLUMN rating_5;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `movie` CHANGE `rating` `rating_10` TINYINT(3) UNSIGNED NULL DEFAULT NULL; 
            ALTER TABLE movie ADD COLUMN rating_5 TINYINT DEFAULT NULL AFTER rating_10;
            SQL
        );
    }
}
