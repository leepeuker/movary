<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixSmtpEncryptionValue extends AbstractMigration
{
    public function down() : void
    {
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            UPDATE `server_setting`
            SET `value` = 'tls'
            WHERE `key` = 'smtpEncryption' AND `value` = 'tsl'
            SQL,
        );
    }
}
