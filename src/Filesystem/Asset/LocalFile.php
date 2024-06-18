<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.1', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\Component\Satellite\Filesystem\Asset\LocalFile', Packaging\Asset\LocalFile::class);

/*
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\Asset\LocalFile instead.
 */
class_alias(Packaging\Asset\LocalFile::class, 'Kiboko\Component\Satellite\Filesystem\Asset\LocalFile');
