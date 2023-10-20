<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\DTO;

use PhpParser\Node\Expr;

class Exclusion
{
    public function __construct(
        public Expr $when,
        public ?Expr $reason = null
    ){}
}
