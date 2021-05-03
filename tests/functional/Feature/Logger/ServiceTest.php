<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger;

use Kiboko\Component\Satellite\Feature\Logger;
use PHPUnit\Framework\TestCase;

final class ServiceTest extends TestCase
{
    public function configProvider()
    {
        yield [
            'expected' => [
                'type' => 'stderr',
                'destinations' => [],
            ],
            'actual' => [
                'logger' => [
                    'type' => 'stderr'
                ]
            ]
        ];

        yield [
            'expected' => [
                'type' => 'null',
                'destinations' => [],
            ],
            'actual' => [
                'logger' => [
                    'type' => 'null'
                ]
            ]
        ];
    }

    /**
     * @dataProvider configProvider
     */
    public function testWithConfiguration(array $expected, array $actual): void
    {
        $factory = new Logger\Service();
        $normalizedConfig = $factory->normalize($actual);

        $this->assertEquals(
            new Logger\Configuration(),
            $factory->configuration()
        );

        $this->assertEquals(
            $expected,
            $normalizedConfig
        );

        $this->assertTrue(
            $factory->validate($actual)
        );

        $this->assertInstanceOf(
            'Kiboko\\Component\\Satellite\\Feature\\Logger\\Repository',
            $factory->compile($normalizedConfig)
        );
    }
}
