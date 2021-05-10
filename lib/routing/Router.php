<?php


namespace PhpLib\routing;


use Exception;
use PhpLib\decorators\Attribute;
use PhpLib\decorators\ErrorRoute as ErrorRouteAttribute;
use PhpLib\decorators\Route as RouteAttribute;
use PhpLib\interfaces\Runnable;
use PhpLib\routing\exceptions\BadMethodException;
use PhpLib\routing\exceptions\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class Router
 * @package PhpLib\routing
 *
 * @method static Route get(string $uri, string $target, string $method = '__construct')
 * @method static Route post(string $uri, string $target, string $method = '__construct')
 * @method static Route put(string $uri, string $target, string $method = '__construct')
 * @method static Route delete(string $uri, string $target, string $method = '__construct')
 */
class Router implements \PhpLib\interfaces\routing\Router, Runnable
{
    public static array $httpMethods = [
        Route::GET,
        Route::POST,
        Route::PUT,
        Route::DELETE
    ];

    const NOT_FOUND = 'not-found';
    const BAD_REQUEST = 'bad-request';
    const INTERNAL_ERROR = 'internal-error';

    /**
     * @var array<string, array<string, Route>> $routes
     */
    private static array $routes = [];

    private static array $errorRoutes = [];

    private array $routesProvider = [];

    public static function route(
        string $httpMethod,
        string $uri,
        string $target,
        string $method = '__construct'
    ): Route {
        if (!isset(static::$routes[$httpMethod])) static::$routes[$httpMethod] = [];

        static::$routes[$httpMethod][$uri] = new Route($uri, $target, $method);

        return static::$routes[$httpMethod][$uri];
    }

    public static function error(string $errorType, string $target, string $method): ErrorRoute {
        static::$errorRoutes[$errorType] = new ErrorRoute($errorType, $target, $method);
        return static::$errorRoutes[$errorType];
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return Route
     * @throws Exception
     */
    public static function __callStatic(string $name, array $arguments): Route {
        if (in_array($name, static::$httpMethods)) {
            [$uri, $target, $method] = $arguments;
            if (empty($method)) $method = '__construct';

            return static::route($name, $uri, $target, $method);
        }

        throw new Exception(__CLASS__ . '::' . $name . '() not found');
    }

    /**
     * @param string $old
     * @param string $new
     * @param string $httpMethod
     * @throws Exception
     */
    public static function changeRouteUri(string $old, string $new, string $httpMethod): void {
        if (!isset(static::$routes[$httpMethod])) {
            throw new Exception('route ' . strtoupper($httpMethod) . ' ' . $old . ' not exists');
        }
        if (!isset(static::$routes[$httpMethod][$old])) {
            throw new Exception('route ' . strtoupper($httpMethod) . ' ' . $old . ' not exists');
        }

        $oldRoute = static::$routes[$httpMethod][$old];
        unset(static::$routes[$httpMethod][$old]);

        static::$routes[$httpMethod][$new] = $oldRoute;
    }

    public function routes(): array {
        return static::$routes;
    }

    public function use(array $provider): Router {
        $this->routesProvider = $provider;
        return $this;
    }

    /**
     * @return ?Route
     * @throws BadMethodException
     * @throws NotFoundException
     */
    private function resolve(): ?Route {
        $currentUri = $_SERVER['REQUEST_URI'];
        [$currentUri] = explode('?', $currentUri);
        $currentHttpMethod = strtolower($_SERVER['REQUEST_METHOD']);

        $expectedHttpMethod = null;
        $givenHttpMethod = null;

        if (isset($this->routes()[$currentHttpMethod])) {
            $route =  array_reduce(
                array_values($this->routes()[$currentHttpMethod]),
                fn (?Route $r, Route $c) => $c->match() ? $c : $r,
                null
            );

            if (!is_null($route)) {
                return $route;
            }
        }

        $matches = 0;
        foreach ($this->routes() as $httpMethod => $_route) {
            if ($httpMethod !== $currentHttpMethod) {
                $match = array_reduce(
                    array_values($this->routes()[$httpMethod]),
                    fn(Route|bool $r, Route $c) => $c->match() ? true : $r,
                    false
                );

                if ($match) {
                    $expectedHttpMethod = $httpMethod;
                    $givenHttpMethod = $currentHttpMethod;

                    $matches++;
                }
            }
        }

        if ($matches > 0) {
            throw new BadMethodException("uri $currentUri expected $expectedHttpMethod http method, $givenHttpMethod given", 400);
        } else {
            throw new NotFoundException("page $currentUri not found", 404);
        }
    }

    private function resolveError(string $errorName, string $message, int $code, array $stackTrace): ?ErrorRoute {
        if (isset(static::$errorRoutes[$errorName])) {
            return static::$errorRoutes[$errorName]
                ->setMessage($message)
                ->setCode($code)
                ->setStackTrace($stackTrace);
        }
        return null;
    }

    /**
     * @param string $route
     * @param string $attributeClass
     * @throws ReflectionException
     */
    private function resolveRoute(string $route, string $attributeClass) {
        $rc = new ReflectionClass($route);
        $routeAttributes = $rc->getAttributes($attributeClass);
        if (!empty($routeAttributes)) {
            [$routeAttribute] = $routeAttributes;

            /** @var Attribute $routeAttributeInstance */
            $routeAttributeInstance = $routeAttribute->newInstance();
            $routeAttributeInstance->setTarget($route);
            $routeAttributeInstance->setMethod('__construct');
            $routeAttributeInstance->process();
        }

        foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
            $routeAttributes = $rm->getAttributes($attributeClass);
            if (!empty($routeAttributes)) {
                [$routeAttribute] = $routeAttributes;

                /** @var Attribute $routeAttributeInstance */
                $routeAttributeInstance = $routeAttribute->newInstance();
                $routeAttributeInstance->setTarget($route);
                $routeAttributeInstance->setMethod($rm->getName());
                $routeAttributeInstance->process();
            }
        }
    }

    public function run() {
        try {
            foreach ($this->routesProvider['errors'] as $error) {
                $this->resolveRoute($error, ErrorRouteAttribute::class);
            }

            foreach ($this->routesProvider['routes'] as $route) {
                $this->resolveRoute($route, RouteAttribute::class);
            }

            $this->resolve()?->resolve();
        } catch (NotFoundException $e) {
            $this->resolveError(static::NOT_FOUND, $e->getMessage(), $e->getCode(), $e->getTrace())->resolve();
        } catch (BadMethodException $e) {
            $this->resolveError(static::BAD_REQUEST, $e->getMessage(), $e->getCode(), $e->getTrace())?->resolve();
        } catch (ReflectionException|Exception $e) {
            $this->resolveError(static::INTERNAL_ERROR, $e->getMessage(), 500, $e->getTrace())?->resolve();
        }
    }
}
