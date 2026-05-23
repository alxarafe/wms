<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Model;

final readonly class Greeting
{
    public function __construct(
        private string $id,
        private string $message,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
