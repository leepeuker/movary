<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use InvalidArgumentException;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class MovieReviewController
{
    public function __construct(
        private readonly MovieReviewApi $reviewApi,
        private readonly MovieApi $movieApi,
        private readonly CurrentWebUser $currentWebUser,
    ) {
    }

    public function delete(Request $request) : Response
    {
        $currentUser = $this->currentWebUser->requireUser();
        if ($currentUser->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $this->reviewApi->delete((int)$request->getRouteParameters()['id'], $currentUser->getId());

        return Response::create(StatusCode::createNoContent());
    }

    public function save(Request $request) : Response
    {
        $currentUser = $this->currentWebUser->requireUser();
        if ($currentUser->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $movieId = (int)$request->getRouteParameters()['id'];
        if ($this->movieApi->findById($movieId) === null) {
            return Response::createNotFound();
        }

        $postParameters = $request->getPostParameters();
        $review = $postParameters['review'] ?? null;
        if (is_string($review) === false) {
            return Response::createBadRequest('A movie review is required.');
        }

        try {
            $this->reviewApi->save(
                $movieId,
                $currentUser->getId(),
                $review,
                isset($postParameters['containsSpoilers']),
            );
        } catch (InvalidArgumentException $exception) {
            return Response::createBadRequest($exception->getMessage());
        }

        return Response::create(StatusCode::createNoContent());
    }
}
