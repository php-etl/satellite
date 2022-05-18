<?php

declare(strict_types=1);

use Kiboko\Contract\Packaging;

trigger_deprecation('php-etl/satellite', '0.2', 'The "%s" interface is deprecated, use "%s" instead.', 'Kiboko\\Component\\Satellite\\Filesystem\\DirectoryInterface', Packaging\DirectoryInterface::class);

/*
 * @deprecated since Satellite 0.1, use Kiboko\Contract\Packaging\DirectoryInterface instead.
 */
class_alias(Packaging\DirectoryInterface::class, 'Kiboko\\Component\\Satellite\\Filesystem\\DirectoryInterface');
