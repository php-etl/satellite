<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.2', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\\Component\\Satellite\\Filesystem\\VirtualDirectory', Packaging\VirtualDirectory::class);

/*
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\VirtualDirectory instead.
 */
class_alias(Packaging\VirtualDirectory::class, 'Kiboko\\Component\\Satellite\\Filesystem\\VirtualDirectory');
