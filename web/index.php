<?php
require_once __DIR__ . '/../app/bootstrap.php';

$prefix = substr($_SERVER['REQUEST_URI'], 0, 8) === '/baboon/' ? 'baboon' : '';

$app->get($prefix . '/', function () {
    return null;
});

$app->get($prefix . '/music', 'controller.music:getAction');
$app->run();
