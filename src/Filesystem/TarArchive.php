<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.2', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\\Component\\Satellite\\Filesystem\\TarArchive', Packaging\TarArchive::class);

/**
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\TarArchive instead.
 */
class_alias(Packaging\TarArchive::class, 'Kiboko\\Component\\Satellite\\Filesystem\\TarArchive');
