<?php

$app->addAdminAjaxAction(
    'endpoints', require_once(__DIR__.'/get.php')
);
