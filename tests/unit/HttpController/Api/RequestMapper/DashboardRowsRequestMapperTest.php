<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\DashboardRowsRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DashboardRowsRequestMapper::class)]
class DashboardRowsRequestMapperTest extends TestCase
{
    public function testMapsKeysToStoredIdsInSubmittedOrder() : void
    {
        $rows = $this->rows();
        $rows = array_reverse($rows);
        foreach ($rows as $position => &$row) {
            $row['position'] = $position;
        }
        unset($row);
        $rows[0]['visible'] = false;
        $rows[1]['initiallyExpanded'] = true;

        self::assertSame([
            'visibleRows' => '12;11;10;9;7;6;8;4;3;2;1;0',
            'extendedRows' => '12;0',
            'orderRows' => '13;12;11;10;9;7;6;8;4;3;2;1;0',
        ], $this->subject()->mapRows($this->request(['rows' => $rows])));
    }

    public function testPositionDeterminesOrderEvenWhenArrayIsShuffled() : void
    {
        $rows = array_reverse($this->rows());

        self::assertSame(
            '0;1;2;3;4;8;6;7;9;10;11;12;13',
            $this->subject()->mapRows($this->request(['rows' => $rows]))['orderRows'],
        );
    }

    #[DataProvider('invalidRowsProvider')]
    public function testRejectsInvalidRows(string $change, string $field) : void
    {
        $rows = $this->rows();
        switch ($change) {
            case 'missing':
                array_pop($rows);
                break;
            case 'duplicate':
                $rows[1]['key'] = $rows[0]['key'];
                break;
            case 'unknown':
                $rows[0]['key'] = 'unknown';
                break;
            case 'nonboolean':
                $rows[0]['visible'] = 1;
                break;
            case 'missingBoolean':
                unset($rows[0]['initiallyExpanded']);
                break;
            case 'duplicatePosition':
                $rows[1]['position'] = 0;
                break;
            case 'invalidPosition':
                $rows[0]['position'] = 13;
                break;
        }

        $this->expectException(InvalidApiRequest::class);
        $this->expectExceptionMessage('Field "' . $field . '"');

        $this->subject()->mapRows($this->request(['rows' => $rows]));
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidRowsProvider() : iterable
    {
        yield 'missing row' => ['missing', 'rows'];
        yield 'duplicate key' => ['duplicate', 'rows[1].key'];
        yield 'unknown key' => ['unknown', 'rows[0].key'];
        yield 'nonboolean visibility' => ['nonboolean', 'rows[0].visible'];
        yield 'missing expansion' => ['missingBoolean', 'rows[0].initiallyExpanded'];
        yield 'duplicate position' => ['duplicatePosition', 'rows[1].position'];
        yield 'invalid position' => ['invalidPosition', 'rows[0].position'];
    }

    private function subject() : DashboardRowsRequestMapper
    {
        return new DashboardRowsRequestMapper(
            new RequestMapper($this->createStub(UserApi::class), $this->createStub(MovieApi::class)),
            new DashboardFactory(),
        );
    }

    private function rows() : array
    {
        $rows = [];
        foreach ((new DashboardFactory())->createDefaultDashboardRows() as $row) {
            $rows[] = ['key' => $row->getKey(), 'position' => count($rows), 'visible' => true, 'initiallyExpanded' => $row->isExtended()];
        }

        return $rows;
    }

    private function request(array $data) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(json_encode($data, JSON_THROW_ON_ERROR));

        return $request;
    }
}
