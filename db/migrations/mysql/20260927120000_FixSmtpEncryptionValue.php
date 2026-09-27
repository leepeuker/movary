<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class FixSmtpEncryptionValue extends AbstractMigration
{
    public function down() : void
    {
        throw new IrreversibleMigrationException('Cannot identify which tls values were migrated from tsl.');
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
