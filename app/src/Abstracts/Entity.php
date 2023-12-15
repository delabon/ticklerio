<?php

namespace App\Abstracts;

use App\Interfaces\EntityInterface;

use function Symfony\Component\String\u;

abstract class Entity implements EntityInterface
{
    /**
     * @return array|mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[u($key)->snake()->toString()] = $value;
        }

        return $data;
    }
}
