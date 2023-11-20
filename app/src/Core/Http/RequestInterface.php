<?php

namespace App\Core\Http;

interface RequestInterface
{
    public static function createFromGlobals(): self;

    public static function create(): self;

    public function ip(): string;

    public function header(string $key): mixed;

    public function server(string $key): mixed;

    public function query(RequestType $requestType, string $key): mixed;

    public function method(): string;

    public function url(): string;

    public function uri(): string;

    public function userAgent(): string;
}
