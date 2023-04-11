<?php

namespace unit\Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command;
use Kiboko\Component\Satellite\Cloud\Diff;
use Kiboko\Component\Satellite\Cloud\DTO;
use PHPUnit\Framework\TestCase;

class StepDiffTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function diffIsEquivalent()
    {
        $diff = new Diff\StepDiff(
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
        );

        $commands = $diff->diff(
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
        );

        $expected = new DTO\CommandBatch();

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDifferentLabels()
    {
        $diff = new Diff\StepDiff(
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
        );

        $this->expectExceptionObject(new Diff\LabelDoesNotMatchException('Label does not match between actual and desired step definition.'));

        $diff->diff(
            new DTO\Step(
                'Dolor sit amet',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDifferentCodes()
    {
        $diff = new Diff\StepDiff(
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
        );

        $this->expectExceptionObject(new Diff\CodeDoesNotMatchException('Code does not match between actual and desired step definition.'));

        $diff->diff(
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('lorem_ipsum'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDifferentOrders()
    {
        $diff = new Diff\StepDiff(
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
        );

        $this->expectExceptionObject(new Diff\OrderDoesNotMatchException('Order does not match between actual and desired step definition.'));

        $diff->diff(
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                3
            ),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDifferentProbes()
    {
        $diff = new Diff\StepDiff(
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
        );

        $commands = $diff->diff(
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
            new DTO\Step(
                'Lorem ipsum',
                new DTO\StepCode('dolor_sit_amet'),
                [],
                new DTO\ProbeList(
                    new DTO\Probe('Lorem ipsum 2', 'lorem_ipsum', 1),
                    new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                ),
                2
            ),
        );

        $expected = new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineStepProbeCommand($pipelineId, new DTO\StepCode('dolor_sit_amet'), new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1)),
            new Command\Pipeline\AddPipelineStepProbeCommand($pipelineId, new DTO\StepCode('dolor_sit_amet'), new DTO\Probe('Lorem ipsum 2', 'lorem_ipsum', 1)),
        );

        $this->assertEquals($expected, $commands);
    }
}
