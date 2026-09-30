<?php declare(strict_types=1);

namespace Tests\Unit\Movary;

use Movary\Factory;
use Movary\Util\File;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    /** @psalm-suppress InternalMethod */
    public function testConfiguresCanonicalMysqlTableDefaults() : void
    {
        $config = new Config($this->createStub(File::class), [
            'DATABASE_MODE' => 'mysql',
            'DATABASE_MYSQL_HOST' => 'database',
            'DATABASE_MYSQL_NAME' => 'movary',
            'DATABASE_MYSQL_USER' => 'movary',
            'DATABASE_MYSQL_PASSWORD' => 'secret',
        ]);

        $connection = Factory::createDbConnection($config);

        try {
            self::assertSame([
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'engine' => 'InnoDB',
            ], $connection->getParams()['defaultTableOptions']);
        } finally {
            $connection->close();
        }
    }
}
