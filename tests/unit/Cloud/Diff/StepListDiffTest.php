<?php

namespace unit\Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command;
use Kiboko\Component\Satellite\Cloud\Diff;
use Kiboko\Component\Satellite\Cloud\DTO;
use PHPUnit\Framework\TestCase;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

class StepListDiffTest extends TestCase
{
    public function initialDataWithThreeSteps(): \Traversable
    {
        yield new DTO\Step(
            'Lorem ipsum',
            new DTO\StepCode('lorem_ipsum'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            1
        );

        yield new DTO\Step(
            'Dolor sit amet',
            new DTO\StepCode('dolor_sit_amet'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            2
        );

        yield new DTO\Step(
            'Consecutir es',
            new DTO\StepCode('consecutir_es'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            3
        );
    }

    public function initialDataWithFiveSteps(): \Traversable
    {
        yield new DTO\Step(
            'Lorem ipsum',
            new DTO\StepCode('lorem_ipsum'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            1
        );

        yield new DTO\Step(
            'Dolor sit amet',
            new DTO\StepCode('dolor_sit_amet'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            2
        );

        yield new DTO\Step(
            'Consecutir es',
            new DTO\StepCode('consecutir_es'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            3
        );

        yield new DTO\Step(
            'Adipiscing elit',
            new DTO\StepCode('adipiscing_elit'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            4
        );

        yield new DTO\Step(
            'Sed cursus nunc nec',
            new DTO\StepCode('sed_cursus_nunc_nec'),
            [],
            new DTO\ProbeList(
                new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
            ),
            5
        );
    }

    public function equivalentDataProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\CommandBatch(),
        ];
    }

    public function equivalentDataWithDifferentOrderNumbersProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label, $step->code, $step->config, $step->probes, $step->order + 1),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label, $step->code, $step->config, $step->probes, $step->order + 1),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(),
        ];
    }

    public function equivalentDataWithDifferentOrderPutFirstAsLast(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 1 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 4),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveAfterPipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es'), new DTO\StepCode('lorem_ipsum')),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 1 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 4),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveAfterPipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es'), new DTO\StepCode('lorem_ipsum')),
            ),
        ];
    }

    public function equivalentDataWithDifferentOrderPutLastAsFirst(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 3 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum'), new DTO\StepCode('consecutir_es')),
            ),
        ];
    }

    public function equivalentDataWithDifferentOrderPutMiddleAsFirst(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum'), new DTO\StepCode('dolor_sit_amet')),
            ),
        ];

        yield 'Step list with 5 steps, putting second as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum'), new DTO\StepCode('dolor_sit_amet')),
            ),
        ];

        yield 'Step list with 5 steps, putting third as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 3 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum'), new DTO\StepCode('consecutir_es')),
            ),
        ];

        yield 'Step list with 5 steps, putting fourth as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 4 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum'), new DTO\StepCode('adipiscing_elit')),
            ),
        ];
    }

    public function equivalentDataWithDifferentOrderPutMiddleAsLast(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 4),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\MoveBeforePipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es'), new DTO\StepCode('dolor_sit_amet')),
            ),
        ];
    }

    public function equivalentDataWithDifferentLabelsProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label . ' 2', $step->code, $step->config, $step->probes, $step->order),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label . ' 2', $step->code, $step->config, $step->probes, $step->order),
                iterator_to_array($this->initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(),
        ];
    }

    public function equivalentDataWithDifferentCodesProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                => new DTO\Step($step->label, new DTO\StepCode($step->code->asString() . '_2'), $step->config, $step->probes, $step->order),
                iterator_to_array($this->initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('dolor_sit_amet')),
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es')),
                new Command\Pipeline\AppendPipelineStepCommand($pipelineId,
                    new DTO\Step(
                        'Lorem ipsum',
                        new DTO\StepCode('lorem_ipsum_2'),
                        [],
                        new DTO\ProbeList(
                            new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                            new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                        ),
                        1
                    ),
                ),
                new Command\Pipeline\AppendPipelineStepCommand($pipelineId,
                    new DTO\Step(
                        'Dolor sit amet',
                        new DTO\StepCode('dolor_sit_amet_2'),
                        [],
                        new DTO\ProbeList(
                            new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                            new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                        ),
                        2
                    ),
                ),
                new Command\Pipeline\AppendPipelineStepCommand($pipelineId,
                    new DTO\Step(
                        'Consecutir es',
                        new DTO\StepCode('consecutir_es_2'),
                        [],
                        new DTO\ProbeList(
                            new DTO\Probe('Lorem ipsum', 'lorem_ipsum', 1),
                            new DTO\Probe('Dolor sit amet', 'dolor_sit_amet', 2),
                        ),
                        3
                    ),
                ),
            ),

        ];
    }

    public function equivalentDataWithRemovedFirstItemProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_slice(iterator_to_array($this->initialDataWithThreeSteps()), 1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_slice(iterator_to_array($this->initialDataWithFiveSteps()), 1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
            ),
        ];
    }

    public function equivalentDataWithRemovedLastItemProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithThreeSteps())),
            new DTO\StepList(...array_slice(iterator_to_array($this->initialDataWithThreeSteps()), 0, -1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es')),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array($this->initialDataWithFiveSteps())),
            new DTO\StepList(...array_slice(iterator_to_array($this->initialDataWithFiveSteps()), 0, -1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('sed_cursus_nunc_nec')),
            ),
        ];
    }

    /**
     * @test
     * @dataProvider equivalentDataProvider
     */
    public function diffIsEquivalent($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentOrderNumbersProvider
     */
    public function hasSameOrderWithDifferentNumbers($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentOrderPutFirstAsLast
     */
    public function hasChangeOrdersPutFirstAsLast($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentOrderPutLastAsFirst
     */
    public function hasChangeOrdersPutLastAsFirst($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentOrderPutMiddleAsLast
     */
    public function hasChangeOrdersPutMiddleAsLast($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentOrderPutMiddleAsFirst
     */
    public function hasChangeOrdersPutMiddleAsFirst($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentLabelsProvider
     */
    public function hasDifferentLabels($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithDifferentCodesProvider
     */
    public function hasDifferentCodes($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithRemovedFirstItemProvider
     */
    public function hasDeletedFirstItem($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    /**
     * @test
     * @dataProvider equivalentDataWithRemovedLastItemProvider
     */
    public function hasDeletedLastItem($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }
}
