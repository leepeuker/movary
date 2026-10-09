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
    public function testCreatesJavascriptVersionFromServedFileContents() : void
    {
        $file = $this->createMock(File::class);
        $file
            ->expects(self::exactly(2))
            ->method('readFile')
            ->willReturnOnConsecutiveCalls('application-javascript', 'service-worker-javascript');

        self::assertSame(
            hash('sha256', "application-javascript\0service-worker-javascript"),
            Factory::createJavascriptVersion($file),
        );
    }

    /** @psalm-suppress InternalMethod */
    public function testConfiguresMysqlTcpConnectionByDefault() : void
    {
        $config = new Config($this->createStub(File::class), [
            'DATABASE_MODE' => 'mysql',
            'DATABASE_MYSQL_HOST' => 'database',
            'DATABASE_MYSQL_PORT' => 3307,
            'DATABASE_MYSQL_NAME' => 'movary',
            'DATABASE_MYSQL_USER' => 'movary',
            'DATABASE_MYSQL_PASSWORD' => 'secret',
        ]);

        $connection = Factory::createDbConnection($config);

        try {
            self::assertSame('database', $connection->getParams()['host']);
            self::assertSame(3307, $connection->getParams()['port']);
            self::assertArrayNotHasKey('unix_socket', $connection->getParams());
        } finally {
            $connection->close();
        }
    }

    /** @psalm-suppress InternalMethod */
    public function testConfiguresMysqlUnixSocketConnection() : void
    {
        $config = new Config($this->createStub(File::class), [
            'DATABASE_MODE' => 'mysql',
            'DATABASE_MYSQL_SOCKET' => '/run/mysqld/mysqld.sock',
            'DATABASE_MYSQL_NAME' => 'movary',
            'DATABASE_MYSQL_USER' => 'movary',
            'DATABASE_MYSQL_PASSWORD' => 'secret',
        ]);

        $connection = Factory::createDbConnection($config);

        try {
            self::assertSame('/run/mysqld/mysqld.sock', $connection->getParams()['unix_socket']);
            self::assertArrayNotHasKey('host', $connection->getParams());
            self::assertArrayNotHasKey('port', $connection->getParams());
        } finally {
            $connection->close();
        }
    }

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
