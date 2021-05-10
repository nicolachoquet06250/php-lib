<?php

use PhpLib\interfaces\routing\{
    Router as RouterInterface,
    Context as ContextInterface
};
use PhpLib\injection\InjectionContainer;
use PhpLib\routing\{ Context, Router };

use appExample\routes\FirstController;
use appExample\errors\http\{ BadRequest, InternalError, NotFound };

define('__ROOT__', realpath(__DIR__ . '../'));

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';

(new InjectionContainer())
    ->use(RouterInterface::class, Router::class)
    ->use(ContextInterface::class, Context::class);

(new Router())->use([
    'routes' => [ FirstController::class ],
    'errors' => [ BadRequest::class, NotFound::class, InternalError::class ]
])->run();
