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
    }

    /**
     * @param string $query
     * @param int $limit
     * @return mixed
     */
    public function getList($query, $limit)
    {
        return \json_decode($this->predisClient->get(
            $this->generateListKey($query, $limit)
        ), true);
    }

    /**
     * @param string $query
     * @param int $limit
     * @param mixed $value
     */
    public function setList($query, $limit, $value)
    {
        $this->predisClient->set(
            $this->generateListKey($query, $limit),
            \json_encode($value)
        );
    }

    /**
     * @param string $query
     * @param int $limit
     * @return \DateTime
     */
    public function getListDate($query, $limit)
    {
        return unserialize($this->predisClient->get($this->generateListDateKey($query, $limit)));
    }

    /**
     * @param string $query
     * @param int $limit
     * @param \DateTime $dateTime
     */
    public function setListDate($query, $limit, \DateTime $dateTime)
    {
        $this->predisClient->set(
            $this->generateListDateKey($query, $limit),
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
     * @param string $query
     * @param int $limit
     * @return string
     */
    private function generateListKey($query, $limit)
    {
        return $this->generateKey([self::CACHE_ALBUM_KEY, base64_encode($query . $limit)]);
    }

    /**
     * @param string $query
     * @param int $limit
     * @return string
     */
    private function generateListDateKey($query, $limit)
    {
        return implode(
            '/', [
            $this->generateListKey($query, $limit),
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