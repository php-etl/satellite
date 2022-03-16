<?php

namespace unit\Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command;
use Kiboko\Component\Satellite\Cloud\Diff;
use Kiboko\Component\Satellite\Cloud\DTO;
use PHPUnit\Framework\TestCase;

class ProbeListDiffTest extends TestCase
{
    /** @test */
    public function diffIsEquivalent()
    {
        $diff = new Diff\ProbeListDiff(
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepCode('zero'),
        );

        $commands = $diff->diff(
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            )
        );

        $expected = new DTO\CommandBatch();

        $this->assertEquals($expected, $commands);
    }

    /** @test */
    public function hasSameOrderWithDifferentNumbers()
    {
        $diff = new Diff\ProbeListDiff(
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            $stepCode = new DTO\StepCode('zero'),
        );

        $commands = $diff->diff(
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 2),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 3),
            )
        );

        $expected = new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 2)),
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 3)),
        );

        $this->assertEquals($expected, $commands);
    }

    /** @test */
    public function hasDifferentOrders()
    {
        $diff = new Diff\ProbeListDiff(
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            $stepCode = new DTO\StepCode('zero'),
        );

        $commands = $diff->diff(
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            new DTO\ProbeList(
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 1),
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 2),
            )
        );

        $expected = new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 1)),
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 2)),
        );

        $this->assertEquals($expected, $commands);
    }

    /** @test */
    public function hasDifferentLabels()
    {
        $diff = new Diff\ProbeListDiff(
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            $stepCode = new DTO\StepCode('zero'),
        );

        $commands = $diff->diff(
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum 2', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            )
        );

        $expected = new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum 2', 'lorem_ipsum', 1)),
        );

        $this->assertEquals($expected, $commands);
    }

    /** @test */
    public function hasDifferentCodes()
    {
        $diff = new Diff\ProbeListDiff(
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            $stepCode = new DTO\StepCode('zero'),
        );

        $commands = $diff->diff(
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum_2', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            )
        );

        $expected = new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, $stepCode, new DTO\Probe('Lorem ipsum', 'lorem_ipsum_2', 1)),
        );

        $this->assertEquals($expected, $commands);
    }
}
