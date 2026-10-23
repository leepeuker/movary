<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Movie;

use Movary\Domain\Country\CountryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\Movie\MovieController;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\SlugifyService;
use Movary\Service\Tmdb\SyncMovie;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

#[CoversClass(MovieController::class)]
#[AllowMockObjectsWithoutExpectations]
class MovieControllerTest extends TestCase
{
    public function testRenderPageLoadsOnlySelectedUsersReview() : void
    {
        $selectedUser = $this->createStub(UserEntity::class);
        $selectedUser->method('getId')->willReturn(7);
        $viewer = $this->createStub(UserEntity::class);
        $viewer->method('getId')->willReturn(99);

        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($selectedUser);
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $currentWebUser->method('findUser')->willReturn($viewer);

        $movieApi = $this->createStub(MovieApi::class);
        $movieApi->method('findByIdFormatted')->willReturn(['title' => 'Arrival']);
        $movieApi->method('findUserRating')->willReturn(null);
        $movieApi->method('findProductionCountriesByMovieId')->willReturn([]);
        $movieApi->method('findGenresByMovieId')->willReturn([]);
        $movieApi->method('findCastByMovieId')->willReturn([]);
        $movieApi->method('findDirectorsByMovieId')->willReturn([]);
        $movieApi->method('fetchHistoryMovieTotalPlays')->willReturn(0);
        $movieApi->method('fetchHistoryByMovieId')->willReturn([]);

        $selectedReview = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 7,
            'review' => 'Selected review.',
            'contains_spoilers' => 0,
            'created_at' => '2026-10-20 12:00:00',
            'updated_at' => null,
        ]);
        $movieReviewApi = $this->createMock(MovieReviewApi::class);
        $movieReviewApi->expects(self::once())->method('find')->with(12, 7)->willReturn($selectedReview);

        $authorizationChecker = $this->createMock(UserPageAuthorizationChecker::class);
        $authorizationChecker
            ->expects(self::once())
            ->method('fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor')
            ->with(12)
            ->willReturn([['name' => 'bob'], ['name' => 'alice']]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/movie.html.twig',
                self::callback(static function (array $context) use ($selectedReview) : bool {
                    self::assertSame($selectedReview, $context['selectedUserReview']);
                    self::assertArrayNotHasKey('otherReviews', $context);
                    self::assertSame([['name' => 'bob'], ['name' => 'alice']], $context['users']);

                    return true;
                }),
            )
            ->willReturn('rendered movie page');

        $movieWatchlistApi = $this->createStub(MovieWatchlistApi::class);
        $movieWatchlistApi->method('hasMovieInWatchlist')->willReturn(false);
        $slugify = $this->createStub(SlugifyService::class);
        $slugify->method('slugify')->willReturn('arrival');
        $countryApi = $this->createStub(CountryApi::class);
        $countryApi->method('getIso31661ToNameMap')->willReturn([]);

        $subject = new MovieController(
            $twig,
            $movieApi,
            $movieWatchlistApi,
            $authorizationChecker,
            $this->createStub(SyncMovie::class),
            $this->createStub(ImdbMovieRatingSync::class),
            $slugify,
            $countryApi,
            $currentWebUser,
            $userApi,
            $movieReviewApi,
        );
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'alice', 'id' => '12']);

        $response = $subject->renderPage($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('rendered movie page', $response->getBody());
    }
}
