<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Server implements Builder
{
    public function __construct(
        private string $host,
        private ?string $port
    ) {
    }

    public function getNode(): Node\Expr
    {
        return new Node\Scalar\String_($this->host);
    }
}
