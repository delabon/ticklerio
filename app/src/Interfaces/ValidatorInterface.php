<?php

namespace App\Interfaces;

interface ValidatorInterface
{
    /**
     * @param mixed[]|array $data
     * @return void
     */
    public function validate(array $data): void;
}
