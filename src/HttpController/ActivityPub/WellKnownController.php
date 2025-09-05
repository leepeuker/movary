<?php

declare(strict_types=1);

namespace Movary\HttpController\ActivityPub;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\Http\Header;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserRepository;
use Movary\Service\ServerSettings;
use Movary\Service\ApplicationUrlService;
use Movary\Util\Json;
use Twig\Environment;

class WellKnownController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly ServerSettings $serverSettings,
        private readonly UserRepository $repository,
    ) {}

    public function handleHostMeta(): Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('activitypub/host-meta.xml.twig', []),
            [Header::createContentTypeXRDXML()],
        );
    }

    public function handleNodeInfo(): Response
    {
        $application_version = $this->serverSettings->getApplicationVersion();
        $application_name = $this->serverSettings->getApplicationName();
        $total_users = $this->repository->getCountOfUsers();

        return Response::createActivityJson(
            Json::encode([
                "version" => "2.1",
                "software" => [
                    "name" => "movary",
                    "version" => $application_version,
                    "repository" => "https://github.com/leepeuker/movary/",
                ],
                "protocols" => ["activitypub"],
                "services" => [
                    "inbound" => [],
                    "outbound" => [],
                ],
                "openRegistrations" => false,
                "usage" => [
                    "users" => [
                        "total" => $total_users,
                    ],
                ],
                "metadata" => [
                    "nodeName" => $application_name,
                ]
            ])
        );
    }

    public function handleNodeInfoMeta(): Response
    {
        $application_url = $this->applicationUrlService->createApplicationDomain();

        return Response::createActivityJson(
            Json::encode([
                "links" => [
                    [
                        "rel" => "self",
                        "type" => "http://nodeinfo.diaspora.software/ns/schema/2.1",
                        "href" => $application_url . "/nodeinfo/2.1",
                    ]
                ]
            ])
        );
    }

    public function handleWebfinger(Request $request): Response
    {
        $resource = $request->getGetParameters()['resource'] ?? null;

        // no resource provided
        // example bad request: http://movary.test/.well-known/webfinger
        if (!$resource) {
            return Response::createBadRequest("no resource query string provided");
        }

        // resource is not string
        // example bad request: http://movary.test/.well-known/webfinger?resource[]=acct:alifeee@movary.test&resource[]=2
        if (!is_string($resource)) {
            return Response::createBadRequest("resource query item was not a string. was it an array?");
        }

        preg_match('/acct:[@~]?([^@]+)@(.+)$/', $resource, $matches);

        // no domain provided
        // example bad request: http://movary.test/.well-known/webfinger?resource=acct:alifeee
        if (count($matches) != 3) {
            return Response::createBadRequest("could not parse username and host from resource");
        }

        $username = $matches[1];
        $domain = $matches[2];

        $application_url = $this->applicationUrlService->createApplicationUrl();
        $application_domain = $this->applicationUrlService->createApplicationDomain();

        // domain does not match
        // example bad request: http://movary.test/.well-known/webfinger?resource=acct:alifeee@movary.local
        if ($domain !== $application_domain) {
            return Response::createBadRequest(
                "domain provided (" . $domain .
                    ") is not the same as current domain (" . $application_domain .
                    ")"
            );
        }

        // user does not exist
        $requested_user = $this->userApi->findUserByName($username);
        if ($requested_user === null) {
            return Response::create(
                StatusCode::createNotFound(),
                "cannot find user " . $username . " on " . $domain
            );
        }

        // user profile is not visible for web requests
        if ($this->authenticationService->isUserPageVisibleForWebRequest($requested_user) === false) {
            return Response::createUnauthorized();
        }

        $response = [
            "subject" => "acct:" . $matches[0] . "@" . $matches[1],
            "links" => [
                [
                    "rel" => "self",
                    "type" => "application/activity+json",
                    "href" => $application_url . "/activitypub/users/" . $username,
                ],
            ]
        ];

        return Response::createActivityJson(
            Json::encode($response)
        );
    }
}
