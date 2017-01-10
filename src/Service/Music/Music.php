<?php
namespace Baboon\Service\Music;
use GuzzleHttp\Exception\RequestException;
use DateTime;

class Music
{
    const REQUEST_MAX_ATTEMPTS = 10;

    const CACHE_TTL = '1 day';

    /**
     * @var Api
     */
    private $apiService;

    /**
     * @var Cache
     */
    private $cacheService;

    /**
     * Music constructor.
     * @param Api $apiService
     * @param Cache $cacheService
     */
    public function __construct(
        Api $apiService,
        Cache $cacheService
    )
    {
        $this->apiService = $apiService;
        $this->cacheService = $cacheService;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getMusicList(array $params)
    {
        if (empty($params['limit']) === true) {
            $params['limit'] = 10;
        }

        $releases = $this->getList($params);

        $data = [];
        foreach ($releases as $album) {
            $artwork = $this->getArtwork($album['id']);
            $data[$album['id']] = array_merge($album, ['artwork' => $artwork]);
        }

        return $data;
    }

    /**
     * @param array $params
     * @param int $attempt
     * @return mixed
     */
    private function getList(array $params, $attempt = 1)
    {
        $getFromApi = function () use ($params) {
            $result = $this->getListFromAPI($params)['releases'];
            $this->setListCached($params, $result, new DateTime());
            return $result;
        };

        $getFromCache = function () use ($params) {
            if ($this->hasListCacheExpired($params) === true) {
                return null;
            }

            return $this->getListCachedValue($params)['releases'];
        };

        $tryAgainApi = function () use ($params, $attempt) {
            $this->getList($params, ++$attempt);
        };

        return $this->getData($getFromApi, $getFromCache, $tryAgainApi, $attempt);
    }

    /**
     * @param $id
     * @param int $attempt
     * @return mixed|null
     */
    private function getArtwork($id, $attempt = 1)
    {
        $getFromApi = function () use ($id) {
            $artwork = $this->getArtworkFromAPI($id);
            $this->setArtworkCache($id, $artwork, new DateTime());
            return $artwork;
        };

        $getFromCache = function () use ($id) {
            if ($this->hasArtworkCacheExpired($id) === true) {
                return null;
            }

            return $this->getArtworkCachedValue($id);
        };

        $tryAgainApi = function () use ($id, $attempt) {
            $this->getArtwork($id, ++$attempt);
        };

        return $this->getData($getFromApi, $getFromCache, $tryAgainApi, $attempt);
    }

    /**
     * @param callable $apiCall
     * @param callable $cacheCall
     * @param callable $tryAgain
     * @param $attempt
     * @return null
     */
    private function getData(callable $apiCall, callable $cacheCall, callable $tryAgain, $attempt)
    {
        try {
            $result = $cacheCall();

            if (empty($result) === false) {
                return $result;
            }

            return $apiCall();
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() === 400) {
                throw $e;
            }

            if ($attempt > self::REQUEST_MAX_ATTEMPTS || $e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            $result = $cacheCall();

            if (empty($result) === false) {
                return $tryAgain();
            }

            return $result;
        }
    }

    /**
     * @param array $params
     * @return mixed
     */
    private function getListFromAPI(array $params)
    {
        return $this->apiService->getList($params);
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param DateTime $dateTime
     */
    private function setArtworkCache($id, $data, DateTime $dateTime)
    {
        $this->setArtworkCachedValue($id, $data);
        $this->setArtworkCachedDate($id, $dateTime);
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function getArtworkFromAPI($id)
    {
        return $this->apiService->getArtwork($id);
    }

    /**
     * @param string $id
     * @param DateTime $dateTime
     */
    private function setArtworkCachedDate($id, DateTime $dateTime)
    {
        $this->cacheService->setArtworkDate($id, $dateTime);
    }

    /**
     * @param string $id
     * @return bool
     */
    private function isArtworkCached($id)
    {
        return empty($this->getArtworkCachedDate($id)) === false;
    }

    /**
     * @param string $id
     * @return DateTime|null
     */
    private function getArtworkCachedDate($id)
    {
        return $this->cacheService->getArtworkDate($id);
    }

    /**
     * @param $id
     * @return bool
     */
    private function hasArtworkCacheExpired($id)
    {
        $is = function() use ($id) {
            return $this->isArtworkCached($id);
        };

        $getDate = function() use ($id) {
            return $this->getArtworkCachedDate($id);
        };

        return $this->checkCache($is, $getDate);
    }

    /**
     * @param array $params
     * @param mixed $releases
     * @param DateTime $dateTime
     */
    private function setListCached(array $params, $releases, DateTime $dateTime)
    {
        $this->setListCachedValue($params, $releases);
        $this->setListCachedDate($params, $dateTime);
    }

    /**
     * @param array $params
     * @return mixed
     */
    private function getListCachedValue(array $params)
    {
        return $this->cacheService->getList($params);
    }

    /**
     * @param array $params
     * @param mixed $value
     */
    private function setListCachedValue(array $params, $value)
    {
        $this->cacheService->setList($params, $value);
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function getArtworkCachedValue($id)
    {
        return $this->cacheService->getArtwork($id);
    }

    /**
     * @param string $id
     * @param mixed $value
     */
    private function setArtworkCachedValue($id, $value)
    {
        $this->cacheService->setArtwork($id, $value);
    }

    /**
     * @param array $params
     * @return DateTime
     */
    private function getListCachedDate(array $params)
    {
        return $this->cacheService->getListDate($params);
    }

    /**
     * @param array $params
     * @param DateTime $dateTime
     */
    private function setListCachedDate(array $params, DateTime $dateTime)
    {
        $this->cacheService->setListDate($params, $dateTime);
    }

    /**
     * @param array $params
     * @return bool
     */
    private function isListCached(array $params)
    {
        return empty($this->getListCachedDate($params)) === false;
    }

    /**
     * @param array $params
     * @return bool
     */
    private function hasListCacheExpired(array $params)
    {
        $is = function () use ($params) {
            return $this->isListCached($params);
        };

        $getDate = function () use ($params) {
            return $this->getListCachedDate($params);
        };

        return $this->checkCache($is, $getDate);
    }

    /**
     * @param callable $is
     * @param callable $getDate
     * @return bool
     */
    private function checkCache(callable $is, callable $getDate)
    {
        if ($is() === false) {
            return false;
        }

        $now = new DateTime();
        /** @var DateTime $cacheDate */
        $cacheDate = $getDate();
        $cacheDate->modify('+' . self::CACHE_TTL);

        return $now >= $cacheDate;
    }
}