<?php


namespace PhpLib;


trait Singleton
{
    private static ?self $instance = null;

    public final static function getInstance(...$params): ?self {
        if (is_null(static::$instance)) {
            $className = static::class;
            static::$instance = new $className(...$params);
        }
        return static::$instance;
    }
}