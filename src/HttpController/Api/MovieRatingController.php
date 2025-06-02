<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

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
        $userId = $this->authenticationService->getUserIdByApiToken($request);
        $tmdbId = $request->getGetParameters()['tmdbId'] ?? null;

        $userRating = null;
        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if($userId === null) {
            return Response::createForbidden();
        }
        if ($movie !== null) {
            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);
        }

        return Response::createJson(
            Json::encode(['personalRating' => $userRating?->asInt()]),
        );
    }

    public function updateRating(Request $request) : Response
    {
        $userId = $this->authenticationService->getUserIdByApiToken($request);
        if($userId === null) {
            return Response::createForbidden();
        }

        if ($this->userApi->fetchUser($userId)->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $postParameters = Json::decode($request->getBody());

        $personalRating = null;
        if (empty($postParameters['rating']) === false && $postParameters['rating'] !== 0) {
            $personalRating = PersonalRating::create((int)$postParameters['rating']);
        }

        $this->movieApi->updateUserRating($movieId, $userId, $personalRating);

        return Response::create(StatusCode::createNoContent());
    }
}
