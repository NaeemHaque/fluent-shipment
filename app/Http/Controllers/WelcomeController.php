<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\Framework\Http\Request\Request;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        return [
            'message' => 'Welcome to WPFluent.'
        ];
    }
}
