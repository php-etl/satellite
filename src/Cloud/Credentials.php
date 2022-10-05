<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class Credentials
{
    public function __construct(
        public string $username,
        public string $password,
        public ?string $workspace = null,
    ) {
    }

    public function __debugInfo(): ?array
    {
        return [
            'login' => $this->username,
            'password' => '**SECRET**',
            'workspace' => $this->workspace,
        ];
    }
}
