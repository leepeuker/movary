<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\MovieRatingRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieRatingController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieRatingRequestMapper $movieRatingRequestMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function deleteRating(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $this->movieApi->updateUserRating($movieId, $userId, null);

        return Response::createNoContent();
    }

    public function getRating(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $rating = $this->movieApi->findUserRating($movieId, $userId);
        if ($rating === null) {
            return Response::createNotFound();
        }

        return Response::createSensitiveJson(Json::encode(['rating' => $rating->asInt()]));
    }

    public function saveRating(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $rating = $this->movieRatingRequestMapper->mapRating($request);
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $this->movieApi->updateUserRating($movieId, $userId, $rating);

        return Response::createNoContent();
    }

    private function findMovieId(Request $request) : ?int
    {
        $movieId = (int)$request->getRouteParameters()['movaryId'];

        return $this->movieApi->findById($movieId) === null ? null : $movieId;
    }
}
