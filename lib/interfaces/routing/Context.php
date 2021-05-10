<?php


namespace PhpLib\interfaces\routing;


interface Context
{
    public function post(string $key): ?string;

    public function get(string $key): ?string;
}