<?php

namespace FluentShipment\App\Utils\Auth;

use FluentShipment\App\Utils\Auth\Authorizer;

class Auth
{
    /**
     * The shared Authorizer instance.
     *
     * @var Authorizer|null
     */
    protected static $authorizer = null;

    /**
     * Get the shared Authorizer instance.
     *
     * @return Authorizer
     */
    public function authorizer()
    {
        if (!static::$authorizer) {
            static::$authorizer = new Authorizer;
        }

        return static::$authorizer;
    }

    /**
     * Dynamically handle static method calls and forward them to the Authorizer.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return (new static)->$method(...$args);
    }

    /**
     * Dynamically handle instance method calls and forward them to the Authorizer.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->authorizer()->$method(...$args);
    }
}
