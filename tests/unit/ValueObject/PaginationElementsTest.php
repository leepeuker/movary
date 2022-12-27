<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\PaginationElements;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\PaginationElements */
class PaginationElementsTest extends TestCase
{
    private PaginationElements $subject;

    public function setUp() : void
    {
        $this->subject = PaginationElements::create(2, 5, 3, 4);
    }

    public function testGetCurrentPage() : void
    {
        self::assertSame(2, $this->subject->getCurrentPage());
    }

    public function testGetMaxPage() : void
    {
        self::assertSame(5, $this->subject->getMaxPage());
    }

    public function testGetNext() : void
    {
        self::assertSame(4, $this->subject->getNext());
    }

    public function testGetPrevious() : void
    {
        self::assertSame(3, $this->subject->getPrevious());
    }
}
