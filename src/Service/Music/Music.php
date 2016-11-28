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
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getMusicList($query, $limit)
    {
        $releases = $this->getList($query, $limit);
        $data = [];
        foreach ($releases as $album) {
            $artwork = $this->getArtwork($album['id']);
            $data[$album['id']] = array_merge($album, ['artwork' => $artwork]);
        }

        return $data;
    }

    /**
     * @param string $query
     * @param int $limit
     * @param int $attempt
     * @return mixed
     */
    private function getList($query, $limit, $attempt = 1)
    {
        $getFromApi = function () use ($query, $limit) {
            $result = $this->getListFromAPI($query, $limit)['releases'];
            $this->setListCached($query, $limit, $result, new DateTime());
            return $result;
        };

        $getFromCache = function () use ($query, $limit) {
            if ($this->hasListCacheExpired($query, $limit) === true) {
                return null;
            }

            return $this->getListCachedValue($query, $limit)['releases'];
        };

        $tryAgainApi = function () use ($query, $limit, $attempt) {
            $this->getList($query, $limit, ++$attempt);
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
     * @param string $query
     * @param int $limit
     * @return mixed
     */
    private function getListFromAPI($query, $limit)
    {
        return $this->apiService->getList($query, $limit);
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
     * @param string $query
     * @param int $limit
     * @param mixed $releases
     * @param DateTime $dateTime
     */
    private function setListCached($query, $limit, $releases, DateTime $dateTime)
    {
        $this->setListCachedValue($query, $limit, $releases);
        $this->setListCachedDate($query, $limit, $dateTime);
    }

    /**
     * @param string $query
     * @param int $limit
     * @return mixed
     */
    private function getListCachedValue($query, $limit)
    {
        return $this->cacheService->getList($query, $limit);
    }

    /**
     * @param string $query
     * @param int $limit
     * @param mixed $value
     */
    private function setListCachedValue($query, $limit, $value)
    {
        $this->cacheService->setList($query, $limit, $value);
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
     * @param string $query
     * @param int $limit
     * @return DateTime
     */
    private function getListCachedDate($query, $limit)
    {
        return $this->cacheService->getListDate($query, $limit);
    }

    /**
     * @param string $query
     * @param int $limit
     * @param DateTime $dateTime
     */
    private function setListCachedDate($query, $limit, DateTime $dateTime)
    {
        $this->cacheService->setListDate($query, $limit, $dateTime);
    }

    /**
     * @param $query
     * @param $limit
     * @return bool
     */
    private function isListCached($query, $limit)
    {
        return empty($this->getListCachedDate($query, $limit)) === false;
    }

    /**
     * @param $query
     * @param $limit
     * @return bool
     */
    private function hasListCacheExpired($query, $limit)
    {
        $is = function () use ($query, $limit) {
            return $this->isListCached($query, $limit);
        };

        $getDate = function () use ($query, $limit) {
            return $this->getListCachedDate($query, $limit);
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