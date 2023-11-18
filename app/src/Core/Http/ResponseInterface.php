<?php

namespace App\Core\Http;

interface ResponseInterface
{
    public function getStatusCode(): int;

    public function getBody(): string;

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array;

    public function send(): void;
}
