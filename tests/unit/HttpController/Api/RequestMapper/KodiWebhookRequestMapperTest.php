<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\KodiWebhookRequestMapper;
use Movary\HttpController\Api\RequestMapper\WebhookRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KodiWebhookRequestMapper::class)]
class KodiWebhookRequestMapperTest extends TestCase
{
    public function testMapsValidMovie() : void
    {
        $payload = ['title' => 'Movie', 'uniqueIds' => ['tmdbId' => '42']];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testPreservesMissingTmdbIdAsIgnoredMovie() : void
    {
        $payload = ['title' => 'Movie', 'uniqueIds' => []];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testRejectsInvalidUniqueIdsObject() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField('uniqueIds', 'must be an object'));

        $this->createSubject(['uniqueIds' => '42'])->mapRequest($this->createStub(Request::class));
    }

    public function testRejectsInvalidTmdbId() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('uniqueIds.tmdbId', 'must be a positive integer or empty'),
        );

        $this->createSubject(['uniqueIds' => ['tmdbId' => 'invalid']])
            ->mapRequest($this->createStub(Request::class));
    }

    private function createSubject(array $payload) : KodiWebhookRequestMapper
    {
        $requestMapper = $this->createStub(WebhookRequestMapper::class);
        $requestMapper->method('mapJsonBody')->willReturn($payload);

        return new KodiWebhookRequestMapper($requestMapper);
    }
}
