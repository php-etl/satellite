<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.2', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\Component\Satellite\Filesystem\VirtualFile', Packaging\VirtualFile::class);

/*
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\VirtualFile instead.
 */
class_alias(Packaging\VirtualFile::class, 'Kiboko\Component\Satellite\Filesystem\VirtualFile');
