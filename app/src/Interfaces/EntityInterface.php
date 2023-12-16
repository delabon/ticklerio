<?php

namespace App\Interfaces;

interface EntityInterface
{
    /**
     * @return array|mixed[]
     */
    public function toArray(): array;

    /**
     * Instantiates an entity using the data passed
     * @param mixed[] $data
     * @param null|object $entity
     * @return object
     */
    public static function make(array $data, null|object $entity = null): object;
}
