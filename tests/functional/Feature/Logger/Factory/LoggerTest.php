<?php declare(strict_types=1);

namespace functional\Factory;

use Kiboko\Component\Satellite\Feature\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    public static function configProvider()
    {
        yield [
            'expected' => [
                'type' => 'stderr',
                'destinations' => [],
            ],
            'expected_class' => \Kiboko\Component\Satellite\Feature\Logger\Builder\Logger::class,
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
            'expected_class' => \Kiboko\Component\Satellite\Feature\Logger\Builder\Logger::class,
            'actual' => [
                'logger' => [
                    'type' => 'null'
                ]
            ]
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('configProvider')]
    public function testWithConfiguration(array $expected, string $expectedClass, array $actual): void
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
            $expectedClass,
            $factory->compile($normalizedConfig)->getBuilder()
        );
    }

    public function testFailToValidate(): void
    {
        $factory = new Logger\Service();
        $this->assertFalse($factory->validate([
            'type' => 'unexpected'
        ]));
    }
}
