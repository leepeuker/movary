<?php

declare(strict_types=1);

namespace Movary\HttpController\ActivityPub;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\ActivityStream;
use Movary\ValueObject\Date;

class ActivityPubController
{
    private const DEFAULT_MOVIE_PAGINATION_LIMIT = 5;

    public function __construct(
        private readonly UserApi $userApi,
        private readonly MovieApi $movieApi,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly ServerSettings $serverSettings,
        private readonly MovieHistoryApi $movieHistoryApi,
    ) {}

    // #################################
    //    ordered collection generics
    // #################################

    static public function handleOrderedCollectionRequest(
        Request $request,
        string $application_url,
        int $totalItems,
        string $collection_path,
        callable $getCollectionItems,
        int $paginationLimit = 50,
    ): Response {
        $page = $request->getGetParameters()['p'];

        $lastPage = intdiv($totalItems + $paginationLimit - 1,  $paginationLimit);

        $orderedCollection = ActivityStream::createOrderedCollection(
            $application_url,
            $collection_path,
            $totalItems,
            $paginationLimit,
        );

        # return parent OrderedCollection if no page requested
        if ($page == null) {
            return Response::createActivityJson(
                Json::encode($orderedCollection)
            );
        }

        # otherwise return requested OrderedCollectionPage
        $page = (int)$page;
        # page number too big
        if ($page > $lastPage || $page < 1)
            return Response::createBadRequest();

        $items = call_user_func($getCollectionItems, $page);

        $orderedCollectionPage = ActivityStream::createOrderedCollectionPage(
            $application_url,
            $collection_path,
            $totalItems,
            $paginationLimit,
            $orderedCollection,
            $page,
            $items,
        );

        $orderedCollection->compact = true;

        return Response::createActivityJson(
            Json::encode($orderedCollectionPage)
        );
    }

    // ###########
    //    movie
    // ###########

    function getHistoryPaginated($application_url, $user, $paginationLimit, $page): array
    {
        return array_map(
            function (array $movie_arr) use ($application_url, $user) {
                $movie = $this->movieApi->findById($movie_arr["id"]);
                $movieAPObj = ActivityStream::createMovie($application_url, $user, $movie);
                $movieAPObj->compact = true;
                return $movieAPObj;
            },
            $this->movieHistoryApi->fetchHistoryPaginated(
                $user->getId(),
                $paginationLimit,
                (int)$page,
            )
        );
    }

    public function handleMovies(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $historyCount = $this->movieHistoryApi->fetchHistoryCount($user->getId());
        $collection_url = "activitypub/" . $user->getName() . "/movies";

        # function to get history
        $getHistoryPaginated = function ($page) use ($application_url, $user) {
            $movies = $this->movieHistoryApi->fetchHistoryPaginated(
                $user->getId(),
                $this::DEFAULT_MOVIE_PAGINATION_LIMIT,
                (int)$page,
            );

            return array_map(
                function (array $movie_arr) use ($application_url, $user) {
                    $movie = $this->movieApi->findById($movie_arr["id"]);
                    $movieAPObj = ActivityStream::createMovie($application_url, $user, $movie);
                    $movieAPObj->compact = true;
                    return $movieAPObj;
                },
                $this->movieHistoryApi->fetchHistoryPaginated(
                    $user->getId(),
                    $this::DEFAULT_MOVIE_PAGINATION_LIMIT,
                    (int)$page,
                )
            );
        };

        # actual logic
        return $this::handleOrderedCollectionRequest(
            $request,
            $application_url,
            $historyCount,
            $collection_url,
            $getHistoryPaginated,
            $this::DEFAULT_MOVIE_PAGINATION_LIMIT,
        );
    }
    public function handleMovie(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        # does movie exist
        $movie_id = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findById($movie_id);
        if (!$movie)
            return Response::createNotFound();

        # create ActivityPub object for Movie
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $movieObject = ActivityStream::createMovie(
            $application_url,
            $user,
            $movie
        );

        return Response::createActivityJson(
            Json::encode($movieObject)
        );
    }

    // #################
    //    user
    // #################

    public function handleActor(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        # create ActivityPub object for user
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $actorObject = ActivityStream::createPerson(
            $application_url,
            $application_name,
            $user
        );

        return Response::createActivityJson(
            Json::encode(
                $actorObject
            )
        );
    }

    public function handleActorInbox(): Response
    {
        // POST requests only …
        return Response::create(
            StatusCode::createOk(),
            "actor inbox goes here"
        );
    }

    public function handleActorOutbox(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "actor outbox goes here"
        );
    }

    public function handleActorFollowing(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "actor following goes here"
        );
    }

    public function handleActorFollowers(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "actor followers goes here"
        );
    }

    // ################
    //    user plays
    // ################

    public function handleActorPlays(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "plays orderedcollection goes here"
        );
    }
    public function handleActorPlay(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        # does movie exist
        $movie_id = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findByIdFormatted($movie_id);
        if (!$movie)
            return Response::createNotFound();

        # does watchdate exist
        $request_watchdate = (string)$request->getRouteParameters()['watchdate'];
        $watch_dates = array_filter(
            $this->movieApi->fetchHistoryByMovieId($movie_id, $user->getId()),
            fn($wd) => $wd["watched_at"] == $request_watchdate
        );
        if (count($watch_dates) < 1)
            return Response::createNotFound();
        $watch = $watch_dates[0];

        # required variables to create object
        $application_url = $this->applicationUrlService->createApplicationUrl();

        # turn Movie into AP object with type "Video"
        $movie['personalRating'] = $this->movieApi->findUserRating($movie_id, $user->getId())?->asInt();
        $movie['@context'] = "https://www.w3.org/ns/activitystreams";
        $movie['type'] = "Video";
        $movie['id'] = $application_url . "/users/" . $user->getName() . "/movies/" . $movie['id'];
        $movie['name'] = $movie['title'];

        # create ActivityPub Note
        $note = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "type" => "Note",
            "id" => (
                $application_url
                . "/activitypub/users/"
                . $user->getName()
                . "/plays/"
                . $movie_id
                . "/"
                . $watch["watched_at"]
            ),
            "summary" => $user->getName() . " watched " . $movie["title"],
            "content" => $user->getName() . " watched " . $movie["title"],
            "published" => "???",
            "actor" => $application_url . "/activitypub/users/" . $user->getName(),
            "inReplyTo" => $movie,
            "attributedTo" => $application_url . "/activitypub/users/" . $user->getName(),
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                $application_url . "/activitypub/users/alifeee/followers"
            ],
        ];

        return Response::createActivityJson(
            Json::encode($note)
        );
    }

    // ####################
    //    user watchlist
    // ####################

    public function handleActorWatchlist(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "watchlist orderedcollection goes here"
        );
    }
    public function handleActorWatchlistItem(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            "watchlist item goes here"
        );
    }
}
