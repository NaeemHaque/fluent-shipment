<?php

return function() use ($app) {
    $path = realpath(__DIR__.'/../../../../database/Migrations');
    $table = $app->request->get('table');
    $source = $path."/{$table}.sql";
    $target = __DIR__ . "/../../data/deleted/migrations/{$table}.sql";

    if (file_exists($source)) {
        rename($source, $target);
    }

    if ($app->request->get('delete_table') === 'true') {
        $table = $app->request->table;
        $ns = $app['__namespace__'];
        $schema = $app->make($ns . '\Framework\Database\Schema');
        $path = realpath(__DIR__ .'/../../../../database/Migrations');
        $schema::dropTableIfExists($table);
    }

    wp_send_json_success([]);
};
