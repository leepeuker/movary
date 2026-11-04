<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Template;

use Movary\Service\MovieTitleFormatter;
use Movary\Service\SlugifyService;
use Movary\ValueObject\MovieTitleDisplay;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

#[CoversNothing]
class MoviePageTest extends TestCase
{
    public function testBackdropAndPosterAreShownWithAccessibleCredits() : void
    {
        $html = $this->renderPage();

        self::assertStringContainsString('class="movie-backdrop" aria-hidden="true"', $html);
        self::assertStringContainsString('src="/backdrop.jpg" alt="" fetchpriority="high"', $html);
        self::assertStringContainsString('movie-profile-with-backdrop', $html);
        self::assertStringContainsString('alt="Example movie Poster"', $html);
        self::assertStringContainsString('tabindex="0" role="group" aria-label="Cast"', $html);
        self::assertStringContainsString('aria-label="Scroll cast right"', $html);
        self::assertStringContainsString('/users/viewer/persons/1-example-person', $html);
        self::assertStringContainsString('Example character', $html);
    }

    public function testMissingBackdropAndMetadataHaveCompactEmptyStates() : void
    {
        $html = $this->renderPage([
            'movie' => ['backdropPath' => null, 'overview' => null, 'tagline' => null, 'releaseDate' => null],
            'castMembers' => [],
            'directors' => [],
        ]);

        self::assertStringNotContainsString('class="movie-backdrop"', $html);
        self::assertStringNotContainsString('movie-profile-with-backdrop', $html);
        self::assertStringNotContainsString('class="movie-tagline', $html);
        self::assertStringContainsString('No plot available.', $html);
        self::assertStringContainsString('No cast available.', $html);
        self::assertStringContainsString('No directors available.', $html);
    }

    public function testOwnerCanUseExistingTrackingControlsAndDialogs() : void
    {
        $html = $this->renderPage(['currentUserName' => 'viewer', 'currentUserIsAdmin' => true]);

        foreach (['logSpecificMovieButton', 'watchlistButton', 'reviewButton', 'editRatingButton', 'moreSpecificMovieButton'] as $id) {
            self::assertSame(1, substr_count($html, 'id="' . $id . '"'));
        }
        self::assertMatchesRegularExpression('/<header class="movie-heading">.*aria-label="Movie options".*<\/header>/s', $html);
        self::assertStringContainsString('data-bs-target="#moreModal"', $html);
        self::assertStringContainsString('modal-edit-rating.html.twig', $html);
        self::assertStringContainsString('modal-change-movie-poster.html.twig', $html);
        self::assertStringContainsString('toggleWatchlist(true)', $html);
        self::assertStringContainsString('onclick="loadWatchDateModal(this)"', $html);
        self::assertStringContainsString('data-play-id="42"', $html);
    }

    public function testVisitorsCanReadHistoryWithoutEditControls() : void
    {
        $html = $this->renderPage(['currentUserName' => 'another-user']);

        foreach (['logSpecificMovieButton', 'watchlistButton', 'reviewButton', 'editRatingButton', 'moreSpecificMovieButton'] as $id) {
            self::assertStringNotContainsString('id="' . $id . '"', $html);
        }
        self::assertStringContainsString('id="toggleWatchDatesButton"', $html);
        self::assertStringContainsString('aria-controls="watchDatesListDiv" aria-expanded="false"', $html);
        self::assertStringNotContainsString('onclick="loadWatchDateModal(this)"', $html);
        self::assertStringNotContainsString('modal-edit-rating.html.twig', $html);
    }

    public function testExistingReviewRemainsAvailableToVisitors() : void
    {
        $html = $this->renderPage(['selectedUserReview' => ['review' => 'A review']]);

        self::assertStringContainsString('id="reviewButton"', $html);
        self::assertStringContainsString('modal-review.html.twig', $html);
    }

    public function testPreferencesAndEscapingArePreserved() : void
    {
        $html = $this->renderPage([
            'movieTitleDisplay' => 'both',
            'movie' => ['originalTitle' => 'Original title', 'overview' => '<script>bad()</script>'],
            'displayCharacterNames' => false,
            'displayTmdbRating' => false,
            'displayImdbRating' => false,
        ]);

        self::assertStringContainsString('Original title</div>', $html);
        self::assertStringContainsString('&lt;script&gt;bad()&lt;/script&gt;', $html);
        self::assertStringNotContainsString('Example character', $html);
        self::assertStringNotContainsString('8.2', $html);
        self::assertStringNotContainsString('8.4', $html);
        self::assertStringContainsString('alt="TMDB"', $html);
        self::assertStringContainsString('alt="IMDb"', $html);
    }

    public function testOriginalTitlePreferenceAndMissingReleaseYear() : void
    {
        $html = $this->renderPage([
            'currentUserName' => 'viewer',
            'movieTitleDisplay' => 'original',
            'movie' => ['originalTitle' => 'Original title', 'releaseDate' => null],
            'totalPlays' => 0,
            'watchDates' => [],
        ]);

        self::assertStringContainsString('Original title ', $html);
        self::assertStringNotContainsString('(1970)', $html);
        self::assertStringContainsString(', null)">Add play', $html);
        self::assertStringContainsString('aria-expanded="false" disabled', $html);
    }

    private function renderPage(array $overrides = []) : string
    {
        $stubs = [
            'base.html.twig' => '{% block body %}{% endblock %}',
            'component/navbar.html.twig' => '',
            'component/user-select.html.twig' => '',
        ];
        foreach (['where-to-watch', 'edit-rating', 'edit-watch-date', 'more-options', 'change-movie-poster', 'review'] as $modal) {
            $stubs['component/modal-' . $modal . '.html.twig'] = 'modal-' . $modal . '.html.twig';
        }
        $twig = new Environment(new ChainLoader([
            new ArrayLoader($stubs),
            new FilesystemLoader(dirname(__DIR__, 3) . '/templates'),
        ]), ['strict_variables' => true]);
        $twig->addFunction(new TwigFunction('css_url', static fn(string $path) : string => '/css/' . $path));
        $twig->addFunction(new TwigFunction('js_url', static fn(string $path) : string => '/js/' . $path));
        $twig->addFilter(new TwigFilter('slugify', [new SlugifyService(), 'slugify']));
        $twig->addFilter(new TwigFilter(
            'movie_title',
            static fn(string $title, ?string $originalTitle) : string => (new MovieTitleFormatter())->format($title, $originalTitle, MovieTitleDisplay::createLocalized()),
        ));
        $person = ['id' => 1, 'name' => 'Example person', 'posterPath' => '/portrait.jpg', 'characterName' => 'Example character'];

        $context = [
            'applicationUrl' => '',
            'theme' => 'dark',
            'routeUsername' => 'viewer',
            'currentUserName' => null,
            'currentUserIsAdmin' => false,
            'currentUserLocationsEnabled' => true,
            'movieTitleDisplay' => 'localized',
            'dateFormatPhp' => 'Y-m-d',
            'movie' => [
                'id' => 12, 'tmdbId' => 34, 'title' => 'Example movie', 'originalTitle' => null,
                'releaseDate' => '2024-01-01', 'posterPath' => '/poster.jpg', 'backdropPath' => '/backdrop.jpg',
                'runtime' => '2h 15m', 'originalLanguage' => 'English', 'personalRating' => 8,
                'tagline' => 'An example tagline.', 'overview' => 'An example plot.',
                'tmdbUrl' => 'https://www.themoviedb.org/movie/34', 'tmdbRatingAverage' => 8.2, 'tmdbRatingVoteCount' => '1K',
                'imdbId' => 'tt123', 'imdbUrl' => 'https://www.imdb.com/title/tt123', 'imdbRatingAverage' => 8.4, 'imdbRatingVoteCount' => '2K',
            ],
            'movieGenres' => [['name' => 'Drama']],
            'movieProductionCountries' => ['United States'],
            'castMembers' => [$person],
            'directors' => [$person],
            'displayCharacterNames' => true,
            'displayTmdbRating' => true,
            'displayImdbRating' => true,
            'selectedUserReview' => null,
            'totalPlays' => 1,
            'watchDates' => [['id' => 42, 'watched_at' => '2026-10-20', 'position' => 1, 'comment' => 'Example comment', 'location_id' => 2]],
            'isOnWatchlist' => true,
        ];
        $overrides['movie'] = array_replace($context['movie'], $overrides['movie'] ?? []);

        return $twig->render('page/movie.html.twig', array_replace($context, $overrides));
    }
}
