<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\UserPreferencesRequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserPreferencesController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly UserPreferencesRequestMapper $requestMapper,
    ) {
    }

    public function getPreferences(Request $request) : Response
    {
        $user = $this->findAuthenticatedUser($request);
        if ($user === null) {
            return Response::createBearerUnauthorized();
        }

        return Response::createSensitiveJson(Json::encode([
            'username' => $user->getName(),
            'privacyLevel' => $user->getPrivacyLevel(),
            'dateFormatId' => $user->getDateFormatId(),
            'movieTitleDisplay' => (string)$user->getMovieTitleDisplay(),
            'country' => $user->getCountry(),
            'watchlistAutomaticRemovalEnabled' => $user->hasWatchlistAutomaticRemovalEnabled(),
            'displayCharacterNames' => $user->getDisplayCharacterNames(),
            'displayTmdbRatings' => $user->getDisplayTmdbRating(),
            'displayImdbRatings' => $user->getDisplayImdbRating(),
        ]));
    }

    public function updatePreferences(Request $request) : Response
    {
        $user = $this->findAuthenticatedUser($request);
        if ($user === null) {
            return Response::createBearerUnauthorized();
        }

        $preferences = $this->requestMapper->mapUpdate($request);
        try {
            $this->userApi->updatePreferences($user->getId(), $preferences);
        } catch (UsernameInvalidFormat) {
            throw InvalidApiRequest::invalidField('username', 'must contain only letters and digits');
        } catch (UsernameNotUnique) {
            throw InvalidApiRequest::invalidField('username', 'must be unique');
        }

        return Response::createNoContent();
    }

    private function findAuthenticatedUser(Request $request) : ?UserEntity
    {
        $authenticatedUser = $this->authenticationService->authenticateApiToken($request);

        return $authenticatedUser === null ? null : $this->userApi->findUserById($authenticatedUser->getUserId());
    }
}
