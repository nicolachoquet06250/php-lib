<?php


namespace PhpLib\decorators;


use JetBrains\PhpStorm\Pure;
use PhpLib\interfaces\decorators\Decorator;

abstract class Attribute implements Decorator
{
    protected string $target;
    protected string $method;


    public function getTarget(): string {
        return $this->target;
    }

    public function setTarget(mixed $target): Attribute {
        $this->target = $target;
        return $this;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function setMethod(string $method): Attribute {
        $this->method = $method;
        return $this;
    }

    #[Pure]
    protected function isConstruct(): bool {
        return $this->getMethod() === '__construct';
    }
}