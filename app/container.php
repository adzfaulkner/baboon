<?php
use Pimple\Container;
use Predis\Client as Predis;
use GuzzleHttp\Client as Guzzle;
use Baboon\Service\Music\Music as MusicService;
use Baboon\Service\Music\Api as ApiService;
use Baboon\Service\Music\Cache as CacheService;

$container = new Container();

$container['settings'] = function () {
    $fileLoc = __DIR__ . '/config/settings.ini';

    if (file_exists($fileLoc) === false) {
        die('Please create ' . $fileLoc);
    }

    return parse_ini_file(__DIR__ . '/config/settings.ini', true);
};

$container['guzzle'] = $container->factory(function ($c) {
    $musicBrainzSettings = $c['settings']['musicbrainz'];
    return new Guzzle([
        'headers' => [
            'User-Agent' => $musicBrainzSettings['user_agent']
        ]
    ]);
});

$container['predis'] = function($c) {
    $redisSettings = $c['settings']['redis'];
    return new Predis($redisSettings['host'] . ':' . $redisSettings['port']);
};

$container['service.music.api'] = function($c) {
    return new ApiService($c['guzzle']);
};

$container['service.music.cache'] = function($c) {
    return new CacheService($c['predis']);
};

$container['service.music.music'] = function($c) {
    return new MusicService(
        $c['service.music.api'],
        $c['service.music.cache']
    );
};
