<?php

$app->addAdminAjaxAction('database_types', function() {
	global $wpdb;

	wp_send_json_success([
		'db_field_types' => $wpdb->get_col("
            SELECT DISTINCT DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            ORDER BY DATA_TYPE
        "),
	]);
});

require_once __DIR__ . '/overview/index.php';
require_once __DIR__ . '/features/index.php';
require_once __DIR__ . '/endpoints/index.php';
require_once __DIR__ . '/database/index.php';
require_once __DIR__ . '/changelog/index.php';
