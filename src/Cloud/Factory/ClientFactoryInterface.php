<?php

namespace Kiboko\Component\Satellite\Cloud\Factory;

use Gyroscops\Api;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface ClientFactoryInterface
{
    public function createClient(HttpClientInterface $client): Api\Client;
}
