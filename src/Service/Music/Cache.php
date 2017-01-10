<?php
namespace Baboon\Service\Music;
use Predis\Client as PredisClient;


class Cache
{
    const CACHE_PREFIX_KEY = 'music';

    const CACHE_ALBUM_KEY = 'list';

    const CACHE_ARTWORK_KEY = 'artwork';

    const CACHE_DATECREATED_KEY = 'dateCreated';

    /**
     * @var PredisClient
     */
    private $predisClient;

    /**
     * Cache constructor.
     * @param $predisClient
     */
    public function __construct(PredisClient $predisClient)
    {
        $this->predisClient = $predisClient;
        $this->predisClient->flushdb();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function getList(array $params)
    {
        return \json_decode($this->predisClient->get(
            $this->generateListKey($params)
        ), true);
    }

    /**
     * @param array $params
     * @param mixed $value
     */
    public function setList(array $params, $value)
    {
        $this->predisClient->set(
            $this->generateListKey($params),
            \json_encode($value)
        );
    }

    /**
     * @param array $params
     * @return \DateTime
     */
    public function getListDate(array $params)
    {
        return unserialize($this->predisClient->get($this->generateListDateKey($params)));
    }

    /**
     * @param array $params
     * @param \DateTime $dateTime
     */
    public function setListDate(array $params, \DateTime $dateTime)
    {
        $this->predisClient->set(
            $this->generateListDateKey($params),
            serialize($dateTime)
        );
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function getArtwork($id)
    {
        return \json_decode($this->predisClient->get(
            $this->generateArtworkKey($id)
        ), true);
    }

    /**
     * @param string $id
     * @param mixed $value
     */
    public function setArtwork($id, $value)
    {
        $this->predisClient->set(
            $this->generateArtworkKey($id),
            \json_encode($value)
        );
    }

    /**
     * @param string $id
     * @param \DateTime $dateTime
     */
    public function setArtworkDate($id, \DateTime $dateTime)
    {
        $this->predisClient->set(
            $this->generateArtworkDateKey($id),
            serialize($dateTime)
        );
    }

    /**
     * @param string $id
     * @return \DateTime|null
     */
    public function getArtworkDate($id)
    {
        return unserialize($this->predisClient->get(
            $this->generateArtworkDateKey($id)
        ));
    }

    /**
     * @param array $params
     * @return string
     */
    private function generateListKey(array $params)
    {
        ksort($params);
        return $this->generateKey([
            self::CACHE_ALBUM_KEY,
            base64_encode(http_build_query($params))
        ]);
    }

    /**
     * @param array $params
     * @return string
     */
    private function generateListDateKey(array $params)
    {
        return implode(
            '/', [
            $this->generateListKey($params),
            self::CACHE_DATECREATED_KEY
        ]);
    }

    /**
     * @param string $id
     * @return string
     */
    private function generateArtworkKey($id)
    {
        return $this->generateKey([self::CACHE_ARTWORK_KEY, $id]);
    }

    /**
     * @param string $id
     * @return string
     */
    private function generateArtworkDateKey($id)
    {
        return implode(
            '/', [
            $this->generateArtworkKey($id),
            self::CACHE_DATECREATED_KEY
        ]);
    }

    /**
     * @param array $segments
     * @return string
     */
    private function generateKey(array $segments)
    {
        return '/' . implode('/', array_merge([self::CACHE_PREFIX_KEY], $segments));
    }
}