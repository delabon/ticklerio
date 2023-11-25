<?php

namespace App\Core;

use App\Core\Session\Session;
use DateTime;

class Csrf
{
    public const SESSION_KEY = 'session_csrf';

    /**
     * @param Session $session
     * @param string $salt
     * @param int $lifeTime should be in minutes
     * For applications with high-security requirements or where actions are typically quick (like form submissions), a shorter lifetime (like 15 to 30 minutes) is preferable.
     * For most general web applications, a lifetime of 1 to 2 hours strikes a good balance.
     * In some cases, you might want the CSRF token to last as long as the user's session. This is more user-friendly, as it prevents the token from expiring while the user is still active, but it can be less secure if the user's session is very long or permanent.
     */
    public function __construct(private Session $session, private string $salt, private int $lifeTime = 30)
    {
    }

    public function generate(): string
    {
        $token = hash('sha256', $this->salt . uniqid() . time());
        $now = new DateTime();
        $now->modify('+ ' . $this->lifeTime . ' minutes');
        $this->session->add(self::SESSION_KEY, [
            'token' => $token,
            'expiry_date' => $now->getTimestamp()
        ]);

        return $token;
    }

    public function validate(string $token): bool
    {
        $sessionToken = $this->session->get(self::SESSION_KEY);

        // No token
        if (empty($sessionToken)) {
            return false;
        }

        // Check if expired
        if ($sessionToken['expiry_date'] <= time()) {
            return false;
        }

        // Token does not match the session token
        if ($token !== $sessionToken['token']) {
            return false;
        }

        return true;
    }

    public function get(): ?string
    {
        $sessionToken = $this->session->get(self::SESSION_KEY);

        return $sessionToken ? $sessionToken['token'] : null;
    }

    public function delete(): void
    {
        $this->session->delete(self::SESSION_KEY);
    }
}
