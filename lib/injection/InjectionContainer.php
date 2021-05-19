<?php


namespace PhpLib\injection;


use PhpLib\ArrayCleaner;
use PhpLib\interfaces\injection\Container;
use PhpLib\Singleton;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class InjectionContainer implements Container
{
    use ArrayCleaner;

    protected static array $dependencies = [];

    public function addDependencies(array $dependencies): InjectionContainer {
        static::$dependencies = $dependencies;

        return $this;
    }

    public function use(string $interface, string $class): Container {
        static::$dependencies[$interface] = [
            'class' => $class
        ];

        return $this;
    }

    /**
     * @param string|object $class
     * @param ?string $method
     * @param ?string $type
     * @param array ...$params
     * @return mixed
     * @throws ReflectionException
     */
    public function inject(string|object $class, ?string $method = null, ?string $type = null, array $params = []): mixed {
        $rc = new ReflectionClass($class);

        if (is_string($class)) {
            $useInjector = array_reduce($rc->getTraits(), fn($r, ReflectionClass $c) => $c->getName() === Injector::class ? true : $r, false);
            if ($useInjector) {
                return $class::inject(...$params);
            } else {
                $useSingleton = array_reduce($rc->getTraits(), fn($r, ReflectionClass $c) => $c->getName() === Singleton::class ? true : $r, false);
                if ($useSingleton) {
                    return $class::getInstance(...$params);
                } else {
                    return new $class(...$params);
                }
            }
        } else {
            if ($rc->hasMethod($method)) {
                $methodParams = array_map(function (ReflectionParameter $p) {
                    if ($p->hasType()) {
                        $className = $p->getType()->getName();
                        $injectorContainer = new InjectionContainer();
                        if (!isset($injectorContainer->dependencies()[$className])) {
                            return null;
                        }
                        $className = $injectorContainer->dependencies()[$className]['class'];

                        $useInjector = array_reduce($p->getDeclaringClass()->getTraits(), fn($r, ReflectionClass $c) => $c->getName() === Injector::class ? true : $r, false);

                        if ($useInjector && in_array('inject', get_class_methods($className))) {
                            return $className::inject();
                        }
                        return new $className();
                    }
                    return null;
                }, $rc->getMethod($method)->getParameters());

                $this->cleanArray($methodParams);
                if (count($methodParams) === 1 && $methodParams[0] === null) {
                    $this->cleanArray($methodParams, both: true);
                }
                $this->cleanArray($params, true);

                $class->$method(...$methodParams, ...$params);
            }
        }
        return null;
    }

    public function dependencies(): array {
        return static::$dependencies;
    }
}