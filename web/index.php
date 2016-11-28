<?php
require_once __DIR__ . '/../app/bootstrap.php';

$app->get('/', function () {
    return null;
});

$app->get('/music', 'controller.music:getAction');

$app->run();
