<?php

declare(strict_types=1);

trigger_deprecation('php-etl/satellite', '0.4', 'The "%s" interface is deprecated, use "%s" instead.', 'Kiboko\Component\Satellite\Adapter\FactoryInterface', Kiboko\Contract\Configurator\Adapter\FactoryInterface::class);

/*
 * @deprecated since Satellite 0.4, use Kiboko\Contract\Configurator\Adapter\FactoryInterface instead.
 */
class_alias(Kiboko\Contract\Configurator\Adapter\FactoryInterface::class, 'Kiboko\Component\Satellite\Adapter\FactoryInterface');
