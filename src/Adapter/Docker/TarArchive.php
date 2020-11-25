<?php

declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

final class TarArchive implements AssetInterface
{
    /** @var resource */
    private $stream;

    public function __construct(FileInterface ...$files)
    {
        $this->stream = fopen('php://temp', 'rb+');

        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    public function addFile(FileInterface $file): void
    {
        $this->addAsset($file->getPath(), $file);
    }

    public function addAsset(string $path, AssetInterface $asset): void
    {
        $resource = $asset->asResource();
        $length = fstat($resource)['size'];

        $this->writeHeader($path, $length);
        stream_copy_to_stream($resource, $this->stream);

        if (($length % 512) !== 0) {
            fwrite($this->stream, str_pad('', 512 - ($length % 512), "\0"));
        }
    }

    private function writeHeader(string $path, int $size)
    {
        if (strlen($path) > 100) {
            throw new \RuntimeException('File path is too long, standard Tar supports only 100 chars.');
        }

        $header = pack(
            'Z100Z8Z8Z8a12a12Z8ccZ100Z6ccZ32Z32Z8Z8Z155Z12',
            $path,
            sprintf('%06o ', 0644),
            sprintf('%06o ', getmyuid()),
            sprintf('%06o ', getmygid()),
            sprintf('%011o ', $size),
            sprintf('%011o ', time()),
            "\x20\x20\x20\x20\x20\x20\x20\x20", // Checksum
            0x20, 0x30,                         // type flag
            '',                                 // linkname
            'ustar',                            // magic
            0x30, 0x30,                         // version
            'docker',                           // uname
            'docker',                           // gname
            sprintf('%06o ', 0),   // devmajor
            sprintf('%06o ', 0),   // devminor
            '',                                 // prefix
            '',                                 // pad``
        );

        for ($i = 0, $checksum = 0; $i < 512; $i++) {
            $checksum += ord($header[$i]);
        }
        $header = substr_replace($header, pack('a6', sprintf("%06o", $checksum)), 148, 7);

        fwrite($this->stream, $header);
    }

    public function asResource()
    {
        $resource = fopen('php://temp', 'rb+', false);
        fseek($this->stream, 0, SEEK_SET);
        stream_copy_to_stream($this->stream, $resource);
        fwrite($resource, pack('a1024', ''));

        fseek($resource, 0, SEEK_SET);
        fseek($this->stream, 0, SEEK_END);

        return $resource;
    }
}