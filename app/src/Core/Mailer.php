<?php

namespace App\Core;

class Mailer
{
    public function send(
        string $to,
        string $subject,
        string $message,
        string $additionalHeaders = "",
        string $additionalParameters = ""
    ): bool {
        return mail(
            $to,
            $subject,
            $message,
            $additionalHeaders,
            $additionalParameters
        );
    }
}
