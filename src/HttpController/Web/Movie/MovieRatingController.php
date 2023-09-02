<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;

class MovieRatingController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function fetchMovieRatingByTmdbdId(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $tmdbId = $request->getGetParameters()['tmdbId'] ?? null;

        $userRating = null;
        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if ($movie !== null) {
            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);
        }

        return Response::createJson(
            Json::encode(['personalRating' => $userRating?->asInt()]),
        );
    }

    public function updateRating(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createForbidden();
        }

        $userId = $this->authenticationService->getCurrentUserId();

        if ($this->userApi->fetchUser($userId)->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $postParameters = $request->getPostParameters();

        $personalRating = null;
        if (empty($postParameters['rating']) === false && $postParameters['rating'] !== 0) {
            $personalRating = PersonalRating::create((int)$postParameters['rating']);
        }

        $this->movieApi->updateUserRating($movieId, $this->authenticationService->getCurrentUserId(), $personalRating);

        return Response::create(StatusCode::createNoContent());
    }
}
