<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class CrewMember
{
    private string $department;

    private string $job;

    private Person $person;

    private function __construct(Person $person, string $department, string $job)
    {
        $this->person = $person;
        $this->department = $department;
        $this->job = $job;
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
