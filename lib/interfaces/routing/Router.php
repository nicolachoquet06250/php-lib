<?php


namespace PhpLib\interfaces\routing;


use PhpLib\routing\Route;

interface Router
{
    public static function route(
        string $httpMethod,
        string $uri,
        string $target,
        string $method = '__construct'
    ): Route;

    public static function __callStatic(string $name, array $arguments): Route;

    public static function changeRouteUri(string $old, string $new, string $httpMethod): void;

    public function routes(): array;

    public function use(array $provider): Router;
}