<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\ResourceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\ResourceType::class)]
class ResourceTypeTest extends TestCase
{
    public function testCreateMovie() : void
    {
        $subject = ResourceType::createMovie();

        self::assertSame('movie', (string)$subject);
    }

    public function testCreatePerson() : void
    {
        $subject = ResourceType::createPerson();

        self::assertSame('person', (string)$subject);
    }

    public function testIsMovie() : void
    {
        self::assertTrue(ResourceType::createMovie()->isMovie());
        self::assertFalse(ResourceType::createPerson()->isMovie());
    }

    public function testIsPerson() : void
    {
        self::assertTrue(ResourceType::createPerson()->isPerson());
        self::assertFalse(ResourceType::createMovie()->isPerson());
    }
}
