<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTotpSecretColumn extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN totp_uri
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN totp_uri CHAR(255) DEFAULT NULL AFTER password
            SQL,
        );
    }
}
