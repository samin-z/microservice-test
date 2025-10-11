<?php
declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
// this is the mongoDB document that stores the counter increment events
#[ODM\Document(collection: "counter_events")]
class CounterEvent
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: "string")]
    private string $eventType;

    #[ODM\Field(type: "date")]
    private \DateTime $timestamp;

    #[ODM\Field(type: "date")]
    private \DateTime $createdAt;

    #[ODM\Field(type: "hash")] // this field stores additional information about the counter increment event
    private array $metadata = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
