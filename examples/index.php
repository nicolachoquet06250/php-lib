<?php

use PhpLib\decorators\{
    Route as RouteAttribute,
    ErrorRoute
};
use PhpLib\interfaces\routing\{
    Router as RouterInterface,
    Context as ContextInterface
};
use PhpLib\injection\{ InjectionContainer, Injector };
use PhpLib\routing\{ Context, Route, Router };

define('__ROOT__', realpath(__DIR__ . '../'));

require __DIR__ . '/../vendor/autoload.php';

abstract class HttpError {
    public function __construct(
        protected string $message,
        private int $code,
        protected array $stackTrace
    ) {
        http_response_code($this->code);
    }

    public abstract function get();
}

#[ErrorRoute(Router::BAD_REQUEST)]
class BadRequest extends HttpError {
    public function get() {
        echo '<pre>';
        var_dump($this->message);
        echo '</pre>';
    }
}

#[ErrorRoute(Router::NOT_FOUND)]
class NotFound extends HttpError {
    public function get() {
        echo '<pre>';
        var_dump($this->message);
        echo '</pre>';
    }
}

#[ErrorRoute(Router::INTERNAL_ERROR)]
class InternalError extends HttpError {
    public function get() {
        echo '<pre>';
        var_dump($this->message);
        echo '</pre>';
    }
}

#[RouteAttribute('/')]
class FirstController {
    use Injector;

    public function __construct(
        private ?RouterInterface $router = null
    ) {}

    public function get() {
        echo <<<HTML
            <form>
                <div>
                    <input type="number" value="1" />
                </div>
                <div>
                    <input type="submit">
                </div>
                <div id="return"></div>
            </form>
            
            <script>
                window.addEventListener('load', () => {
                    document.querySelector('form').addEventListener('submit', e => {
                        e.preventDefault();
                        
                        fetch('/' + document.querySelector('input[type="number"]').value + '?fetch', {
                            method: 'put'
                        }).then(r => r.text())
                          .then(text => document.querySelector('#return').innerHTML = text);
                    })
                })
            </script>
        HTML;
    }

    public function post(int $id) {
        var_dump('POST', $id);
    }

    public function put(int $id) {
        var_dump('PUT', $id);
    }

    #[RouteAttribute(
        uri: '/test/toto/{slot}',
        httpMethod: Route::POST,
        params: [ 'slot' => Route::SLOT ]
    )]
    public function toto(?RouterInterface $router, string $slot) {
        echo '<pre>';
        var_dump('POST', $slot);
        var_dump($router);
        echo '</pre>';
    }

    #[RouteAttribute(
        uri: '/test/toto/{slot}',
        httpMethod: Route::GET,
        params: [ 'slot' => Route::SLOT ]
    )]
    public function toto2(?RouterInterface $router, ?ContextInterface $context, string $slot) {
        echo '<pre>';
        var_dump('GET', $slot, 'queryString \'test\' ' . (!is_null($context->get('test')) ? 'is ' . $context->get('test') : 'not exists'));
        var_dump($router);
        echo '</pre>';
    }
}

(new InjectionContainer())
    ->use(RouterInterface::class, Router::class)
    ->use(ContextInterface::class, Context::class);

(new Router())->use([
    'routes' => [ FirstController::class ],
    'errors' => [ BadRequest::class, NotFound::class, InternalError::class ]
])->run();
