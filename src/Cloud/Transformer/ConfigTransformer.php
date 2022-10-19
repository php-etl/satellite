<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Transformer;

use Symfony\Component\ExpressionLanguage\Expression;

final class ConfigTransformer implements ConfigTransformerInterface
{
    public function transform(mixed $config): mixed
    {
        if (\is_array($config)) {
            foreach ($config as &$item) {
                $item = $this->transform($item);
            }

            unset($item);
        }

        if ($config instanceof Expression) {
            $config = (string) $config;
        }

        return $config;
    }
}
