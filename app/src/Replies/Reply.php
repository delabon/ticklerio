<?php

namespace App\Replies;

use App\Abstracts\Entity;

class Reply extends Entity
{
    protected int $id = 0;
    protected int $ticketId = 0;
    protected int $userId = 0;
    protected string $message = '';
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTicketId(): int
    {
        return $this->ticketId;
    }

    public function setTicketId(int $ticketId): void
    {
        $this->ticketId = $ticketId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
