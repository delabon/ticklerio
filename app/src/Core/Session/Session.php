<?php

namespace App\Core\Session;

use DateTime;
use RuntimeException;
use SessionHandler;

class Session
{
    private const EXPIRY_DATE_KEY = 'session_expiry_date';
    private const FINGERPRINT_KEY = 'session_fingerprint';
    private int $ttl = 0;

    public function __construct(
        private readonly SessionHandler $handler,
        public readonly string $name = 'MySessionName',
        public readonly int $lifeTime = 3600, // 1 hour in seconds
        public readonly bool $ssl = false,
        public readonly bool $httpOnly = true,
        public readonly string $path = '/',
        public readonly string $domain = '.mysite.com',
        public readonly string $savePath = ''
    ) {
        if (!is_dir($this->savePath)) {
            throw new RuntimeException("The session folder '{$this->savePath}' does not exist or it is not a folder.");
        }

        if (!is_writable($this->savePath)) {
            throw new RuntimeException("The session folder '{$this->savePath}' is not writeable.");
        }

        $this->ttl = $this->lifeTime / 60; // in minutes

        // Config session
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_trans_id', 0);
        ini_set('session.save_handler', 'files');

        session_name($this->name);
        session_save_path($this->savePath);
        session_set_cookie_params(
            $this->lifeTime,
            $this->path,
            $this->domain,
            $this->ssl,
            $this->httpOnly
        );
        session_set_save_handler($this->handler, true);
    }

    public function start(): void
    {
        session_start();
        $this->validateSession();
    }

    public function end(): void
    {
        session_unset();
        session_destroy();
    }

    private function validateSession(): void
    {
        $now = new DateTime();
        $expiryDate = $this->get(self::EXPIRY_DATE_KEY);
        $fingerprint = $this->get(self::FINGERPRINT_KEY);

        if (!$expiryDate) {
            $this->renewSession();
        } elseif ($now->getTimestamp() > $expiryDate) {
            $this->renewSession();
        } elseif ($this->generateFingerprint() !== $fingerprint) {
            $this->renewSession();
        }
    }

    private function renewSession(): void
    {
        session_regenerate_id(true);
        $this->setExpiryDate();
        $this->setFingerprint();
    }

    private function setExpiryDate(): void
    {
        $date = new DateTime();
        $date->modify("+" . $this->ttl . " minutes");
        $this->add(self::EXPIRY_DATE_KEY, $date->getTimestamp());
    }

    private function setFingerprint(): void
    {
        $this->add(self::FINGERPRINT_KEY, $this->generateFingerprint());
    }

    /**
     * Method to generate a fingerprint hash from client information
     * available in $_SERVER superglobal. This hash should be considered to
     * be stable for a client during any given session.
     *
     * @return string
     */
    private function generateFingerprint(): string
    {
        $fingerprint = '';

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
        }

        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $fingerprint .= $_SERVER['HTTP_ACCEPT_ENCODING'];
        }

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $fingerprint .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        return hash('sha256', empty($fingerprint) ? 'NO FINGERPRINT AVAILABLE' : $fingerprint);
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

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
