<?php

namespace App\Core;

use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class Container
{
    /** @var array<string, callable> */
    private array $definitions = [];
    /** @var array<string, int|callable> */
    private array $singletons = [];

    public function register(string $className, callable $definition): self
    {
        $this->definitions[$className] = $definition;

        return $this;
    }

    public function singleton(string $className, callable $definition): self
    {
        if (!isset($this->singletons[$className])) {
            $this->singletons[$className] = 1;
            $this->definitions[$className] = $definition;
        }

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function get(string $className): object
    {
        if (isset($this->singletons[$className])) {
            if (!is_object($this->singletons[$className])) {
                $this->singletons[$className] = $this->definitions[$className]($className);
            }

            return $this->singletons[$className];
        }

        if (isset($this->definitions[$className])) {
            return $this->definitions[$className]($className);
        }

        return $this->autowire($className);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function autowire(string $className): object
    {
        $reflection = new ReflectionClass($className);

        $parameters = array_map(
            function (ReflectionParameter $parameter) use ($className) {
                $type = $parameter->getType();

                if (!$type) {
                    throw new InvalidArgumentException("Cannot auto-wire parameter '{$parameter->getName()}' in class '{$className}': Missing type hint."); // phpcs:ignore
                }

                if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    throw new InvalidArgumentException("Cannot auto-wire parameter '{$parameter->getName()}' in class '{$className}': Type is not a class or interface."); // phpcs:ignore
                }

                return $this->get($type->getName());
            },
            $reflection->getConstructor()?->getParameters() ?? []
        );

        return new $className(...$parameters);
    }
}
