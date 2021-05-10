<?php

namespace appExample\errors;

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