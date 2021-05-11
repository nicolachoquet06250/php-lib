# php-lib
simple bibliothèque php pour la gestion du routage et la gestion de l'injection de dépendence

## DOCUMENTATION

 - Créer un Controlleur :
 
```shell
cd [project]
mkdir [controllers_directory]
cd [controllers_directory]
touch [ClassController].php
```

```php
<?php
namespace appExample\controllers_directory;

use PhpLib\decorators\Route as RouteAttribute;
use PhpLib\routing\Route;
use PhpLib\injection\Injector;
use PhpLib\interfaces\routing\Router;

#[RouteAttribute('/')]
class ClassController {
    use Injector;
    
    public function __construct(
        private ?Router $router
    ) {}
    
    public function get() {
        // code of GET http method
    }
    
    public function post(int $id) {
        // code of POST http method
    }
    
    public function put(int $id) {
        // code of PUT http method
    }
    
    public function delete(int $id) {
        // code of DELETE http method
    }
    
    #[RouteAttribute(
        uri: '/test/toto/{slot}',
        httpMethod: Route::GET,
        params: [ 'slot' => Route::SLOT ]
    )]
    public function customGetRoute(string $slot) {
        // code of custom GET method
    }
    
    #[RouteAttribute(
        uri: '/test/toto/{slot}',
        httpMethod: Route::POST,
        params: [ 'slot' => Route::SLOT ]
    )]
    public function customPostRoute(string $slot) {
        // code of custom GET method
    }
}
```

 - Créer un controlleur d'erreur http :
 
```shell
cd [project]
mkdir [error_controllers_directory]
cd [error_controllers_directory]
touch [HttpErrorClass].php
```

```php
<?php

namespace appExample\error_controllers_directory;

use PhpLib\decorators\ErrorRoute;
use PhpLib\routing\Router;

#[ErrorRoute(Router::NOT_FOUND)]
class NotFound {
    public function __construct(
        protected string $message,
        private int $code,
        protected array $stackTrace
    ) {
        http_response_code($this->code);
    }
    
    public function get() {
        // code of "404 not found" error
    }
}
```

```php
<?php

use PhpLib\routing\Router;
use appExample\controllers_directory\ClassController;
use appExample\error_controllers_directory\{ NotFound, BadRequest, InternalError };

require __DIR__ . '/vendor/autoload.php';

// ... code

(new Router())->use([
    'routes' => [ ClassController::class ],
    'errors' => [ NotFound::class, BadRequest::class, InternalError::class ]
])->run();
```

 - Mettre en place l'injection de dépendences

```php
<?php

use PhpLib\injection\InjectionContainer;
use PhpLib\interfaces\routing\{
    Router as RouterInterface,
    Context as ContextInterface
};
use PhpLib\routing\{ Context, Router };

require __DIR__ . '/vendor/autoload.php';

(new InjectionContainer())
    // use(interface, associatedClass) => InjectionContainer
    ->use(RouterInterface::class, Router::class)
    ->use(ContextInterface::class, Context::class);

// ... rest of code
```

 - Injecter un objet dans le constructeur / les propriétées d'une classe

```php
<?php

use PhpLib\injection\Injector;
use PhpLib\interfaces\routing\{
    Router as RouterInterface,
    Context as ContextInterface
};

class MyClass {
    // indispensable for injection in construct
    use Injector;
    
    public function __construct(
        private ?RouterInterface $router,
        private ?ContextInterface $context
    ) {
        // code of construct
    }
}
```
