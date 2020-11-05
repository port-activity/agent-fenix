<?php
namespace SMA\PAA\SERVICE;

use Predis\Client;

class RedisClient extends Client
{
    public function __construct()
    {
        $url = getenv("REDIS_URL");
        if (!$url) {
            throw new \Exception("Missing env REDIS_URL");
        }
        parent::__construct($url);
    }
}
