<?php

namespace FluentShipment\App\Http\Policies;

use FluentShipment\App\Utils\Auth\Auth;
use FluentShipment\Framework\Http\Request\Request;
use FluentShipment\Framework\Foundation\Policy as BasePolicy;

class Policy extends BasePolicy
{
	/**
     * Check user permission for any method.
     * 
     * @param  \FluentShipment\Framework\Http\Request\Request $request
     * @return bool
     */
    public function verifyRequest(Request $request, ...$args)
    {
        return Auth::check($request, 'manage_options', ...$args);
    }	
}
