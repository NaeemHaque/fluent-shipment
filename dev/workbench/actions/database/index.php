<?php

$app->addAdminAjaxAction(
	'migrations', require_once(__DIR__ . '/get.php')
);

$app->addAdminAjaxAction(
	'save_migration', require_once(__DIR__ . '/save.php')
);

$app->addAdminAjaxAction(
	'migrate_table', require_once(__DIR__ . '/migrate.php')
);

$app->addAdminAjaxAction(
	'refresh_table', require_once(__DIR__ . '/refresh.php')
);

$app->addAdminAjaxAction(
	'rollback_table', require_once(__DIR__ . '/rollback.php')
);

$app->addAdminAjaxAction(
	'delete_migration', require_once(__DIR__ . '/delete.php')
);
