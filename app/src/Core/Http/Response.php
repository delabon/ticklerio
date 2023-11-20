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
        $this->makeHeaderKeysLowerCase();
        $this->ifBodyIsArrayMakeItJsonResponse();
        $this->contentTypeAsHtmlIfNotSet();
        $this->headers['content-length'] = strlen($this->body);
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

    private function makeHeaderKeysLowerCase(): void
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        $this->headers = $headers;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    private function ifBodyIsArrayMakeItJsonResponse(): void
    {
        if (is_array($this->body)) {
            $this->headers['content-type'] = 'application/json';
            $this->body = json_encode($this->body);

            if (!$this->body) {
                throw new RuntimeException("Json encoding has failed.");
            }
        }
    }

    private function contentTypeAsHtmlIfNotSet(): void
    {
        if (!isset($this->headers['content-type'])) {
            $this->headers['content-type'] = 'text/html';
        }
    }
}
