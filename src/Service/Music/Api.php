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
     * @param array $params
     * @return mixed
     */
    public function getList(array $params)
    {
        $params['fmt'] = 'json';

        $qryStr = http_build_query($params);

        $result = $this->guzzleClient->request(
            'GET',
            'http://musicbrainz.org/ws/2/release/?' . $qryStr
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