<?php declare(strict_types=1);

namespace Movary\JobQueue;

use JsonSerializable;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobEntity implements JsonSerializable
{
    private function __construct(
        private readonly int $id,
        private readonly JobType $type,
        private readonly JobStatus $status,
        private readonly ?int $userId,
        private readonly array $parameters,
        private readonly ?DateTime $updatedAt,
        private readonly DateTime $createdAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            JobType::createFromString($data['job_type']),
            JobStatus::createFromString($data['job_status']),
            $data['user_id'],
            $data['parameters'] === null ? [] : Json::decode($data['parameters']),
            $data['updated_at'] === null ? null : DateTime::createFromString($data['updated_at']),
            DateTime::createFromString($data['created_at']),
        );
    }

    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getStatus() : JobStatus
    {
        return $this->status;
    }

    public function getType() : JobType
    {
        return $this->type;
    }

    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    public function getUserId() : ?int
    {
        return $this->userId;
    }

    public function jsonSerialize() : array
    {
        return [
            'type' => $this->type,
            'status' => $this->getStatus(),
            'userId' => $this->getUserId(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt()
        ];
    }
}
