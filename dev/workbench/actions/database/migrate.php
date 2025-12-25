<?php

return function () use ($app) {
	$table = $app->request->table;
	$ns = $app['__namespace__'];
	$schema = $app->make($ns . '\Framework\Database\Schema');
	$path = realpath(__DIR__ .'/../../../../database/Migrations');
	$schema::createTable(
		$table, file_get_contents($path . '/' . $table . '.sql')
	);

	wp_send_json_success([
		'message' => 'Table migrated successfully!'
	]);
};
