<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class EmailAlreadyExistsException extends Exception
{
    /**
     * @param string $email
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $email, int $code = 0, Throwable $previous = null)
    {
        $message = "A user with the email '{$email}' already exists.";
        parent::__construct($message, $code, $previous);
    }
}
