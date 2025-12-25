<?php

namespace FluentShipment\Framework\Container;

use Exception;
use FluentShipment\Framework\Container\Contracts\Psr\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
