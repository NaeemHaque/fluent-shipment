<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\Framework\Foundation\Application;

class ExceptionHandler
{
    protected $app = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    public function handle($e)
    {
        $logEntry = sprintf(
            "[%s] %s: %s in %s on line %d\nStack trace:\n%s\n\n",
            date('c'),                  // ISO 8601 timestamp
            get_class($e),              // Exception class
            $e->getMessage(),           // Message
            $e->getFile(),              // File where it occurred
            $e->getLine(),              // Line number
            $e->getTraceAsString()      // Full stack trace
        );

        error_log($logEntry);
    }
}
