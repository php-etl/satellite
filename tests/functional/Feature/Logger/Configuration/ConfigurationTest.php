<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger\Configuration;

use Kiboko\Component\Satellite\Feature\Logger\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config;

class ConfigurationTest extends TestCase
{
    private ?Config\Definition\Processor $processor = null;

    protected function setUp(): void
    {
        $this->processor = new Config\Definition\Processor();
    }

    public function validConfigProvider()
    {
        yield [
            'expected' => [
                'destinations' => [],
            ],
            'actual' => [
            ]
        ];

        yield [
            'expected' => [
                'type' => 'null',
                'destinations' => [],
            ],
            'actual' => [
                'type' => 'null',
            ]
        ];
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testValidConfig($expected, $actual)
    {
        $config = new Configuration();

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration(
                $config,
                [
                    $actual
                ]
            )
        );
    }
}
