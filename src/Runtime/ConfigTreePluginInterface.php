<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    FWG ETL
 * @copyright   2021 - Diglin (https://www.diglin.com)
 */

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Contract\Configurator\FactoryInterface;

interface ConfigTreePluginInterface
{
    public function addPlugins(FactoryInterface ...$plugins): self;
}
