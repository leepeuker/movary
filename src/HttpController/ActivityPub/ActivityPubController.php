<?php

declare(strict_types=1);

namespace Movary\HttpController\ActivityPub;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\ActivityStream;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class ActivityPubController
{
    private const DEFAULT_MOVIE_PAGINATION_LIMIT = 50;
    private const DEFAULT_PLAYS_PAGINATION_LIMIT = 50;
    private const DEFAULT_WATCHLIST_PAGINATION_LIMIT = 50;

    public function __construct(
        private readonly UserApi $userApi,
        private readonly MovieApi $movieApi,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly ServerSettings $serverSettings,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
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

    public function handleMovies(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $historyCount = $this->movieApi->fetchTotalPlayCountUnique($user->getId());
        $collection_url = "activitypub/users/" . $user->getName() . "/movies";

        # function to get history
        $getHistoryPaginated = function ($page) use ($application_url, $user) {
            return array_map(
                function (array $movie_arr) use ($application_url, $user) {
                    $movie = $this->movieApi->findById($movie_arr["id"]);
                    $movieAPObj = ActivityStream::createMovie($application_url, $user, $movie);
                    $movieAPObj->compact = true;
                    return $movieAPObj;
                },
                $this->movieHistoryApi->fetchUniqueWatchedMoviesPaginated(
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

    public function handleActorOutbox(Request $request): Response
    {

        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $historyCount = $this->movieHistoryApi->fetchHistoryCount($user->getId());
        $collection_path = "activitypub/users/" . $user->getName() . "/outbox";

        # function to get all plays in order
        $allWatchlistItems = $this->movieWatchlistApi->fetchAllWatchlistItems($user->getId());
        $allWatchlistCreateObjects = array_map(
            function ($watchlistItem) use ($application_url, $application_name, $user) {
                $movie = $this->movieApi->findById($watchlistItem["id"]);

                $watchlistObject = ActivityStream::createWatchlistItem(
                    $application_url,
                    $application_name,
                    $user,
                    $movie,
                    $watchlistItem,
                );
                $watchlistObject->compact = true;

                $actorObject = ActivityStream::createPerson($application_url, $application_name, $user);
                $actorObject->compact = true;

                $createObject = ActivityStream::createCreate(
                    $application_url . "/activitypub/users/" . $user->getName() . "/create/watchlist/" . $movie->getId(),
                    "created a watchlist item",
                    $actorObject,
                    $watchlistObject,
                    DateTime::createFromString($watchlistItem["added_at"]),
                );

                return $createObject;
            },
            $allWatchlistItems,
        );
        $allPlays = $this->movieHistoryApi->fetchHistoryPaginated($user->getId(), 999999, 1);
        $allPlaysCreateObjects = array_map(
            function ($watch) use ($application_url, $application_name, $user) {
                $movie = $this->movieApi->findById($watch["id"]);
                $playObject = ActivityStream::createPlay(
                    $application_url,
                    $application_name,
                    $user,
                    $movie,
                    $watch,
                );
                $playObject->compact = true;

                $actorObject = ActivityStream::createPerson($application_url, $application_name, $user);
                $actorObject->compact = true;

                $createObject = ActivityStream::createCreate(
                    $application_url . "/activitypub/users/" . $user->getName() . "/create/play/" . $movie->getId() . "/" . $watch["watched_at"],
                    "marked a movie as played",
                    $actorObject,
                    $playObject,
                    DateTime::createFromString($watch["created_at"]),
                );

                return $createObject;
            },
            $allPlays,
        );

        $notes = [
            ...$allWatchlistCreateObjects,
            ...$allPlaysCreateObjects,
        ];

        usort($notes, function ($a, $b) {
            return strcmp(
                $a->get_published(),
                $b->get_published(),
            );
        });

        # actual logic
        $orderedCollection = ActivityStream::createOrderedCollectionWithItems(
            $application_url,
            $collection_path,
            $historyCount,
            $notes,
        );

        return Response::createActivityJson(
            Json::encode($orderedCollection)
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

    public function handleActorPlays(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $historyCount = $this->movieHistoryApi->fetchHistoryCount($user->getId());
        $collection_url = "activitypub/users/" . $user->getName() . "/plays";

        # function to get all plays in order
        $getPlaysPaginated = function ($page) use ($application_url, $application_name, $user) {
            return array_map(
                function (array $movie_arr) use ($application_url, $application_name, $user) {
                    $movie = $this->movieApi->findById($movie_arr["id"]);
                    $playObject = ActivityStream::createPlay(
                        $application_url,
                        $application_name,
                        $user,
                        $movie,
                        $movie_arr,
                    );
                    $playObject->compact = true;
                    return $playObject;
                },
                $this->movieHistoryApi->fetchHistoryPaginated(
                    $user->getId(),
                    $this::DEFAULT_PLAYS_PAGINATION_LIMIT,
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
            $getPlaysPaginated,
            $this::DEFAULT_PLAYS_PAGINATION_LIMIT,
        );
    }
    public function handleActorPlaysForMovie(Request $request): Response
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

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $history = $this->movieApi->fetchHistoryByMovieId($movie->getId(), $user->getId());
        $historyCount = count($history);
        $collection_url = "activitypub/users/" . $user->getName() . "/plays/" . $movie->getId();

        # function to get all plays for single film in order
        $getMoviePlaysPaginated = function () use ($application_url, $application_name, $history, $user) {
            return array_map(
                function (array $movie_arr) use ($application_url, $application_name, $user) {
                    $movie = $this->movieApi->findById($movie_arr["movie_id"]);
                    $playObject = ActivityStream::createPlay(
                        $application_url,
                        $application_name,
                        $user,
                        $movie,
                        $movie_arr,
                    );
                    $playObject->compact = true;
                    return $playObject;
                },
                $history,
            );
        };

        # actual logic
        return $this::handleOrderedCollectionRequest(
            $request,
            $application_url,
            $historyCount,
            $collection_url,
            $getMoviePlaysPaginated,
            $this::DEFAULT_PLAYS_PAGINATION_LIMIT,
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
        $movie = $this->movieApi->findById($movie_id);
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
        $watch = array_values($watch_dates)[0];

        # create play object
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $playObject = ActivityStream::createPlay(
            $application_url,
            $application_name,
            $user,
            $movie,
            $watch,
        );

        return Response::createActivityJson(
            Json::encode($playObject)
        );
    }

    // ####################
    //    user watchlist
    // ####################

    public function handleActorWatchlist(Request $request): Response
    {
        # does user exist
        $user = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if (!$user)
            return Response::createNotFound();

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $watchlistCount = $this->movieWatchlistApi->fetchWatchlistCount($user->getId());
        $collection_url = "activitypub/users/" . $user->getName() . "/watchlist";

        # function to get all watchlist items in order
        $getWatchlistPaginated = function ($page) use ($application_url, $application_name, $user) {
            return array_map(
                function (array $movie_arr) use ($application_url, $application_name, $user) {
                    $movie = $this->movieApi->findById($movie_arr["id"]);
                    $movieAPObj = ActivityStream::createMovie($application_url, $user, $movie);
                    $movieAPObj->compact = true;
                    return $movieAPObj;
                },
                $this->movieWatchlistApi->fetchWatchlistPaginated(
                    $user->getId(),
                    $this::DEFAULT_WATCHLIST_PAGINATION_LIMIT,
                    (int)$page,
                )
            );
        };

        # actual logic
        return $this::handleOrderedCollectionRequest(
            $request,
            $application_url,
            $watchlistCount,
            $collection_url,
            $getWatchlistPaginated,
            $this::DEFAULT_WATCHLIST_PAGINATION_LIMIT,
        );
    }

    public function handleActorWatchlistItem(Request $request): Response
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

        # is movie in watchlist
        if (!$this->movieWatchlistApi->hasMovieInWatchlist($user->getId(), $movie->getId()))
            return Response::createNotFound();

        $watchlist_item = $this->movieWatchlistApi->fetchWatchlistItem($user->getId(), $movie->getId());

        # create watchlist object (Note)
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $watchlistObject = ActivityStream::createWatchlistItem(
            $application_url,
            $application_name,
            $user,
            $movie,
            $watchlist_item,
        );

        return Response::createActivityJson(
            Json::encode($watchlistObject)
        );
    }

    // ####################
    //    meta create AP
    // ####################

    public function handleCreatePlayItem(Request $request): Response
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

        # does watchdate exist
        $request_watchdate = (string)$request->getRouteParameters()['watchdate'];
        $watch_dates = array_filter(
            $this->movieApi->fetchHistoryByMovieId($movie_id, $user->getId()),
            fn($wd) => $wd["watched_at"] == $request_watchdate
        );
        if (count($watch_dates) < 1)
            return Response::createNotFound();
        $watch = array_values($watch_dates)[0];

        # create play object (Note)
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $playObject = ActivityStream::createPlay(
            $application_url,
            $application_name,
            $user,
            $movie,
            $watch,
        );

        $actorObject = ActivityStream::createPerson($application_url, $application_name, $user);
        $actorObject->compact = true;

        $createObject = ActivityStream::createCreate(
            $application_url . "/activitypub/users/" . $user->getName() . "/create/watchlist/" . $movie->getId(),
            "marked a movie as played",
            $actorObject,
            $playObject,
            DateTime::createFromString($watch["created_at"]),
        );

        return Response::createActivityJson(
            Json::encode($createObject)
        );
    }

    public function handleCreateWatchlistItem(Request $request): Response
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

        # is movie in watchlist
        if (!$this->movieWatchlistApi->hasMovieInWatchlist($user->getId(), $movie->getId()))
            return Response::createNotFound();

        $watchlist_item = $this->movieWatchlistApi->fetchWatchlistItem($user->getId(), $movie->getId());

        # create watchlist object (Note)
        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_name = $this->serverSettings->getApplicationName();
        $watchlistObject = ActivityStream::createWatchlistItem(
            $application_url,
            $application_name,
            $user,
            $movie,
            $watchlist_item,
        );

        $actorObject = ActivityStream::createPerson($application_url, $application_name, $user);
        $actorObject->compact = true;

        $createObject = ActivityStream::createCreate(
            $application_url . "/activitypub/users/" . $user->getName() . "/create/watchlist/" . $movie->getId(),
            "created a watchlist item",
            $actorObject,
            $watchlistObject,
            DateTime::createFromString($watchlist_item["added_at"]),
        );

        return Response::createActivityJson(
            Json::encode($createObject)
        );
    }
}
