<?php

namespace unit\Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command;
use Kiboko\Component\Satellite\Cloud\Diff;
use Kiboko\Component\Satellite\Cloud\DTO;
use PHPUnit\Framework\TestCase;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

class StepListDiffTest extends TestCase
{
    public static function initialDataWithThreeSteps(): \Traversable
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

    public static function initialDataWithFiveSteps(): \Traversable
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

    public static function equivalentDataProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\CommandBatch(),
        ];
    }

    public static function equivalentDataWithDifferentOrderNumbersProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label, $step->code, $step->config, $step->probes, $step->order + 1),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label, $step->code, $step->config, $step->probes, $step->order + 1),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(),
        ];
    }

    public static function equivalentDataWithDifferentOrderPutFirstAsLast(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 1 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 4),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('lorem_ipsum'),
                ),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 1 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 6),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('adipiscing_elit'),
                    new DTO\StepCode('sed_cursus_nunc_nec'),
                    new DTO\StepCode('lorem_ipsum'),
                ),
            ),
        ];
    }

    public static function equivalentDataWithDifferentOrderPutLastAsFirst(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 3 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('dolor_sit_amet'),
                ),
            ),
        ];
    }

    public static function equivalentDataWithDifferentOrderPutMiddleAsFirst(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('consecutir_es'),
                ),
            ),
        ];

        yield 'Step list with 5 steps, putting second as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('adipiscing_elit'),
                    new DTO\StepCode('sed_cursus_nunc_nec'),
                ),
            ),
        ];

        yield 'Step list with 5 steps, putting third as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 3 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('adipiscing_elit'),
                    new DTO\StepCode('sed_cursus_nunc_nec'),
                ),
            ),
        ];

        yield 'Step list with 5 steps, putting fourth as first' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 4 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 0),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('adipiscing_elit'),
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('dolor_sit_amet'),
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('sed_cursus_nunc_nec'),
                ),
            ),
        ];
    }

    public static function equivalentDataWithDifferentOrderPutMiddleAsLast(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => $step->order !== 2 ? $step : new DTO\Step($step->label, $step->code, $step->config, $step->probes, 4),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\ReorderPipelineStepCommand(
                    $pipelineId,
                    new DTO\StepCode('lorem_ipsum'),
                    new DTO\StepCode('consecutir_es'),
                    new DTO\StepCode('dolor_sit_amet'),
                ),
            ),
        ];
    }

    public static function equivalentDataWithDifferentLabelsProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label . ' 2', $step->code, $step->config, $step->probes, $step->order),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(),
        ];

        yield 'Step list with 5 steps' => [
            new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                    => new DTO\Step($step->label . ' 2', $step->code, $step->config, $step->probes, $step->order),
                iterator_to_array(self::initialDataWithFiveSteps())
            )),
            new DTO\CommandBatch(),
        ];
    }

    public static function equivalentDataWithDifferentCodesProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_map(
                fn (DTO\Step $step)
                => new DTO\Step($step->label, new DTO\StepCode($step->code->asString() . '_2'), $step->config, $step->probes, $step->order),
                iterator_to_array(self::initialDataWithThreeSteps())
            )),
            new DTO\CommandBatch(
                new Command\Pipeline\PrependPipelineStepCommand($pipelineId,
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
                new Command\Pipeline\AddAfterPipelineStepCommand($pipelineId,
                    new DTO\StepCode('lorem_ipsum_2'),
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
                new Command\Pipeline\AddAfterPipelineStepCommand($pipelineId,
                    new DTO\StepCode('dolor_sit_amet_2'),
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
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('dolor_sit_amet')),
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es')),
            ),

        ];
    }

    public static function equivalentDataWithRemovedFirstItemProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_slice(iterator_to_array(self::initialDataWithThreeSteps()), 1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_slice(iterator_to_array(self::initialDataWithFiveSteps()), 1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('lorem_ipsum')),
            ),
        ];
    }

    public static function equivalentDataWithRemovedLastItemProvider(): \Traversable
    {
        yield 'Step list with 3 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithThreeSteps())),
            new DTO\StepList(...array_slice(iterator_to_array(self::initialDataWithThreeSteps()), 0, -1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('consecutir_es')),
            ),
        ];

        yield 'Step list with 5 steps' => [
            $pipelineId = new DTO\PipelineId('00000000-0000-0000-0000-000000000000'),
            new DTO\StepList(...iterator_to_array(self::initialDataWithFiveSteps())),
            new DTO\StepList(...array_slice(iterator_to_array(self::initialDataWithFiveSteps()), 0, -1)),
            new DTO\CommandBatch(
                new Command\Pipeline\RemovePipelineStepCommand($pipelineId, new DTO\StepCode('sed_cursus_nunc_nec')),
            ),
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataProvider')]
    public function testDiffIsEquivalent($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentOrderNumbersProvider')]
    public function testHasSameOrderWithDifferentNumbers($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentOrderPutFirstAsLast')]
    public function testHasChangeOrdersPutFirstAsLast($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentOrderPutLastAsFirst')]
    public function testHasChangeOrdersPutLastAsFirst($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentOrderPutMiddleAsLast')]
    public function testHasChangeOrdersPutMiddleAsLast($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentOrderPutMiddleAsFirst')]
    public function testHasChangeOrdersPutMiddleAsFirst($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentLabelsProvider')]
    public function testHasDifferentLabels($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithDifferentCodesProvider')]
    public function testHasDifferentCodes($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithRemovedFirstItemProvider')]
    public function testHasDeletedFirstItem($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('equivalentDataWithRemovedLastItemProvider')]
    public function testHasDeletedLastItem($pipelineId, $left, $right, $expected)
    {
        $diff = new Diff\StepListDiff($pipelineId);

        $commands = $diff->diff($left, $right);

        $this->assertEquals($expected, $commands);
    }
}
