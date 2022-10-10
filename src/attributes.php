<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

function extractAttributes(object $object, string $class): \Traversable
{
    $reflection = new \ReflectionObject($object);
    $attributes = $reflection->getAttributes($class);

    /** @var \ReflectionAttribute $attribute */
    foreach ($attributes as $attribute) {
        yield $attribute->newInstance();
    }
}

function expectAttributes(object $object, string $class): \Traversable
{
    $reflection = new \ReflectionObject($object);
    $attributes = $reflection->getAttributes($class);
    if (\count($attributes) < 1) {
        throw new \RuntimeException('the provided configuration object should have a '.$class.' attribute defined.');
    }

    /** @var \ReflectionAttribute $attribute */
    foreach ($attributes as $attribute) {
        yield $attribute->newInstance();
    }
}
