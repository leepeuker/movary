<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateUsername extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user MODIFY COLUMN name VARCHAR(256) DEFAULT NULL AFTER email;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            UPDATE user SET name = id WHERE name IS NULL;
            ALTER TABLE user MODIFY COLUMN name VARCHAR(256) NOT NULL AFTER email;
            SQL
        );
    }
}
