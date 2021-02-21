<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Asset;

use Kiboko\Component\Satellite\AssetInterface;

final class Resource implements AssetInterface
{
    /** @var resource */
    private $stream;

    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \TypeError(sprintf('Expected value of type resource, got %s', gettype($resource)));
        }

        $this->stream = $resource;
    }

    /** @return resource */
    public function asResource()
    {
        $resource = fopen('php://temp', 'rb+');
        fseek($this->stream, 0, SEEK_SET);
        stream_copy_to_stream($this->stream, $resource);
        fseek($resource, 0, SEEK_SET);
        fseek($this->stream, 0, SEEK_END);
        return $resource;
    }
}
