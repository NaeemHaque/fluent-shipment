<?php

$app->addAdminAjaxAction(
    'save_feature', require_once(__DIR__.'/save.php')
);

$app->addAdminAjaxAction(
    'get_features', require_once(__DIR__.'/get.php')
);

$app->addAdminAjaxAction(
    'delete_feature', require_once(__DIR__.'/delete.php')
);
