<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Person\PersonApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

readonly class ImagesController
{
    public function __construct(
        private PersonApi $personApi,
        private MovieApi $movieApi,
        private string $publicDirectory
    ){}

    public function getMovieImage(Request $request) : Response
    {
        $resourceId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findById($resourceId);
        if($movie === null) {
            return Response::createNotFound();
        }
        $posterPath = $movie->getPosterPath();
        if($posterPath === null) {
            return Response::createNotFound();
        }
        $image = file_get_contents($this->publicDirectory . $posterPath);
        if($image === false) {
            return Response::createNotFound();
        }
        return Response::createJpeg($image);
    }

    public function getPersonImage(Request $request) : Response
    {
        $resourceId = (int)$request->getRouteParameters()['id'];
        $person = $this->personApi->findById($resourceId);
        if($person === null) {
            return Response::createNotFound();
        }
        $posterPath = $person->getPosterPath();
        if($posterPath === null) {
            return Response::createNotFound();
        }
        $image = file_get_contents($this->publicDirectory . $posterPath);
        if($image === false) {
            return Response::createNotFound();
        }
        return Response::createJpeg($image);
    }
}
