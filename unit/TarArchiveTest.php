<?php declare(strict_types=1);

namespace unit\Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;
use Kiboko\Component\Satellite\TarArchive;
use PHPUnit\Framework\TestCase;

final class TarArchiveTest extends TestCase
{
    public function testArchiveCreation()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $this->assertIsResource($archive->asResource());
    }

    public function testArchiveFileHeaderContainsFilename()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z99filename', fread($resource, 512), 0);

        $this->assertEquals((binary) 'Dockerfile', $data['filename']);
    }

    public function testArchiveFileHeaderContainsFileMode()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z7mode', fread($resource, 512), 100);

        $this->assertEquals((binary) '000644 ', $data['mode']);
    }

    public function testArchiveFileHeaderContainsUserId()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z7uid', fread($resource, 512), 108);

        $this->assertMatchesRegularExpression('/\d{6}\s/', $data['uid']);
    }

    public function testArchiveFileHeaderContainsGroupId()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z7gid', fread($resource, 512), 116);

        $this->assertMatchesRegularExpression('/\d{6}\s/', $data['gid']);
    }

    public function testArchiveFileHeaderContainsSize()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z11size', fread($resource, 512), 124);

        $this->assertEquals((binary) '00000000011', $data['size']);
    }

    public function testArchiveFileHeaderContainsTime()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z11time', fread($resource, 512), 136);

        $this->assertMatchesRegularExpression('/^\d+$/ ', $data['time']);
    }

    public function testArchiveFileHeaderContainsChecksum()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z8checksum', fread($resource, 512), 148);

        $this->assertMatchesRegularExpression('/^\d+\s?$/ ', $data['checksum']);
    }

    public function testArchiveFileHeaderContainsTypeFlag()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z1flag', fread($resource, 512), 156);

        $this->assertEquals((binary) '0', $data['flag']);
    }

    public function testArchiveFileHeaderContainsLinkName()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z100name', fread($resource, 512), 157);

        $this->assertEquals((binary) '', $data['name']);
    }

    public function testArchiveFileHeaderContainsMagicCode()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('a5magic', fread($resource, 512), 257);

        $this->assertEquals((binary) 'ustar', $data['magic']);
    }

    public function testArchiveFileHeaderContainsVersion()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('cmajor/cminor', fread($resource, 512), 263);

        $this->assertEquals(0x30, $data['major']);
        $this->assertEquals(0x30, $data['minor']);
    }

    public function testArchiveFileHeaderContainsUserName()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z32uname', fread($resource, 512), 265);

        $this->assertEquals((binary) 'docker', $data['uname']);
    }

    public function testArchiveFileHeaderContainsGroupName()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z32uname', fread($resource, 512), 297);

        $this->assertEquals((binary) 'docker', $data['uname']);
    }

    public function testArchiveFileHeaderContainsDevMajor()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z8devmajor', fread($resource, 512), 329);

        $this->assertEquals((binary) '000000 ', $data['devmajor']);
    }

    public function testArchiveFileHeaderContainsDevMinor()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z8devminor', fread($resource, 512), 337);

        $this->assertEquals((binary) '000000 ', $data['devminor']);
    }

    public function testArchiveFileHeaderContainsPrefix()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 0, SEEK_SET);

        $data = unpack('Z155prefix', fread($resource, 512), 345);

        $this->assertEquals((binary) '', $data['prefix']);
    }

    public function testArchiveFileHeaderContainsPad()
    {
        $archive = new TarArchive(new Dockerfile(new Dockerfile\From('php')));

        $resource = $archive->asResource();
        fseek($resource, 512, SEEK_SET);

        $this->assertEquals((binary) 'FROM php', fread($resource, 8));
    }
    //Z155Z12
    //
    // '',                               // pad
}
