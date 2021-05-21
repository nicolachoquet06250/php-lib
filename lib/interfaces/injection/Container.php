<?php


namespace PhpLib\interfaces\injection;


interface Container
{
    /**
     * @param array<string, array<string, string>> $dependencies
     * @return Container
     */
    public function addDependencies(array $dependencies): Container;

    public function use(string $interface, string|callable $class): Container;
}