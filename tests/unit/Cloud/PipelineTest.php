<?php

namespace unit\Cloud;

use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\Cloud\DTO\ProbeList;
use Kiboko\Component\Satellite\Cloud\DTO\StepCode;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    private Satellite\Cloud\Context $context;

    protected function setUp(): void
    {
        $this->context = new Satellite\Cloud\Context();
        $this->context->changeOrganization(new Satellite\Cloud\DTO\OrganizationId('5624f073-4085-42ee-b33b-e8869c3f2f51'));
        $this->context->changeWorkspace(new Satellite\Cloud\DTO\WorkspaceId('577d9281-88ad-45be-8e12-307adf5e787c'));
    }

    public function testCreate(): void
    {
        $pipeline = new Satellite\Cloud\Pipeline($this->context);

        $batch = $pipeline->create(Satellite\Cloud\Pipeline::fromLegacyConfiguration(
            [
                'kaniko' => [
                    'from' => 'php:8.1-cli-alpine',
                ],
                'composer' => [
                    'autoload' => [
                        'psr4' => [
                            [
                                'namespace' => 'App\\\\',
                                'paths' =>
                                    [
                                        'src/',
                                    ],
                            ],
                        ],
                    ],
                ],
                'pipeline' => [
                    'name' => 'My products pipeline',
                    'code' => 'products_pipeline',
                    'steps' => [
                        [
                            'name' => 'Extract products',
                            'code' => 'csv.extractor',
                            'csv' =>
                                [
                                    'extractor' =>
                                        [
                                            'file_path' => '../data/products.csv',
                                        ],
                                ],
                        ],
                        [
                            'name' => 'Transform products',
                            'code' => 'fastmap',
                            'fastmap' =>
                                [
                                    'map' =>
                                        [
                                            [
                                                'copy' => '[sku]',
                                                'field' => '[identifier]',
                                            ],
                                        ],
                                ],
                        ],
                        [
                            'name' => 'Transform',
                            'code' => 'my_fastmap',
                            'fastmap' =>
                                [
                                    'map' =>
                                        [
                                            [
                                                'copy' => '[identifier]',
                                                'field' => '[sku]',
                                            ],
                                        ],
                                ],
                        ],
                        [
                            'name' => 'Loads products',
                            'code' => 'stream.loader',
                            'stream' =>
                                [
                                    'loader' =>
                                        [
                                            'destination' => '../data/products.ldjson',
                                            'format' => 'json',
                                        ],
                                ],
                        ],
                    ],
                ],
            ],
        ));

        $this->assertCount(1, $batch->getIterator());
    }

    public function testUpdate(): void
    {
        $pipeline = new Satellite\Cloud\Pipeline($this->context);

        $batch = $pipeline->update(
            new Satellite\Cloud\DTO\ReferencedPipeline(
                new Satellite\Cloud\DTO\PipelineId(''),
                new Satellite\Cloud\DTO\Pipeline(
                    'My products pipeline',
                    'products_pipeline',
                    new Satellite\Cloud\DTO\StepList(
                        new Satellite\Cloud\DTO\Step(
                            'Extract products',
                            new StepCode('csv.extractor'),
                            [
                                'file_path' => '../data/products.csv',
                            ],
                            new ProbeList(
                            // FIXME: add probes
                            ),
                            1
                        ),
                        new Satellite\Cloud\DTO\Step(
                            'Transform products',
                            new StepCode('fastmap'),
                            [
                                [
                                    'copy' => '[sku]',
                                    'field' => '[identifier]',
                                ],
                            ],
                            new ProbeList(
                            // FIXME: add probes
                            ),
                            2
                        ),
                        new Satellite\Cloud\DTO\Step(
                            'Load products',
                            new StepCode('stream.loader'),
                            [
                                'destination' => '../data/products.ldjson',
                                'format' => 'json',
                            ],
                            new ProbeList(
                            // FIXME: add probes
                            ),
                            3
                        )
                    ),
                    new Satellite\Cloud\DTO\Autoload()
                )
            ),
            new Satellite\Cloud\DTO\Pipeline(
                'My products pipeline',
                'products_pipeline',
                new Satellite\Cloud\DTO\StepList(
                    new Satellite\Cloud\DTO\Step(
                        'Extract products',
                        new StepCode('csv.extractor'),
                        [
                            'file_path' => '../data/products.csv',
                        ],
                        new ProbeList(
                        // FIXME: add probes
                        ),
                        1
                    ),
                    new Satellite\Cloud\DTO\Step(
                        'Load products',
                        new StepCode('stream.loader'),
                        [
                            'destination' => '../data/products.ldjson',
                            'format' => 'json',
                        ],
                        new ProbeList(
                        // FIXME: add probes
                        ),
                        2
                    ),
                ),
                new Satellite\Cloud\DTO\Autoload(),
            ),
        );

        $this->assertCount(1, $batch->getIterator());
    }

    public function testRemove(): void
    {
        $pipeline = new Satellite\Cloud\Pipeline($this->context);

        $batch = $pipeline->remove(new Satellite\Cloud\DTO\PipelineId('ccb39aac-3ed3-4dfd-93a0-d9f1d53c687b'));

        $this->assertCount(1, $batch->getIterator());
    }
}
