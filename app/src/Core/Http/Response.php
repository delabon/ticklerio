<?php

namespace App\Core\Http;

use RuntimeException;

class Response implements ResponseInterface
{
    /**
     * Sets the content-type to 'application/json' when the body parameter is and array
     * Sets the content-type to 'text/html' when the content-type is not set
     * @param string|array<mixed> $body
     * @param HttpStatusCode $code
     * @param array<string, mixed> $headers
     */
    public function __construct(
        private string|array $body = "",
        private readonly HttpStatusCode $code = HttpStatusCode::OK,
        private array $headers = []
    ) {
        if (is_array($this->body)) {
            $this->headers['Content-Type'] = 'application/json';
            $this->body = json_encode($this->body);

            if (!$this->body) {
                throw new RuntimeException("Json encoding has failed.");
            }
        }

        // Sets content type as text/html if it's not set
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html';
        }

        $this->headers['Content-Length'] = strlen($this->body);
    }

    public function getStatusCode(): int
    {
        return $this->code->value;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        http_response_code($this->getStatusCode());
        echo $this->getBody();
    }
}
