<?php
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\ClientException;

require_once __DIR__ . '/../app/bootstrap.php';

$prefix = substr($_SERVER['REQUEST_URI'], 0, 8) === '/baboon/' ? 'baboon' : '';

$app->get($prefix . '/', function () {
    return null;
});

$app->error(function (ClientException $e) {
    $message = json_decode($e->getResponse()->getBody()->getContents());
    return new JsonResponse([
        'url' => strval($e->getRequest()->getUri()),
        'code' => $e->getCode(),
        'error' => $message->error
    ]);
});

$app->get($prefix . '/music', 'controller.music:getAction');
$app->run();
