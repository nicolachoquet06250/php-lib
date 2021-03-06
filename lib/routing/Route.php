<?php


namespace PhpLib\routing;


use Exception;
use PhpLib\ArrayCleaner;
use PhpLib\injection\InjectionContainer;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Route
{
    use ArrayCleaner;

    public const GET       = 'get';
    public const POST      = 'post';
    public const PUT       = 'put';
    public const DELETE    = 'delete';

    public const NUMBER = '([0-9]+)';
    public const STRING = '([^\/]+)';
    public const SLOT = '([a-zA-Z0-9\-\_]+)';

    protected array $params = [];

    protected array $paramsMatches = [];

    public function __construct(
        protected string $uri,
        protected string $target,
        protected string $method
    ) {}

    public function with(string $param, string $regex, bool $add = false, &$newUri = ''): Route {
        if ($add) {
            $this->uri .= "/{{$param}}";
        }
        $newUri = $this->uri;
        $this->params[$param] = $regex;
        $this->paramsMatches[$param] = null;
        return $this;
    }

    public function getUri($withRegex = true): string {
        $uri = $this->uri;
        $uri = str_replace('//', '/', $uri);
        if ($withRegex) {
            foreach ($this->params as $param => $regex) {
                $uri = str_replace("{{$param}}", str_replace('([', "(?<$param>[", $regex), $uri);
            }
            $uri = str_replace('/', '\/', $uri);
            $uri = "/$uri\$/D";
        }
        return $uri;
    }

    public function getTarget(): string {
        return $this->target;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function addParamMatch(string $param, mixed $match) {
        $this->paramsMatches[$param] = $match;
    }

    public function match(): bool {
        $currentUri = $_SERVER['REQUEST_URI'];
        [$currentUri] = explode('?', $currentUri);

        preg_match($this->getUri(), $currentUri, $matches);
        if (!empty($matches)) {
            array_shift($matches);

            foreach ($matches as $k => $v) {
                if ( array_key_exists( $k, $this->paramsMatches ) ) {
                    $this->addParamMatch($k, $v);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function resolve(): array|string|null {
        $class = $this->getTarget();
        $rc = new ReflectionClass($class);

        $injectionContainer = new InjectionContainer();
        $controller = $injectionContainer->inject($class);

        $rm = $rc->getMethod($this->getMethod());
        $methodParams = array_map(function (ReflectionParameter $c) {
            if (! array_key_exists( $c->getName(), $this->paramsMatches ) ) {
                if (!$c->getType()->allowsNull()) {
                    throw new Exception($this->getTarget() . '::' . $this->getMethod() . '() param ' . $c->getName() . ' expected ' . $c->getReturnType() . ' but null given');
                }
                return null;
            }

            return $this->paramsMatches[$c->getName()];
        }, $rm->getParameters());

        $this->cleanArray($methodParams);

        return $injectionContainer->inject($controller, $this->getMethod(), params: $methodParams);
    }
}