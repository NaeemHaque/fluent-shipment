<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\Framework\Foundation\Application;

class DeactivationHandler
{
    protected $app = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    public function handle()
    {
        // ...
    }
}
