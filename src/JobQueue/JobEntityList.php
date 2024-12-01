<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<JobEntity>
 */
class JobEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(JobEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(JobEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
