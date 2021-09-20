<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Serverless;

use Kiboko\Component\Satellite\Adapter\Serverless\Provider\ProviderInterface;
use Kiboko\Contract\Packaging\FileInterface;
use Symfony\Component\Yaml\Yaml;

final class Serverless implements ServerlessResourceInterface, FileInterface
{
    public function __construct(
        private string $service,
        private ProviderInterface $provider
    ) {}

    public function asArray(): array
    {
        return [
            'service' => $this->service,
            'provider' => $this->provider->asArray(),
        ];
    }

    public function __toString()
    {
        return Yaml::dump($this->asArray());
    }

    public function asResource()
    {
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, (string) $this);
        fseek($resource, 0, SEEK_SET);

        return $resource;
    }

    public function getPath(): string
    {
        return 'serverless.yml';
    }
}
