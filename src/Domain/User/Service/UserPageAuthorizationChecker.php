<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;

class UserPageAuthorizationChecker
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor(int $movieId) : array
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false) {
            return $this->userApi->fetchAllHavingWatchedMoviePublicVisibleUsernames($movieId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieInternVisibleUsernames($movieId);
    }

    public function fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor(int $personId) : array
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false) {
            return $this->userApi->fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames($personId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames($personId);
    }

    public function fetchAllVisibleUsernamesForCurrentVisitor() : array
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false) {
            return $this->userApi->fetchAllPublicVisibleUsernames();
        }

        return $this->userApi->fetchAllInternVisibleUsernames();
    }

    public function findUserIdIfCurrentVisitorIsAllowedToSeeUser(Request $request) : ?int
    {
        $requestUsername = (string)$request->getRouteParameters()['username'];

        $requestedUser = $this->userApi->findUserByName($requestUsername);
        if ($requestedUser === null) {
            return null;
        }

        $requestedUserId = $requestedUser->getId();

        if ($this->authenticationService->isUserPageVisibleForCurrentUser($requestedUser->getPrivacyLevel(), $requestedUserId) === false) {
            return null;
        }

        return $requestedUserId;
    }
}
