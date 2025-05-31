<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Radarr\RadarrFeedUrlGenerator;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Http\Response;

class RadarrController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly RadarrFeedUrlGenerator $radarrFeedUrlGenerator,
    ) {
    }

    public function deleteRadarrFeedUrl() : Response
    {
        $this->userApi->deleteRadarrFeedId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function regenerateRadarrFeedUrl() : Response
    {
        $feedId = $this->userApi->regenerateRadarrFeedId($this->authenticationService->getCurrentUserId());
        $feedUrl = $this->radarrFeedUrlGenerator->generateUrl($feedId);

        return Response::createJson(Json::encode(['url' => $feedUrl]));
    }
}
