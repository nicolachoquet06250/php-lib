<?php

namespace appExample\errors\http;

use \appExample\errors\HttpError;
use PhpLib\decorators\ErrorRoute;
use PhpLib\routing\Router;

#[ErrorRoute(Router::INTERNAL_ERROR)]
class InternalError extends HttpError {
    public function get() {
        echo '<pre>';
        var_dump($this->message);
        echo '</pre>';
    }
}