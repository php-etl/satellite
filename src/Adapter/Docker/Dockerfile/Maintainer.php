<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class Maintainer implements LayerInterface
{
    private string $name;
    private string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function __toString()
    {
        return sprintf('LABEL maintainer="%s <%s>"', $this->name, $this->email);
    }
}
