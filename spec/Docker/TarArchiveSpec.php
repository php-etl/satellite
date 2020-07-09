<?php

namespace spec\Kiboko\Component\ETL\Satellite\Docker;

use Kiboko\Component\ETL\Satellite\Docker\Dockerfile;
use PhpSpec\ObjectBehavior;

class TarArchiveSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith(
            new Dockerfile(new Dockerfile\From('php'))
        );

        $this->asResource()->shouldBeResource();
    }
}
