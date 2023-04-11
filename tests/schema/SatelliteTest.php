<?php declare(strict_types=1);

namespace schema\Kiboko\Component\Satellite;

use JsonSchema\SchemaStorage;
use JsonSchema\SchemaStorageInterface;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\TestCase;
use schema\Kiboko\Component\Satellite\Constraint\MatchesJSONSchema;

final class SatelliteTest extends TestCase
{
    private ?SchemaStorageInterface $storage = null;

    public function setUp(): void
    {
        $this->storage = new SchemaStorage();

        $schemas = [
            'schema.json',
            'schema/definitions.json',
            'schema/expressions.json',
            'schema/plugin/akeneo.json',
            'schema/plugin/batch.json',
            'schema/plugin/csv.json',
            'schema/plugin/custom.json',
            'schema/plugin/fastmap.json',
            'schema/plugin/ftp.json',
            'schema/plugin/http.json',
            'schema/plugin/json.json',
            'schema/plugin/sftp.json',
            'schema/plugin/spreadsheet.json',
            'schema/plugin/sql.json',
            'schema/plugin/stream.json',
            'schema/plugin/sylius.json',
        ];

        foreach ($schemas as $schema) {
            $this->storage->addSchema(
                'https://raw.githubusercontent.com/php-etl/satellite/master/' . $schema,
                $this->schemaFromPath(__DIR__ . '/../../' . $schema)
            );
        }
    }

    private static function schemaFromPath(string $path): \stdClass|array
    {
        return \json_decode(\file_get_contents($path), null, 512, JSON_THROW_ON_ERROR);
    }

    public static function validDataProvider(): \Generator
    {
//        yield '[0.2] Empty unique satellite' => [
//            (object) [
//                'satellite' => (object) []
//            ],
//            self::schemaFromPath(__DIR__.'/../../schema.json'),
//        ];

        yield 'Unique satellite deploying in Docker' => [
            (object) [
                'satellite' => (object) [
                    'docker' => (object) [
                        'from' => 'php:8.0-cli',
                        'workdir' => 'build/'
                    ],
                    'pipeline' => (object) [
                        'steps' => []
                    ]
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Unique satellite deploying in Filesystem' => [
            (object) [
                'satellite' => (object) [
                    'filesystem' => (object) [
                        'path' => 'build/'
                    ],
                    'pipeline' => (object) [
                        'steps' => []
                    ]
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Akeneo plugin extractor' => [
            (object) [
                'enterprise' => true,
                'extractor' => (object) [
                    'type' => 'product',
                    'method' => 'all'
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema/plugin/akeneo.json'),
        ];
    }

    public static function invalidDataProvider(): \Generator
    {
        yield '[0.2] Empty unique satellite' => [
            (object) [
                'satellite' => (object) []
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Empty multiple satellite' => [
            (object) [
                'version' => '0.3',
                'satellites' => (object) [
                    'satellite1' => [],
                    'satellite2' => [],
                ],
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Unrelated file format' => [
            [
                'foo' => []
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Unique satellite with multiple adapters' => [
            [
                'satellite' => [
                    'docker' => [
                        'from' => 'php:8.0-cli'
                    ],
                    'filesystem' => [
                        'path' => 'build/'
                    ]
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Empty satellite with multiple adapters' => [
            [
                'version' => '0.3',
                'satellites' => [
                    'product_export' => [
                        'docker' => [
                            'from' => 'php:8.0-cli'
                        ],
                        'filesystem' => [
                            'path' => 'build/'
                        ]
                    ]
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema.json'),
        ];

        yield 'Akeneo plugin empty extractor' => [
            (object) [
                'enterprise' => true,
                'extractor' => (object) [
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema/plugin/akeneo.json'),
        ];

        yield 'Akeneo plugin empty lookup' => [
            (object) [
                'enterprise' => true,
                'lookup' => (object) [
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema/plugin/akeneo.json'),
        ];

        yield 'Akeneo plugin empty loader' => [
            (object)[
                'enterprise' => true,
                'loader' => (object) [
                ]
            ],
            self::schemaFromPath(__DIR__ . '/../../schema/plugin/akeneo.json'),
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validDataProvider')]
    public function testDocumentIsValid($json, \stdClass|array $schema)
    {
        $this->assertThat(
            $json,
            new MatchesJSONSchema($this->storage, $schema)
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidDataProvider')]
    public function testDocumentIsInvalid($json, \stdClass|array $schema)
    {
        $this->assertThat(
            $json,
            new LogicalNot(
                new MatchesJSONSchema($this->storage, $schema)
            )
        );
    }
}
