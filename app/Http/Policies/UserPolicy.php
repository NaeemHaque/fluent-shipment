<?php

namespace FluentShipment\App\Http\Policies;

use FluentShipment\App\Utils\Auth\Auth;
use FluentShipment\Framework\Http\Request\Request;

class UserPolicy extends Policy
{
    /**
     * Check user permission for the current method.
     * 
     * @param  \FluentShipment\Framework\Http\Request\Request $request
     * @return bool
     */
    public function create(Request $request, ...$args)
    {
        return Auth::check($request, 'manage_options', ...$args);
    }

    /**
     * Check user permission for the current method.
     * 
     * @param  \FluentShipment\Framework\Http\Request\Request $request
     * @return bool
     */
    public function update(Request $request, ...$args)
    {
        return Auth::check($request, 'manage_options', ...$args);
    }
}
