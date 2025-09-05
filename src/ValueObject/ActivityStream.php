<?php

declare(strict_types=1);

namespace Movary\ValueObject;

use JsonSerializable;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Date;
use Movary\Util\Json;
use RuntimeException;

class ActivityStream implements JsonSerializable
{
    public bool $compact = false;

    private function __construct(
        private readonly string $type = 'Object',
        private readonly null|string $id = null,
        private readonly null|string $name = null,
        private array $extra_AP_items = [],
        private readonly string $context = "https://www.w3.org/ns/activitystreams",
    ) {
        $this->check_if_using_reserved_key($extra_AP_items);
    }

    private function check_if_using_reserved_key(array $array): void
    {
        if (
            key_exists("@context", $array)
            || key_exists("id", $array)
            || key_exists("type", $array)
            || key_exists("name", $array)
        ) {
            throw new RuntimeException(
                "trying to override ActivityPub keys in extra keys object! "
                    . "Do not provide @context, id, type, or name. Pass to constructor"
            );
        }
    }

    public function update_attributes(
        array $attributes,
    ) {
        $this->check_if_using_reserved_key($attributes);
        $this->extra_AP_items = [
            ...$this->extra_AP_items,
            ...$attributes
        ];
    }

    public static function createNote(
        string $id,
        string $name,
        Date $published,
        ActivityStream $attributedTo,
        ActivityStream $cc,
        string $content,
    ): ActivityStream {
        $attributedTo->compact = true;
        $cc->compact = true;
        return new self(
            "Note",
            $id,
            $name,
            [
                "published" => $published,
                "attributedTo" => $attributedTo,
                "content" => $content,
                "to" => [
                    "https://www.w3.org/ns/activitystreams#Public",
                ],
                "cc" => [
                    $cc,
                ]
            ]
        );
    }

    public static function createOrderedCollection(
        string $application_url,
        string $collection_path,
        int $totalItems,
        int $itemsPerPage,
        string $name = "",
    ) {
        $collection_url = $application_url . "/" . $collection_path;
        $lastPage = intdiv($totalItems + $itemsPerPage - 1,  $itemsPerPage);

        $orderedCollection = new self(
            "OrderedCollection",
            $collection_url,
            $name,
            [
                "totalItems" => $totalItems,
                "startIndex" => 0,
            ]
        );

        $orderedCollectionPage1 = ActivityStream::createOrderedCollectionPage(
            $application_url,
            $collection_url,
            $totalItems,
            $itemsPerPage,
            $orderedCollection,
            1,
            [],
        );
        $orderedCollectionPage1->compact = true;
        $orderedCollectionPage2 = ActivityStream::createOrderedCollectionPage(
            $application_url,
            $collection_url,
            $totalItems,
            $itemsPerPage,
            $orderedCollection,
            $lastPage,
            [],
        );
        $orderedCollectionPage2->compact = true;

        $orderedCollection->update_attributes(
            ["first" => $orderedCollectionPage1, "last" => $orderedCollectionPage2]
        );

        return $orderedCollection;
    }

    public static function createOrderedCollectionPage(
        string $application_url,
        string $collection_path,
        int $totalItems,
        int $itemsPerPage,
        null|ActivityStream $parent_collection,
        int $currentPage,
        array $contents,
        string $name = "",
    ) {
        $lastPage = intdiv($totalItems + $itemsPerPage - 1,  $itemsPerPage);
        $nextprev = [];
        $collection_url = $application_url . "/" . $collection_path;
        if ($currentPage > 1)
            $nextprev["prev"] = $collection_url . "?p=" . ($currentPage - 1);
        if ($currentPage < $lastPage)
            $nextprev["next"] = $collection_url . "?p=" . ($currentPage + 1);

        return new self(
            "OrderedCollectionPage",
            $collection_url . "?p=" . $currentPage,
            $name,
            [
                "totalItems" => $totalItems,
                "partOf" => $parent_collection,
                ...$nextprev,
                "orderedItems" => $contents
            ]
        );
    }

    public static function createPerson(
        string $application_url,
        string $application_name,
        UserEntity $user,
    ): ActivityStream {
        $user_root_url = $application_url . "/activitypub/users";
        $user_url = $user_root_url . "/" . $user->getName();
        return new self(
            "Person",
            $user_url,
            $user->getName() . " on " . $application_name,
            [
                "following" => $user_url . "/" . "following",
                "followers" => $user_url . "/" . "followers",
                "inbox" => $user_url . "/" . "inbox",
                "outbox" => $user_url . "/" . "outbox",
                "preferredUsername" => $user->getName(),
                "url" => $user_url,
                "manuallyApproveFollowers" => false,
                "discoverable" => true,
                "indexable" => true,
                "published" => "???",
                "publicKey" => [
                    "id" => $user_url . "#main-key",
                    "owner" => $user_url,
                    "publicKeyPem" => "------BEGIN PUBLIC KEY-----………",
                ]
            ]
        );
    }

    public static function createMovie(
        $application_url,
        UserEntity $user,
        MovieEntity $movie,
    ) {
        $user_root_url = $application_url . "/activitypub/users";
        $user_url = $user_root_url . "/" . $user->getName();
        $movie_root_url = $user_url . "/movies";
        $movie_url = $movie_root_url . "/" . $movie->getId();
        return new self(
            "Video",
            $movie_url,
            $movie->getTitle(),
            [
                "releaseDate" => $movie->getReleaseDate(),
                "summary" => $movie->getOverview(),
                "originalLanguage" => $movie->getOriginalLanguage(),
                "movaryId" => $movie->getId(),
                "tmdbId" => $movie->getTmdbId(),
            ]
        );
    }

    public function jsonSerialize(): mixed
    {
        if ($this->compact) {
            return $this->id;
        }
        return [
            "@context" => $this->context,
            "id" => $this->id,
            "type" => $this->type,
            "name" => $this->name,
            ...$this->extra_AP_items
        ];
    }
}
