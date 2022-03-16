<?php

namespace unit\Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Diff;
use Kiboko\Component\Satellite\Cloud\DTO;
use PHPUnit\Framework\TestCase;

class ProbeDiffTest extends TestCase
{
    /** @test */
    public function diffIsEquivalent()
    {
        $diff = new Diff\ProbeDiff();

        $commands = $diff->diff(
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 2),
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 2)
        );

        $this->assertCount(0, $commands);
    }

    /** @test */
    public function hasDifferentLabels()
    {
        $diff = new Diff\ProbeDiff();

        $this->expectExceptionObject(new Diff\LabelDoesNotMatchException('Label does not match between actual and desired probe definition.'));

        $diff->diff(
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 2),
            new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2)
        );
    }

    /** @test */
    public function hasDifferentCodes()
    {
        $diff = new Diff\ProbeDiff();

        $this->expectExceptionObject(new Diff\CodeDoesNotMatchException('Code does not match between actual and desired probe definition.'));

        $diff->diff(
            new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 2),
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 2)
        );
    }

    /** @test */
    public function hasDifferentOrders()
    {
        $diff = new Diff\ProbeDiff();

        $this->expectExceptionObject(new Diff\OrderDoesNotMatchException('Order does not match between actual and desired probe definition.'));

        $diff->diff(
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 1),
            new DTO\Probe('Lorem ipsum', 'dolor_sit_amet', 2)
        );
    }
}
