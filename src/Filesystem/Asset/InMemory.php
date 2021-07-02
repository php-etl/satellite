<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.1', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\\Component\\Satellite\\Filesystem\\Asset\\InMemory', Packaging\Asset\InMemory::class);

/**
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\Asset\InMemory instead.
 */
class_alias(Packaging\Asset\InMemory::class, 'Kiboko\\Component\\Satellite\\Filesystem\\Asset\\InMemory');
