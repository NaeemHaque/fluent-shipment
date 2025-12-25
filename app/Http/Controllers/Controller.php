<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\App;

use FluentShipment\Framework\Http\Controller as BaseController;

abstract class Controller extends BaseController
{
    public function app($module = null)
    {
        return App::make($module);
    }
}
