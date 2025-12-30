<?php

// Define the main/primary menu bar
$router->menu('primary', function($router) {
    $router->add('/', 'modules/dashboard')
        ->name('dashboard')
        ->icon('HomeFilled')
        ->title(__('Dashboard', 'fluent-shipment'))
        ->props([
            'user'     => wp_get_current_user(),
            'isAdmin'  => current_user_can('manage_options'),
        ])
        ->middleware('auth');

    $router->add('shipments', 'modules/shipments')
           ->name('shipments')
           ->icon('Memo')
           ->title(__('Shipments', 'fluent-shipment'));

    $router->add('riders', 'modules/riders')
           ->name('riders')
           ->icon('User')
           ->title(__('Riders', 'fluent-shipment'));
           
    $router->add('riders/profile/:riderId', 'modules/riders/RiderProfile')
           ->name('riders.profile')
           ->props(true);

//    $router->add('posts-all', 'modules/posts')
//        ->name('posts.all')
//        ->icon('Memo')
//        ->title(__('Posts', 'wpfluent'));
//
//    $router->add('/users', 'modules/users')
//        ->name('users')
//        ->icon('User')
//        ->title(__('Users', 'wpfluent'))
//        ->middleware(['auth'])
//        ->children(function($router) {
//            $router->add(
//                ':id/view',
//                'modules/users/components/view',
//                'users.view'
//            )
//            ->meta([
//                'middleware' => current_user_can('administrator')
//                    ? ['auth', 'admin'] : ['auth']
//            ]);
//        });
});

$item = $router->primary;

$router->menu('secondary', function($router) {
    $router->add('settings', 'modules/settings')
        ->name('settings')
        ->icon('Setting')
        ->title(__('Settings', 'fluent-shipment'));
});
