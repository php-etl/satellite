<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Auth
{
    public function __construct(
        public string $url,
        public string $token,
    ) {}
}
