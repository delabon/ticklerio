<?php

namespace App\Interfaces;

interface SanitizerInterface
{
    /**
     * @param mixed[]|array $data
     * @return mixed[]|array
     */
    public function sanitize(array $data): array;
}
