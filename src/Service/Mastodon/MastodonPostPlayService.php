<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\Service\SlugifyService;
use Movary\ValueObject\Date;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;

class MastodonPostPlayService
{
    const int MASTODON_MESSAGE_MAX_LENGTH = 500;

    public function __construct(
        private readonly MastodonPostService $mastodonPostService,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SlugifyService $slugify,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly MovieHistoryApi $movieHistoryApi,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing parameter: userId');
        }

        $movieId = $job->getParameters()['movieId'] ?? null;
        if ($movieId === null) {
            throw new RuntimeException('Missing parameter: movieId');
        }

        $watchDate = $job->getParameters()['watchDate'] ?? null;
        if ($watchDate !== null) {
            $watchDate = Date::createFromString($watchDate);
        }

        $this->postPlay($userId, $movieId, $watchDate);
    }

    public function postPlay(int $userId, int $movieId, ?Date $watchDate) : void
    {
        $movie = $this->movieApi->findById($movieId);
        if ($movie === null) {
            throw new RuntimeException('Movie does not exist with id: ' . $movieId);
        }

        $user = $this->userApi->findUserById($userId);
        if ($user === null) {
            throw new RuntimeException('User does not exist with id: ' . $movieId);
        }

        $mastodonMessageBase = 'Watched movie: ' . $movie->getTitle() . ' (' . $movie->getReleaseDate()?->format('Y') . ')';
        $mastodonMessageRating = '';
        $mastodonMessageComment = '';
        $mastodonMessageMovieUrl = "\n\n" . $this->generateMovieUrl($user, $movieId, $movie);

        $personalRating = $this->movieApi->findUserRating($movieId, $userId)?->asInt();
        if ($personalRating !== null) {
            $mastodonMessageRating = $this->formatUserRating($personalRating);
        }

        $watchHistoryComment = $this->movieHistoryApi->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchDate)?->getComment();
        if ($watchHistoryComment != null) {
            $mastodonMessageComment = $this->formatUserComment($watchHistoryComment);
        }

        $mastodonMessageLength = strlen($mastodonMessageBase . $mastodonMessageRating . $mastodonMessageComment . $mastodonMessageMovieUrl);
        if ($mastodonMessageLength >= self::MASTODON_MESSAGE_MAX_LENGTH) {
            $mastodonMessageCommentMaxLength = self::MASTODON_MESSAGE_MAX_LENGTH - strlen($mastodonMessageBase . $mastodonMessageRating . $mastodonMessageMovieUrl) - 2;
            $mastodonMessageComment = substr($mastodonMessageComment, 0, $mastodonMessageCommentMaxLength) . "…\n";
        }

        $mastodonMessage = $mastodonMessageBase . $mastodonMessageRating . $mastodonMessageComment . $mastodonMessageMovieUrl;

        $this->mastodonPostService->postMessageForUser($userId, $mastodonMessage);
    }

    private function formatUserComment(string $watchHistoryComment) : string
    {
        return "\nComment: " . $watchHistoryComment;
    }

    private function formatUserRating(int $personalRating) : string
    {
        return "\nRated: " . str_repeat("★", $personalRating) . str_repeat("☆", 10 - $personalRating);
    }

    private function generateMovieUrl(UserEntity $user, int $movieId, MovieEntity $movie) : string
    {
        return $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create(
                '/users/' . $user->getName() . '/movies/' . $movieId . '-' . $this->slugify->slugify($movie->getTitle()),
            ),
        );
    }
}
