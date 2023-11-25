<?php

namespace App\Core\Session;

use RuntimeException;
use SessionHandlerInterface;

readonly class Session
{
    public function __construct(
        private SessionHandlerInterface $handler,
        public SessionHandlerType $handlerType = SessionHandlerType::Array,
        public string $name = 'MySessionName',
        public int $lifeTime = 3600, // 1 hour in seconds
        public bool $ssl = false,
        public bool $useCookies = true, // Recommended
        public bool $httpOnly = true,
        public string $path = '/',
        public string $domain = '.mysite.com',
        public string $savePath = ''
    ) {
        if ($this->handlerType === SessionHandlerType::Files) {
            $this->setupFilesHandler();
        }

        // Config session
        if ($this->useCookies) {
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
        }

        ini_set('session.use_trans_id', 0);
        session_name($this->name);

        if ($this->useCookies) {
            session_set_cookie_params(
                $this->lifeTime,
                $this->path,
                $this->domain,
                $this->ssl,
                $this->httpOnly
            );
        }

        // ini_set('session.save_handler', 'files'); Not needed when using your own handler.
        session_set_save_handler($this->handler, true);
    }

    private function setupFilesHandler(): void
    {
        if (!is_dir($this->savePath)) {
            throw new RuntimeException("The session folder '{$this->savePath}' does not exist or it is not a folder.");
        }

        if (!is_writable($this->savePath)) {
            throw new RuntimeException("The session folder '{$this->savePath}' is not writeable.");
        }

        session_save_path($this->savePath);
    }

    public function start(?string $sessionId = null): void
    {
        if ($sessionId !== null) {
            session_id($sessionId);
        }

        session_start();
    }

    public function end(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_unset();
        session_destroy();
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            return null;
        }

        return $_SESSION[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function add(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
