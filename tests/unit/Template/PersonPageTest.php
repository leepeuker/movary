<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Template;

use Movary\Domain\Movie\MovieApi;
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
class PersonPageTest extends TestCase
{
    private Environment $twig;

    protected function setUp() : void
    {
        $this->twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'base.html.twig' => '{% block body %}{% endblock %}',
                'component/navbar.html.twig' => '',
                'component/user-select.html.twig' => '',
            ]),
            new FilesystemLoader(dirname(__DIR__, 3) . '/templates'),
        ]), ['strict_variables' => true]);
        $this->twig->addFunction(new TwigFunction('js_url', static fn(string $path) : string => '/js/' . $path));
        $this->twig->addFunction(new TwigFunction('css_url', static fn(string $path) : string => '/css/' . $path));
        $this->twig->addFilter(new TwigFilter('slugify', [new SlugifyService(), 'slugify']));
        $formatter = new MovieTitleFormatter();
        $this->twig->addFilter(new TwigFilter(
            'movie_title',
            static fn(string $title, ?string $originalTitle) : string => $formatter->format($title, $originalTitle, MovieTitleDisplay::createLocalized()),
        ));
    }

    public function testWatchlistOnlyActingCreditsAreShownSeparately() : void
    {
        $html = $this->renderPage('Writing', [], [$this->movie()], [], []);

        self::assertStringContainsString('No watched movies for this person yet.', $html);
        self::assertStringContainsString('Watched <span class="text-body-secondary">(0)</span>', $html);
        self::assertStringContainsString('On Watchlist <span class="text-body-secondary">(1)</span>', $html);
        self::assertSame(3, substr_count($html, 'href="/users/viewer/movies/12-example-movie"'));
        self::assertStringContainsString('data-storage-key="personWatchlistView"', $html);
        self::assertStringNotContainsString('data-storage-key="personWatchedView"', $html);
        self::assertStringNotContainsString('role="tablist"', $html);
    }

    public function testWatchlistOnlyDirectingCreditsAreShownSeparately() : void
    {
        $html = $this->renderPage('Acting', [], [], [], [$this->movie()]);

        self::assertStringContainsString('Watched <span class="text-body-secondary">(0)</span>', $html);
        self::assertStringContainsString('On Watchlist <span class="text-body-secondary">(1)</span>', $html);
        self::assertSame(3, substr_count($html, 'href="/users/viewer/movies/12-example-movie"'));
        self::assertStringNotContainsString('role="tablist"', $html);
    }

    public function testCombinedWatchedCreditsAreDeduplicated() : void
    {
        $html = $this->renderPage('Writing', [$this->movie()], [$this->movie()], [$this->movie()], [$this->movie()]);

        self::assertStringContainsString('Watched <span class="text-body-secondary">(1)</span>', $html);
        self::assertStringContainsString('On Watchlist <span class="text-body-secondary">(1)</span>', $html);
        self::assertSame(6, substr_count($html, 'href="/users/viewer/movies/12-example-movie"'));
        self::assertSame(2, substr_count($html, 'person-movie-view-toggle"'));
        self::assertStringContainsString('data-storage-key="personWatchedView"', $html);
        self::assertStringContainsString('data-storage-key="personWatchlistView"', $html);
        self::assertStringContainsString('aria-label="Show table view for watched movies"', $html);
        self::assertStringContainsString('aria-label="Show table view for watchlist movies"', $html);
        self::assertSame(2, substr_count($html, 'class="person-movie-table table-responsive d-none"'));
        self::assertSame(2, substr_count($html, '<td>2024</td>'));
        self::assertSame(2, substr_count($html, '<td class="d-none d-md-table-cell">8</td>'));
        self::assertStringContainsString('tabindex="0" role="group" aria-label="Watched movies"', $html);
        self::assertStringContainsString('tabindex="0" role="group" aria-label="Watchlist movies"', $html);
        self::assertStringContainsString('aria-label="Scroll watched movies right"', $html);
        self::assertStringContainsString('aria-label="Scroll watchlist movies right"', $html);
        self::assertStringNotContainsString('role="tablist"', $html);
    }

    public function testCombinedCreditsRemainAlphabeticallySorted() : void
    {
        $actorMovie = array_replace($this->movie(), ['id' => 13, 'title' => 'zebra']);
        $directorMovie = array_replace($this->movie(), ['id' => 14, 'title' => 'Alpha']);
        $html = $this->renderPage('Directing', [$actorMovie], [$actorMovie], [$directorMovie], [$directorMovie]);

        self::assertStringContainsString('Watched <span class="text-body-secondary">(2)</span>', $html);
        self::assertStringContainsString('On Watchlist <span class="text-body-secondary">(2)</span>', $html);
        self::assertMatchesRegularExpression('/Watched.*14-alpha.*13-zebra.*On Watchlist.*14-alpha.*13-zebra/s', $html);
        self::assertStringNotContainsString('role="tablist"', $html);
    }

    public function testMissingMetadataAndCreditsHaveEmptyStates() : void
    {
        $html = $this->renderPage(null, [], [], [], []);

        self::assertStringContainsString('No biography available.', $html);
        self::assertStringContainsString('No watched movies for this person yet.', $html);
        self::assertStringContainsString('No watchlist movies for this person yet.', $html);
        self::assertStringNotContainsString('role="tablist"', $html);
        self::assertStringNotContainsString('<dt>Gender</dt>', $html);
        self::assertStringNotContainsString('years old', $html);
        self::assertStringNotContainsString('id="biographyToggle"', $html);
        self::assertStringNotContainsString('person-movie-view-toggle', $html);
        self::assertStringNotContainsString('id="moreSpecificMovieButton"', $html);
    }

    public function testSignedInUserSeesOptionsBesidePersonName() : void
    {
        $html = $this->renderPage(null, [], [], [], [], true);

        self::assertSame(1, substr_count($html, 'id="moreSpecificMovieButton"'));
        self::assertMatchesRegularExpression('/<header class="person-heading">\s*<h1[^>]*>Example person<\/h1>\s*<button[^>]*id="moreSpecificMovieButton"[^>]*>\s*<i class="bi bi-gear" aria-hidden="true"><\/i>\s*<\/button>\s*<\/header>/s', $html);
        self::assertStringContainsString('aria-label="Person options"', $html);
        self::assertStringContainsString('btn-outline-light person-options-button', $html);
        self::assertStringContainsString('title="Person options"', $html);
        self::assertStringContainsString('data-bs-target="#moreModal"', $html);
        self::assertStringNotContainsString('data-person-options-placement', $html);
        self::assertStringContainsString('id="moreModal"', $html);
    }

    public function testBiographyPreservesEmptyLinesAndEscapesMarkup() : void
    {
        $html = $this->renderPage(null, [], [], [], [], biography: "First line.\r\n\r\n \t\nSecond line.\r\r<script>alert(1)</script>");

        self::assertStringContainsString("First line.<br />\r\n<br />\r\n \t<br />\nSecond line.<br />\r<br />\r&lt;script&gt;alert(1)&lt;/script&gt;</p>", $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('id="biographyToggle"', $html);
    }

    public function testWhitespaceOnlyBiographyHasEmptyState() : void
    {
        $html = $this->renderPage(null, [], [], [], [], biography: "\r\n \t\n\r");

        self::assertStringContainsString('No biography available.', $html);
        self::assertStringNotContainsString('id="biographyParagraph"', $html);
        self::assertStringNotContainsString('id="biographyToggle"', $html);
    }

    private function movie() : array
    {
        return [
            'id' => 12,
            'title' => 'Example movie',
            'original_title' => null,
            'release_date' => '2024-01-01',
            'poster_path' => '/poster.jpg',
            'userRating' => 8,
        ];
    }

    private function renderPage(
        ?string $department,
        array $watchedActing,
        array $watchlistActing,
        array $watchedDirecting,
        array $watchlistDirecting,
        bool $signedIn = false,
        ?string $biography = null,
    ) : string {
        $movieApi = $this->getMockBuilder(MovieApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchWithActor', 'fetchWithDirector', 'fetchFromWatchlistWithActor', 'fetchFromWatchlistWithDirector'])
            ->getMock();
        $movieApi->expects(self::once())->method('fetchWithActor')->with(1, 2)->willReturn($watchedActing);
        $movieApi->expects(self::once())->method('fetchWithDirector')->with(1, 2)->willReturn($watchedDirecting);
        $movieApi->expects(self::once())->method('fetchFromWatchlistWithActor')->with(1, 2)->willReturn($watchlistActing);
        $movieApi->expects(self::once())->method('fetchFromWatchlistWithDirector')->with(1, 2)->willReturn($watchlistDirecting);

        return $this->twig->render('page/person.html.twig', [
            'applicationUrl' => '',
            'theme' => 'dark',
            'routeUsername' => 'viewer',
            'currentUserName' => $signedIn ? 'viewer' : null,
            'currentUserIsAdmin' => false,
            'person' => [
                'id' => 1,
                'isHiddenInTopLists' => false,
                'name' => 'Example person',
                'posterPath' => '/profile.jpg',
                'knownForDepartment' => $department,
                'gender' => '0',
                'birthDate' => null,
                'deathDate' => null,
                'placeOfBirth' => null,
                'biography' => $biography,
                'imdbUrl' => null,
                'tmdbUrl' => 'https://www.themoviedb.org/person/1',
            ],
            'watchedMovies' => $movieApi->fetchWatchedWithPerson(1, 2),
            'watchlistMovies' => $movieApi->fetchFromWatchlistWithPerson(1, 2),
        ]);
    }
}
