<?php


namespace PhpLib\routing;


use Exception;
use PhpLib\injection\InjectionContainer;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class ErrorRoute
{
    protected string $message;
    protected int $code;
    protected array $stackTrace;

    public function __construct(
        protected string $errorType,
        protected string $target,
        protected string $method
    ) {}

    public function setMessage(string $message): ErrorRoute {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function setCode(int $code): ErrorRoute {
        $this->code = $code;
        return $this;
    }

    public function getCode(): int {
        return $this->code;
    }

    public function setStackTrace(array $stackTrace): ErrorRoute {
        $this->stackTrace = $stackTrace;
        return $this;
    }

    public function getStackTrace(): array {
        return $this->stackTrace;
    }

    /**
     * @throws ReflectionException
     */
    public function resolve() {
        $class = $this->getTarget();
        $injectionContainer = new InjectionContainer();
        $controller = $injectionContainer->inject($class, params: [$this->getMessage(), $this->getCode(), $this->getStackTrace()]);
        $injectionContainer->inject($controller, $this->getMethod(), params: [$this->getMessage(), $this->getCode(), $this->getStackTrace()]);
    }

    public function setTarget(string $target): ErrorRoute
    {
        $this->target = $target;
        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setMethod(string $method): ErrorRoute
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}