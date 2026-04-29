<?php
namespace BM\Core;

class Container
{
    private array $instances = [];
    private array $bindings = [];

    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $instance = ($this->bindings[$id])($this);
            $this->instances[$id] = $instance;
            return $instance;
        }

        return $this->resolve($id);
    }

    private function resolve(string $className)
    {
        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Если нет типа или это примитивный тип (int, string, array, bool и т.д.)
            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \RuntimeException(
                        "Cannot resolve parameter '{$parameter->getName()}' in {$className}"
                    );
                }
                continue;
            }

            // Получаем имя класса
            $typeName = $type->getName();

            // Рекурсивно разрешаем зависимость
            $dependencies[] = $this->get($typeName);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }
}