<?php

namespace appExample\routes;

use PhpLib\decorators\Route as RouteAttribute;
use PhpLib\interfaces\routing\{ Router, Context };
use PhpLib\injection\Injector;
use PhpLib\routing\Route;

#[RouteAttribute('/')]
class FirstController {
    use Injector;

    public function __construct(
        private ?Router $router = null
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
    public function toto(?Router $router, string $slot) {
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
    public function toto2(?Router $router, ?Context $context, string $slot) {
        echo '<pre>';
        var_dump('GET', $slot, 'queryString \'test\' ' . (!is_null($context->get('test')) ? 'is ' . $context->get('test') : 'not exists'));
        var_dump($router);
        echo '</pre>';
    }
}