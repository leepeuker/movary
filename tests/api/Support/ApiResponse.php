<?php declare(strict_types=1);

namespace Tests\Api\Movary\Support;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function assertStatus(int $expectedStatus) : self
    {
        Assert::assertSame($expectedStatus, $this->response->getStatusCode());

        return $this;
    }

    public function assertHeaderSame(string $name, string $expectedValue) : self
    {
        Assert::assertSame($expectedValue, $this->response->getHeaderLine($name));

        return $this;
    }

    /** @param array<string, mixed> $expectedBody */
    public function assertJsonSame(array $expectedBody) : self
    {
        Assert::assertSame(
            $expectedBody,
            json_decode((string)$this->response->getBody(), true, flags: JSON_THROW_ON_ERROR),
        );

        return $this;
    }
}
