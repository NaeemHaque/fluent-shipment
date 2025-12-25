<?php

$app->addAdminAjaxAction(
	'logs', require_once (__DIR__ . '/get.php')
);

$app->addAdminAjaxAction(
	'save_log', require_once (__DIR__ . '/save.php')
);

$app->addAdminAjaxAction(
	'delete_log', require_once (__DIR__ . '/delete.php')
);
