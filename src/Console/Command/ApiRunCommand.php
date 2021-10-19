<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

final class ApiRunCommand extends HookRunCommand
{
    protected static $defaultName = 'run:api';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Run the api.');
    }
}
