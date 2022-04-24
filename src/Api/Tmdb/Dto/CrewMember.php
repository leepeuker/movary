<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class CrewMember
{
    private function __construct(
        private readonly Person $person,
        private readonly string $department,
        private readonly string $job
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            Person::createFromArray($data),
            $data['department'],
            $data['job'],
        );
    }

    public function getDepartment() : string
    {
        return $this->department;
    }

    public function getJob() : string
    {
        return $this->job;
    }

    public function getPerson() : Person
    {
        return $this->person;
    }
}
