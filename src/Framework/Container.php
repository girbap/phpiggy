<?php

declare(strict_types=1);

namespace Framework;

use Framework\Exceptions\ContainerException;
use ReflectionClass, ReflectionNamedType;

class Container
{
    private array $definitions = [];
    private array $resolved = [];

    public function addDefinitions(array $newDefinitions)
    {
        $this->definitions = array_merge($this->definitions, $newDefinitions);
    }

    public function resolve(string $className)
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable())
        {
            throw new ContainerException("Class $className is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null)
        {
            return new $className;
        }

        $parameters = $constructor->getParameters();

        if (count($parameters) === 0)
        {
            return new $className;
        }

        $dependencies = [];

        foreach ($parameters as $parameter)
        {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (!$type)
            {
                throw new ContainerException("Failed to resolve class $className because parameter $parameter is missing a type hint.");
            }

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin())
            {
                throw new ContainerException("Failed to resolve class $className because of invalid parameter name.");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    public function get(string $id)
    {
        if (!array_key_exists($id, $this->definitions))
        {
            throw new ContainerException("Class $id does not exist in container definitions.");
        }

        if (array_key_exists($id, $this->resolved))
        {
            return $this->resolved[$id];
        }

        $factory = $this->definitions[$id];
        $dependency = $factory();

        $this->resolved[$id] = $dependency;

        return $dependency;
    }
}
