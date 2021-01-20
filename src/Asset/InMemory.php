<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Asset;

use Kiboko\Component\Satellite\AssetInterface;

final class InMemory implements AssetInterface
{
    /** @var resource */
    private $stream;

    public function __construct(string $content)
    {
        $this->stream = fopen('php://temp', 'rb+');
        fwrite($this->stream, $content);
        fseek($this->stream, 0, SEEK_SET);
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
