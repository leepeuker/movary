<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\HttpController\Api\RequestMapper\MovieReviewRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieReviewResponseMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieReviewController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieReviewApi $movieReviewApi,
        private readonly MovieReviewRequestMapper $movieReviewRequestMapper,
        private readonly MovieReviewResponseMapper $movieReviewResponseMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function deleteReview(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $this->movieReviewApi->delete($movieId, $userId);

        return Response::createNoContent();
    }

    public function getReview(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $review = $this->movieReviewApi->find($movieId, $userId);
        if ($review === null) {
            return Response::createNotFound();
        }

        return Response::createSensitiveJson(
            Json::encode($this->movieReviewResponseMapper->mapReview($review)),
        );
    }

    public function saveReview(Request $request) : Response
    {
        $movieId = $this->findMovieId($request);
        if ($movieId === null) {
            return Response::createNotFound();
        }

        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $review = $this->movieReviewRequestMapper->mapReview($request);

        $this->movieReviewApi->save(
            $movieId,
            $userId,
            $review['review'],
            $review['containsSpoilers'],
        );

        return Response::createNoContent();
    }

    private function findMovieId(Request $request) : ?int
    {
        $movieId = (int)$request->getRouteParameters()['movaryId'];

        return $this->movieApi->findById($movieId) === null ? null : $movieId;
    }
}
