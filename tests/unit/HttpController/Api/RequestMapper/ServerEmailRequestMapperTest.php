<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\RequestMapper\ServerEmailRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerEmailRequestMapper::class)]
class ServerEmailRequestMapperTest extends TestCase
{
    public function testPreservesExplicitFalseAndEmptyEncryption() : void
    {
        $data = ['emailEnabled' => false, 'smtpEncryption' => '', 'smtpWithAuthentication' => false];
        self::assertSame($data, $this->map($data));
    }

    #[DataProvider('invalidFields')]
    public function testRejectsInvalidFields(array $data) : void
    {
        $this->expectException(InvalidApiRequest::class);
        $this->map($data);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidFields() : iterable
    {
        yield 'empty' => [[]];
        yield 'unknown' => [['smtpPasswordConfigured' => true]];
        yield 'boolean string' => [['emailEnabled' => 'false']];
        yield 'authentication number' => [['smtpWithAuthentication' => 1]];
        yield 'port string' => [['smtpPort' => '587']];
        yield 'port float' => [['smtpPort' => 587.5]];
        yield 'host array' => [['smtpHost' => []]];
        yield 'password null' => [['smtpPassword' => null]];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function map(array $data) : array
    {
        $mapper = $this->createStub(RequestMapper::class);
        $mapper->method('mapJsonObject')->willReturn($data);

        return (new ServerEmailRequestMapper($mapper))->mapUpdate($this->createStub(Request::class));
    }
}
