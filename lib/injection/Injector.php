<?php


namespace PhpLib\injection;


use PhpLib\Singleton;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

trait Injector
{
    /**
     * @param mixed ...$params
     * @return static
     * @throws ReflectionException
     */
    public final static function inject(...$params): static {
        $class = static::class;
        $rc = new ReflectionClass($class);

        $construct = $rc->getConstructor();
        $constructParams = [];
        if (!empty($construct)) {
            $constructParams = array_map(function (ReflectionParameter $p) {
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
            }, $construct->getParameters());
        }

        $useSingleton = array_reduce($rc->getTraits(), fn($r, ReflectionClass $c) => $c->getName() === Singleton::class ? true : $r, false);
        if ($useSingleton) {
            return $class::getInstance(...$constructParams, ...$params);
        } else {
            return new $class(...$constructParams, ...$params);
        }
    }
}