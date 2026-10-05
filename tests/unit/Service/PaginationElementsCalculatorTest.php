<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use InvalidArgumentException;
use Movary\Service\PaginationElementsCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaginationElementsCalculator::class)]
class PaginationElementsCalculatorTest extends TestCase
{
    public static function providePaginationData() : array
    {
        return [
            'empty result' => [0, 30, 1, 1, 1, null, null, 0],
            'first page' => [61, 30, 1, 1, 3, null, 2, 0],
            'middle page' => [61, 30, 2, 2, 3, 1, 3, 30],
            'last page' => [61, 30, 3, 3, 3, 2, null, 60],
            'page above range is clamped' => [61, 30, 20, 3, 3, 2, null, 60],
        ];
    }

    #[DataProvider('providePaginationData')]
    public function testCreatesPaginationElements(
        int $totalCount,
        int $limit,
        int $requestedPage,
        int $expectedPage,
        int $expectedMaxPage,
        ?int $expectedPrevious,
        ?int $expectedNext,
        int $expectedOffset,
    ) : void {
        $pagination = (new PaginationElementsCalculator())->createPaginationElements(
            $totalCount,
            $limit,
            $requestedPage,
        );

        self::assertSame($expectedPage, $pagination->getCurrentPage());
        self::assertSame($expectedMaxPage, $pagination->getMaxPage());
        self::assertSame($expectedPrevious, $pagination->getPrevious());
        self::assertSame($expectedNext, $pagination->getNext());
        self::assertSame($expectedOffset, $pagination->getOffset());
        self::assertSame($totalCount, $pagination->getTotalCount());
    }

    public function testRejectsInvalidValues() : void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PaginationElementsCalculator())->createPaginationElements(1, 0, 1);
    }
}
