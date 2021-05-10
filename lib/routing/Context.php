<?php


namespace PhpLib\routing;


class Context implements \PhpLib\interfaces\routing\Context
{
    public function post(string $key): ?string {
        return $_POST[$key] ?? null;
    }

    public function get(string $key): ?string {
        return $_GET[$key] ?? null;
    }
}