<?php
namespace Baboon\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Baboon\Service\Music\Music as MusicService;

class Music
{
    /**
     * @var MusicService
     */
    private $musicSevice;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Music constructor.
     * @param MusicService $musicService
     * @param RequestStack $requestStack
     */
    public function __construct(
        MusicService $musicService,
        RequestStack $requestStack
    )
    {
        $this->musicSevice = $musicService;
        $this->requestStack = $requestStack;
    }

    /**
     * @return JsonResponse
     */
    public function getAction()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $params = $currentRequest->query->all();

        return new JsonResponse($this->musicSevice->getMusicList($params));
    }

}