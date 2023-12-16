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

    /**
     * Instantiates an entity using the data passed
     * @param mixed[] $data
     * @param null|object $entity
     * @return object
     */
    public static function make(array $data, null|object $entity = null): object
    {
        $entityClassName = self::getEntityClassName();
        $entity = is_null($entity) ? new $entityClassName() : $entity;

        foreach ($data as $key => $value) {
            $method = u('set_' . $key)->camel()->toString();

            if (!method_exists($entity, $method)) {
                continue;
            }

            $entity->$method($value);
        }

        return $entity;
    }

    private static function getEntityClassName(): string
    {
        $parts = explode('\\', static::class);
        $className = array_pop($parts);

        return implode('\\', $parts) . '\\' . $className;
    }
}
