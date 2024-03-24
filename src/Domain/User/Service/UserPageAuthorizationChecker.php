<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;

readonly class UserPageAuthorizationChecker
{
    public function __construct(
        private UserApi $userApi,
        private Authentication $authenticationService,
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

    public function fetchAllVisibleUsernamesForCurrentVisitor(Request $request) : array
    {
        if ($this->authenticationService->createAuthenticationObjectDynamically($request) === null) {
            return $this->userApi->fetchAllPublicVisibleUsernames();
        }

        return $this->userApi->fetchAllInternVisibleUsernames();
    }
}
