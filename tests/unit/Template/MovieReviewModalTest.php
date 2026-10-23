<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Template;

use Movary\Domain\Movie\Review\MovieReviewEntity;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[CoversNothing]
class MovieReviewModalTest extends TestCase
{
    private Environment $twig;

    protected function setUp() : void
    {
        $this->twig = new Environment(new FilesystemLoader(dirname(__DIR__, 3) . '/templates'));
    }

    public function testViewerSeesSpoilerWarningAndReadOnlyReview() : void
    {
        $html = $this->renderReview('viewer', 'owner', $this->createReview(true));

        self::assertStringContainsString('This review contains spoilers.', $html);
        self::assertStringContainsString('id="movieReviewText" class="mb-0 text-break d-none"', $html);
        self::assertStringContainsString('A thoughtful review.', $html);
        self::assertStringNotContainsString('onclick="editMovieReview()"', $html);
        self::assertStringNotContainsString('id="movieReviewEdit"', $html);
        self::assertStringNotContainsString('>Close</button>', $html);
    }

    public function testOwnerSeesReviewWithEditModeAvailable() : void
    {
        $html = $this->renderReview('owner', 'owner', $this->createReview(false));

        self::assertStringContainsString('id="movieReviewView"', $html);
        self::assertStringContainsString('onclick="editMovieReview()"', $html);
        self::assertStringContainsString('id="movieReviewEdit" class="d-none"', $html);
        self::assertStringContainsString('Delete review', $html);
    }

    public function testOwnerWithoutReviewStartsInWriteMode() : void
    {
        $html = $this->renderReview('owner', 'owner', null);

        self::assertStringContainsString('id="reviewModalTitle">Write review', $html);
        self::assertStringContainsString('id="movieReviewEdit" class=""', $html);
        self::assertStringNotContainsString('id="movieReviewView"', $html);
        self::assertStringNotContainsString('Delete review', $html);
    }

    private function createReview(bool $containsSpoilers) : MovieReviewEntity
    {
        return MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'A thoughtful review.',
            'contains_spoilers' => (int)$containsSpoilers,
            'created_at' => '2026-10-23 12:00:00',
            'updated_at' => null,
        ]);
    }

    private function renderReview(
        string $currentUsername,
        string $routeUsername,
        ?MovieReviewEntity $review,
    ) : string {
        return $this->twig->render('component/modal-review.html.twig', [
            'currentUserName' => $currentUsername,
            'routeUsername' => $routeUsername,
            'selectedUserReview' => $review,
        ]);
    }
}
