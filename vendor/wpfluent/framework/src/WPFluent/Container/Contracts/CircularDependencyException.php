<?php

namespace FluentShipment\Framework\Container\Contracts;

use Exception;
use FluentShipment\Framework\Container\Contracts\Psr\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
