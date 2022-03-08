<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class Auth
{
    private string $pathName;
    private array $configuration;

    public function __construct(?string $pathName = null)
    {
        if ($pathName === null) {
            $this->pathName = getenv('HOME') . '/.gyroscops/';
        } else {
            $this->pathName = $pathName;
        }

        if (!file_exists($this->pathName)
            && !mkdir($this->pathName, 0700, true)
            && !is_dir($this->pathName)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" can not be created', $this->pathName));
        }

        $content = false;
        if (file_exists($this->pathName . '/auth.json')) {
            $content = \file_get_contents($this->pathName . '/auth.json');
        } else {
            touch($this->pathName . '/auth.json');
            chmod($this->pathName . '/auth.json', 0700);
        }
        if ($content === false) {
            $this->configuration = [];
            return;
        }

        $this->configuration = \json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public function dump(): void
    {
        $content = \json_encode($this->configuration, flags: JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        \file_put_contents($this->pathName . '/auth.json', $content);
    }

    public function append(string $url, string $token): void
    {
        $this->configuration[$url] = [
            'token' => $token
        ];
    }

    public function token(string $url): string
    {
        return $this->configuration[$url]['token']
            ?? throw new \OutOfBoundsException('There is no available token to authenticate to the service.');
    }

    public function organization(string $url): OrganizationId
    {
        return $this->configuration[$url]['token']
            ?? throw new \OutOfBoundsException('There is no available token to authenticate to the service.');
    }
}
