<?php
namespace Baboon\Service\Music;
use GuzzleHttp\Client as GuzzleClient;


class Api
{

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * Api constructor.
     * @param GuzzleClient $guzzleClient
     */
    public function __construct(GuzzleClient $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param string $query
     * @param int $limit
     * @return mixed
     */
    public function getList($query, $limit)
    {
        $result = $this->guzzleClient->request(
            'GET',
            'http://musicbrainz.org/ws/2/release/?query=' . $query. '&fmt=json&limit=' . $limit
        );

        return \json_decode($result->getBody()->getContents(), true);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function getArtwork($id)
    {
        $result = $this->guzzleClient->request(
            'GET',
            'http://coverartarchive.org/release/' . $id . '/'
        );

        return \json_decode($result->getBody()->getContents(), true);
    }

}