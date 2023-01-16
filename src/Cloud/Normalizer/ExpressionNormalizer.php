<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Normalizer;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExpressionNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): string
    {
        return (string) $object;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return is_a($data, Expression::class);
    }
}
