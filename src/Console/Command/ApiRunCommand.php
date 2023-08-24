<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

final class ApiRunCommand extends HookRunCommand
{
    protected static $defaultName = 'run:api';
    protected static $defaultDescription = 'Run the api.';
}
