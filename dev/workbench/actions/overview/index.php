<?php

$app->addAdminAjaxAction(
    'get_overview', require_once(__DIR__.'/get.php')
);

$app->addAdminAjaxAction(
    'save_overview', require_once(__DIR__.'/save.php')
);
