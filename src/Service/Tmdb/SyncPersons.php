<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncPersons
{
    public function __construct(
        private readonly SyncPerson $syncPerson,
        private readonly PersonApi $personApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncPersons(?int $maxAgeInHours = null, ?int $movieCountSyncThreshold = null) : void
    {
        $this->personApi->deleteAllNotReferenced();

        $persons = $this->personApi->fetchAllOrderedByLastUpdatedAtTmdbAsc($movieCountSyncThreshold);

        foreach ($persons as $person) {
            $person = PersonEntity::createFromArray($person);

            $updatedAtTmdb = $person->getUpdatedAtTmdb();
            if ($maxAgeInHours !== null && $updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb, $maxAgeInHours) === false) {
                continue;
            }

            try {
                $this->syncPerson->syncPerson($person->getTmdbId());
            } catch (Throwable $t) {
                $this->logger->warning('Could not sync person with id "' . $person->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }
        }
    }

    private function syncExpired(DateTime $updatedAtTmdb, int $maxAgeInDays = null) : bool
    {
        return DateTime::create()->differenceInHours($updatedAtTmdb) > $maxAgeInDays;
    }
}
