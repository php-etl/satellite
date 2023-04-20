<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP\Builder;

use PhpParser\Builder;
use PhpParser\Node;

interface ActionBuilderInterface extends Builder
{
    public function withLogger(Node\Expr $logger): self;

    public function withState(Node\Expr $state): self;
}
