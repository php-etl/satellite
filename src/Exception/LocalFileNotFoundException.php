<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

class LocalFileNotFoundException extends \Exception
{
    public function __construct(string $files)
    {
        parent::__construct(sprintf('The following files or directories must be present: %s', $files));
    }
}
