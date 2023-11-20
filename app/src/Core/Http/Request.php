<?php

namespace App\Core\Http;

use Exception;
use App\Core\Exceptions\MissingServerParameterException;

readonly class Request implements RequestInterface
{
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, getallheaders());
    }

    /**
     * @param array<mixed, mixed> $getParams
     * @param array<mixed, mixed> $postParams
     * @param array<mixed, mixed> $cookies
     * @param array<mixed, mixed> $files
     * @param array<mixed, mixed> $server
     * @param array<mixed, mixed> $headers
     * @return self
     */
    public static function create(
        array $getParams = [],
        array $postParams = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        array $headers = []
    ): self {
        return new self($getParams, $postParams, $cookies, $files, $server, $headers);
    }

    /**
     * @param array<mixed, mixed> $getParams
     * @param array<mixed, mixed> $postParams
     * @param array<mixed, mixed> $cookies
     * @param array<mixed, mixed> $files
     * @param array<mixed, mixed> $server
     * @param array<mixed, mixed> $headers
     */
    private function __construct(
        public array $getParams,
        public array $postParams,
        public array $cookies,
        public array $files,
        public array $server,
        public array $headers
    ) {
    }

    /**
     * @throws Exception
     */
    public function ip(): string
    {
        $ip = null;

        if (!empty($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        } elseif (!empty($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        }

        if (!$ip) {
            throw new MissingServerParameterException('The IP address is missing in the $_SERVER superglobal.');
        }

        return $ip;
    }

    public function header(string $key): mixed
    {
        if (!isset($this->headers[$key])) {
            return null;
        }

        return $this->headers[$key];
    }

    public function query(RequestType $requestType, string $key): mixed
    {
        $prop = $requestType->value . 'Params';

        if (!isset($this->$prop[$key])) {
            return null;
        }

        return $this->$prop[$key];
    }

    public function server(string $key): mixed
    {
        if (!isset($this->server[$key])) {
            return null;
        }

        return $this->server[$key];
    }

    public function method(): string
    {
        return $this->server('REQUEST_METHOD') ? strtolower($this->server('REQUEST_METHOD')) : RequestType::Get->value;
    }

    /**
     * @throws Exception
     */
    public function url(): string
    {
        if (!$this->server('HTTP_HOST')) {
            throw new MissingServerParameterException('HTTP_HOST is missing from $_SERVER superglobal. Unable to determine the request URL.');
        }

        $url = $this->server('HTTPS') === 'on' ? 'https' : 'http';
        $url .= '://' . $this->server('HTTP_HOST');

        try {
            $url .= $this->uri();
        } catch (MissingServerParameterException $e) {
            $url .= '/';
        }

        return $url;
    }

    /**
     * @throws Exception
     */
    public function uri(): string
    {
        if (!$this->server('REQUEST_URI')) {
            throw new MissingServerParameterException('REQUEST_URI is missing from the $_SERVER superglobal. Unable to determine the request URI.');
        }

        return $this->server('REQUEST_URI');
    }

    public function userAgent(): string
    {
        return $this->server('HTTP_USER_AGENT') ?: '';
    }
}
