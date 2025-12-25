<?php
/**
 * This is an example of using middleware in routes. The global middleware group will be
 * applied to all routes and the before middleware will be executed before the policy
 * handler ran and the after middleware group will be executed right after the main
 * callback (the main route handler) ran without any exceptions/interruptions.
 * The route middleware will be ran if any middleware is explicitly used.
 */

/**
 * Note: Please use class based middleware when you need more than a few to keep
 * this confile clean and store the middleware in the Http/Middleware folder.
 */

/**
 * How to use route middleware:
 * 
 * $router->namespace(...)->before('auth')->after('loger')->group(...);
 * $router->before('auth')->after('loger')->get(...);
 */

return [
	'global' => [
		'before' => [
			function ($request, $next) {
				return $next(
					$request->forget([
						'_locale',
						'query_timestamp'
					])
				);
			}
		],
		'after' => [
			function ($response, $next) {
				// disable litespeed cache
        		do_action(
        			'litespeed_control_set_nocache',
        			'fluent plugin api request'
        		);
        		
        		return $next($response);
			}
		]
	],
];