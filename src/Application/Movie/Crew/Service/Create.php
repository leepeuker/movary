<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew\Service;

use Movary\Application\Movie\Crew\Repository;

class Create
{
    private const RELEVANT_JOBS = [
        'Screenplay',
        'Director',
        'Producer',
        'Story',
        'Executive Producer',
        'Director of Photography',
        'Editor',
        'Director of Photography',
        'Original Music Composer',
        'Author',
        'Music Director',
    ];

    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(int $movieId, int $personId, string $job, string $department, int $position) : void
    {
        if (in_array($job, self::RELEVANT_JOBS, true) === false) {
            return;
        }

        $this->repository->create($movieId, $personId, $job, $department, $position);
    }
}
