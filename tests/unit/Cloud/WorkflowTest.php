<?php

namespace unit\Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\StringStartsWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use unit\Kiboko\Component\Satellite\Cloud\CLient\GetWorkflowItemThrowNotFoundException;
use unit\Kiboko\Component\Satellite\Cloud\CLient\GetWorkflowItem;

class WorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        $context = new DummyContext();
        $context->changeOrganization(new Satellite\Cloud\DTO\OrganizationId('5624f073-4085-42ee-b33b-e8869c3f2f51'));
        $context->changeWorkspace(new Satellite\Cloud\DTO\WorkspaceId('577d9281-88ad-45be-8e12-307adf5e787c'));
    }

    public function testLegacyConfiguration(): void
    {
        $workflow = Satellite\Cloud\Workflow::fromLegacyConfiguration(
            [
                'composer' => [
                    'autoload' => ['psr4' => ['Namespace\\' => ['paths' => ['path/to/namespace']]]],
                    'require' => ['vendor/package', 'vendor/package2'],
                    'repositories' => [['name' => 'Repo Name', 'type' => 'git', 'url' => 'http://repo.url']],
                    'auth' => [['url' => 'http://auth.url', 'token' => 'authToken']]
                ],
                'code' => 'my_workflow',
                'workflow' => [
                    'jobs' => [
                        [
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
                                        'name' => 'Transform',
                                        'code' => 'fastmap',
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
                        ['action' => ['name' => 'Test Action', 'code' => 'action123']]

                    ],
                ],
            ],
        );

        $this->assertInstanceOf(Satellite\Cloud\DTO\Workflow::class, $workflow);
        $this->assertEquals('my_workflow', $workflow->code());
        $this->assertEquals(1, $workflow->composer()->autoload()->count());
        $this->assertEquals(1, $workflow->composer()->auths()->count());
        $this->assertEquals(1, $workflow->composer()->repositories()->count());
        $this->assertEquals(2, $workflow->composer()->packages()->count());
        $this->assertEquals(2, $workflow->jobs()->count());
    }

    public function testCreatesDefaultNameAndCodeIfNotProvided(): void {
        $configuration = [
            'workflow' => [
                'name' => 'Test Workflow',
                'jobs' => [
                    ['pipeline' => ['name' => 'Test Pipeline', 'code' => 'pipeline123', 'steps' => []]],
                    ['action' => ['name' => 'Test Action', 'code' => 'action123']]
                ]
            ]
        ];

        $workflow = Satellite\Cloud\Workflow::fromLegacyConfiguration($configuration);

        $this->assertInstanceOf(Satellite\Cloud\DTO\Workflow::class, $workflow);
        $this->assertMatchesRegularExpression('/workflow[a-f0-9]{8}/', $workflow->code());
    }

    public function testFromLegacyConfigurationWithEmptyConfig()
    {
        $this->expectException(\RuntimeException::class);

        $workflow = Satellite\Cloud\Workflow::fromLegacyConfiguration([]);

        $this->assertInstanceOf(Satellite\Cloud\DTO\Workflow::class, $workflow);
    }

    public function testThrowsExceptionForUnsupportedJobType()
    {
        $configuration = [
            'workflow' => ['jobs' => [['invalidType' => []]]],
            'composer' => [
                'autoload' => ['psr4' => []],
                'require' => [],
                'repositories' => []
            ]
        ];

        $this->expectException(\RuntimeException::class);

        Satellite\Cloud\Workflow::fromLegacyConfiguration($configuration);
    }

    public function testFromLegacyConfigurationWithExpressions()
    {
        $configuration = [
            'workflow' => [
                'jobs' => [
                    [
                        'pipeline' => [
                            'code' => 'pipelineCode',
                            'steps' => [
                                [
                                    'csv' => [
                                        'extractor' => [
                                            'file_path' => new Expression('../data/products.csv'),
                                        ],
                                    ],
                                ],
                                [
                                    'fastmap' => [
                                        'map' => [
                                            'expression' => new Expression('value > 100'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'composer' => [
                'autoload' => ['psr4' => []],
                'require' => [],
                'repositories' => []
            ]
        ];

        $workflow = Satellite\Cloud\Workflow::fromLegacyConfiguration($configuration);

        WorkflowTest::assertStringNotStartsWith(
            '@=', $workflow->jobs()->get('pipelineCode')->stepList->get('step1')->config['fastmap']['map']['expression']
        );
        WorkflowTest::assertStringStartsWith(
            '@=', $workflow->jobs()->get('pipelineCode')->stepList->get('step0')->config['csv']['extractor']['file_path']
        );
    }

    public function testFromApiWithIdIsSuccessful()
    {
        $workflowId = new Satellite\Cloud\DTO\WorkflowId('fe8a4711-624d-44ad-a4a5-e578427a7887');

        $result = Satellite\Cloud\Workflow::fromApiWithId(
            GetWorkflowItem::create(),
            $workflowId
        );

        $this->assertInstanceOf(Satellite\Cloud\DTO\ReferencedWorkflow::class, $result);
        $this->assertEquals('fe8a4711-624d-44ad-a4a5-e578427a7887', $result->id()->asString());
    }

    public function testFromApiWithIdThrowException()
    {
        $workflowId = new Satellite\Cloud\DTO\WorkflowId('fe8a4711-624d-44ad-a4a5-e578427a7887');

        $this->expectException(Satellite\Cloud\AccessDeniedException::class);
        $this->expectExceptionMessage('Could not retrieve the workflow.');

        Satellite\Cloud\Workflow::fromApiWithId(
            GetWorkflowItemThrowNotFoundException::create(),
            $workflowId
        );
    }

    public function testCreate()
    {
        $workflow = new Satellite\Cloud\Workflow(
            new DummyContext()
        );

        $commandBatch = $workflow->create(
            new Satellite\Cloud\DTO\Workflow(
                'Test Workflow',
                'test_workflow',
                new Satellite\Cloud\DTO\JobList(),
                new Satellite\Cloud\DTO\Composer(
                    new Satellite\Cloud\DTO\Autoload(),
                    new Satellite\Cloud\DTO\PackageList(),
                    new Satellite\Cloud\DTO\RepositoryList(),
                    new Satellite\Cloud\DTO\AuthList(),
                ),
            )
        );

        $this->assertInstanceOf(Satellite\Cloud\DTO\CommandBatch::class, $commandBatch);
        $this->assertCount(1, $commands = $commandBatch->getIterator());

        $this->assertInstanceOf(Satellite\Cloud\Command\Workflow\DeclareWorkflowCommand::class, $commands[0]);
    }

    public function testUpdate()
    {
        $workflow = new Satellite\Cloud\Workflow(
            new DummyContext()
        );

        $commandBatch = $workflow->update(
            new Satellite\Cloud\DTO\ReferencedWorkflow(
                new Satellite\Cloud\DTO\WorkflowId('fe8a4711-624d-44ad-a4a5-e578427a7887'),
                new Satellite\Cloud\DTO\Workflow(
                    'Test Workflow',
                    'test_workflow',
                    new Satellite\Cloud\DTO\JobList(),
                    new Satellite\Cloud\DTO\Composer(
                        new Satellite\Cloud\DTO\Autoload(),
                        new Satellite\Cloud\DTO\PackageList(),
                        new Satellite\Cloud\DTO\RepositoryList(),
                        new Satellite\Cloud\DTO\AuthList(),
                    ),
                ),
            ),
            new Satellite\Cloud\DTO\Workflow(
                'Test Workflow',
                'test_workflow',
                new Satellite\Cloud\DTO\JobList(
                    new Satellite\Cloud\DTO\Workflow\Pipeline(
                        'Extract product from csv',
                        new Satellite\Cloud\DTO\JobCode('csv.extractor'),
                        new Satellite\Cloud\DTO\StepList(),
                        0
                    )
                ),
                new Satellite\Cloud\DTO\Composer(
                    new Satellite\Cloud\DTO\Autoload(),
                    new Satellite\Cloud\DTO\PackageList(),
                    new Satellite\Cloud\DTO\RepositoryList(),
                    new Satellite\Cloud\DTO\AuthList(),
                ),
            ),
        );

        $this->assertInstanceOf(Satellite\Cloud\DTO\CommandBatch::class, $commandBatch);
        $this->assertCount(1, $commands = $commandBatch->getIterator());

        $this->assertInstanceOf(Satellite\Cloud\Command\Workflow\PrependWorkflowJobCommand::class, $commands[0]);
    }

    public function testRemove()
    {
        $workflow = new Satellite\Cloud\Workflow(
            new DummyContext()
        );

        $commandBatch = $workflow->remove(
            new Satellite\Cloud\DTO\WorkflowId('fe8a4711-624d-44ad-a4a5-e578427a7887')
        );

        $this->assertInstanceOf(Satellite\Cloud\DTO\CommandBatch::class, $commandBatch);
        $this->assertCount(1, $commands = $commandBatch->getIterator());

        $this->assertInstanceOf(Satellite\Cloud\Command\Workflow\RemoveWorkflowCommand::class, $commands[0]);
    }

    public static function assertStringNotStartsWith(mixed $needle, string $haystack, string $message = ''): void
    {
        $constraint = new LogicalNot(
            new StringStartsWith($needle),
        );

        static::assertThat($haystack, $constraint, $message);
    }
}
