<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\AbstractMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(AbstractMap::class)]
class AbstractMapTest extends TestCase
{
    public function testObjectKeysRetainTheirIdentity() : void
    {
        $map = new /** @extends AbstractMap<object, string> */ class extends AbstractMap {
            public function __construct()
            {
            }

            public function get(object $key) : mixed
            {
                return $this->findByKey($key);
            }

            public function set(object $key, string $value) : void
            {
                $this->setKeyAndValue($key, $value);
            }
        };
        $firstKey = new stdClass();
        $secondKey = new stdClass();

        $map->set($firstKey, 'first');
        $map->set($secondKey, 'second');
        $map->set($firstKey, 'updated');

        self::assertCount(2, $map);
        self::assertSame('updated', $map->get($firstKey));
        self::assertSame('second', $map->get($secondKey));
        self::assertNull($map->get(new stdClass()));
        self::assertSame($firstKey, $map->key());
        self::assertSame('updated', $map->current());
        $map->next();
        self::assertSame($secondKey, $map->key());
        self::assertSame('second', $map->current());
    }
}
