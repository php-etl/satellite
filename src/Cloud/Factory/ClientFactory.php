<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Factory;

use Gyroscops\Api;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ClientFactory implements ClientFactoryInterface
{
    public function createPsr18Client(string $url, string $token, bool $withSSL = true): HttpClientInterface
    {
        return HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $withSSL,
                'auth_bearer' => $token
            ]
        );
    }

    public function createClient(HttpClientInterface $client): Api\Client
    {
        $psr18Client = new Psr18Client($client);

        return Api\Client::create($psr18Client);
    }
}
