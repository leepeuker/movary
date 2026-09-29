<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use RuntimeException;

final class SchemaMismatch extends RuntimeException
{
    /**
     * @param list<string> $differences
     */
    public static function create(array $differences) : self
    {
        return new self(
            "The database schema does not match the Doctrine cutover boundary:\n- "
            . implode("\n- ", $differences),
        );
    }
}
