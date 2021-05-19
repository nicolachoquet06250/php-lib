<?php


namespace PhpLib\decorators;


use Attribute;
use \PhpLib\decorators\Attribute as AttributeBase;
use PhpLib\routing\Router;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ErrorRoute extends AttributeBase
{
    public function __construct(
        protected string $errorType
    ) {}

    /**
     * @throws ReflectionException
     */
    public function process(): void
    {
        if ($this->isConstruct()) {
            $rc = new ReflectionClass($this->target);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $_method) {
                if (in_array($_method->getName(), Router::$httpMethods)) {
                    Router::error(
                        errorType: $this->getErrorType(),
                        target: $this->getTarget(),
                        method: $_method->getName()
                    );
                }
            }
        } else {
            Router::error(
                errorType: $this->getErrorType(),
                target: $this->getTarget(),
                method: $this->getMethod()
            );
        }
    }

    public function getErrorType(): string {
        return $this->errorType;
    }
}