<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class Auth
{
    private array $configuration;

    public function __construct(private string $pathname)
    {
        $content = false;
        if (file_exists($this->pathname)) {
            $content = \file_get_contents($this->pathname);
        }
        if ($content === false) {
            $this->configuration = [];
            return;
        }

        $this->configuration = \json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public function append(string $url, string $token): void
    {
        $this->configuration[$url] = [
            'token' => $token
        ];
    }

    public function dump(): void
    {
        $content = \json_encode($this->configuration, flags: JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        \file_put_contents($this->pathname, $content);
    }
}
