<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Dto\SearchRequestDto;
use Movary\HttpController\Api\RequestMapper\SearchRequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieSearchResponseMapper;
use Movary\HttpController\Web\MovieSearchController;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieSearchController::class)]
class MovieSearchControllerTest extends TestCase
{
    public function testUsesWebSessionUser() : void
    {
        $request = $this->createStub(Request::class);
        $requestData = SearchRequestDto::create('Matrix', 1, null);

        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(12, CredentialType::WEB_SESSION));
        $requestMapper = $this->createMock(SearchRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($requestData);
        $tmdbApi = $this->createMock(TmdbApi::class);
        $tmdbApi
            ->expects(self::once())
            ->method('searchMovie')
            ->with('Matrix', null, 1)
            ->willReturn(['total_results' => 0, 'results' => []]);

        $subject = new MovieSearchController(
            $tmdbApi,
            $this->createStub(PaginationElementsCalculator::class),
            $requestMapper,
            $this->createStub(MovieSearchResponseMapper::class),
            $authentication,
        );

        $response = $subject->search($request);

        self::assertSame('{"results":[],"currentPage":0,"maxPage":0}', $response->getBody());
    }
}
