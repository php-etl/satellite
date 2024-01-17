<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

enum ArgumentType: string
{
    case PIPELINE = 'pipeline';
    case WORKFLOW = 'workflow';
}
