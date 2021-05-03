<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger\Service;

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
            'expected_class' => 'Kiboko\\Component\\Satellite\\Feature\\Logger\\Builder\\Logger',
            'actual' => [
                'logger' => [
                    'type' => 'stderr'
                ],
            ],
        ];

        yield [
            'expected' => [
                'type' => 'null',
                'destinations' => [],
            ],
            'expected_class' => 'Kiboko\\Component\\Satellite\\Feature\\Logger\\Builder\\Logger',
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
    public function testWithConfigurationAndProcessor(array $expected, string $expectedClass, array $actual): void
    {
        $service = new Logger\Service();
        $normalizedConfig = $service->normalize($actual);

        $this->assertEquals(
            new Logger\Configuration(),
            $service->configuration()
        );

        $this->assertEquals(
            $expected,
            $normalizedConfig
        );

        $this->assertTrue($service->validate($actual));

        $this->assertInstanceOf(
            $expectedClass,
            $service->compile($normalizedConfig)->getBuilder()
        );
    }
}
