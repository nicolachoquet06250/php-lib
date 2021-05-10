<?php

namespace appExample\errors\http;

use \appExample\errors\HttpError;
use PhpLib\decorators\ErrorRoute;
use PhpLib\routing\Router;

#[ErrorRoute(Router::BAD_REQUEST)]
class BadRequest extends HttpError {
    public function get() {
        echo '<pre>';
        var_dump($this->message);
        echo '</pre>';
    }
}