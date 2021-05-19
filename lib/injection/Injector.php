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
    final public static function inject(...$params): static {
    	$mapHandle = static function (ReflectionParameter $p) use (&$mapHandle) {
		    if ($p->hasType()) {
			    $className = $p->getType()->getName();
			    $injectorContainer = new InjectionContainer();
			    if (!isset($injectorContainer->dependencies()[$className])) {
				    return null;
			    }

			    if (isset($injectorContainer->dependencies()[$className]['class'])) {
				    $className = $injectorContainer->dependencies()[ $className ]['class'];

				    $useInjector = array_reduce(
					    $p->getDeclaringClass()->getTraits(),
					    static fn( $r, ReflectionClass $c ) => $c->getName() === Injector::class ? true : $r,
					    false
				    );

				    if ( $useInjector ) {
					    return $className::inject();
				    }

				    return new $className();
			    }

			    if (isset($injectorContainer->dependencies()[$className]['callback'])) {
				    $callback = $injectorContainer->dependencies()[$className]['callback'];

				    $rc = new \ReflectionFunction($callback);
				    $params = array_map($mapHandle, $rc->getParameters());
				    return $callback(...$params);
			    }
		    }
		    return null;
	    };

        $class = static::class;
        $rc = new ReflectionClass($class);

        $construct = $rc->getConstructor();
        $constructParams = [];
        if (!is_null($construct)) {
            $constructParams = array_map($mapHandle, $construct->getParameters());
        }

        $useSingleton = array_reduce($rc->getTraits(), fn($r, ReflectionClass $c) => $c->getName() === Singleton::class ? true : $r, false);
        if ($useSingleton) {
            return $class::getInstance(...$constructParams, ...$params);
        }

	    return new $class(...$constructParams, ...$params);
    }
}