<?php


namespace PhpLib\decorators;


use Attribute;
use Exception;
use PhpLib\routing\Router;
use PhpLib\decorators\Attribute as AttributeBase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use PhpLib\routing\Route as RouteBase;

#[Attribute(
	Attribute::TARGET_CLASS
	| Attribute::TARGET_METHOD
	| Attribute::IS_REPEATABLE
)]
class Route extends AttributeBase
{
    public function __construct(
        protected string $uri,
        protected ?string $httpMethod = null,
        protected array $params = []
    ) {}

    public function getHttpMethod(): ?string {
        return $this->httpMethod;
    }

    public function getUri(): string {
        return $this->uri;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function process(): void
    {
        if ($this->isConstruct()) {
            $rc = new ReflectionClass($this->target);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $_method) {
                if (in_array($_method->getName(), Router::$httpMethods)) {
                    $route = Router::route(
                        httpMethod: $_method->getName(),
                        uri: $this->getUri(),
                        target: $this->getTarget(),
                        method: $_method->getName()
                    );

                    if ( in_array( $_method->getName(), [
	                    RouteBase::POST,
	                    RouteBase::PUT,
	                    RouteBase::DELETE
                    ], true ) ) {
                        $oldUri = $this->getUri();
                        $route->with(
                            param: 'id',
                            regex: RouteBase::NUMBER,
                            add: true,
                            newUri: $newUri
                        );

                        if ($oldUri !== $newUri) {
                            Router::changeRouteUri(
                                old: $oldUri,
                                new: $newUri,
                                httpMethod: $_method->getName()
                            );
                        }
                    }
                }
            }
        } else {
            $route = Router::route(
                httpMethod: $this->getHttpMethod(),
                uri: $this->getUri(),
                target: $this->getTarget(),
                method: $this->getMethod()
            );

            foreach ($this->params as $param => $regex) {
                $route->with($param, $regex);
            }
        }
    }
}