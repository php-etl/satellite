<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_100,
        SymfonyLevelSetList::UP_TO_SYMFONY_60,
    ]);

    $rectorConfig->skip([StringClassNameToClassConstantRector::class => [
        __DIR__ . '/src/Plugin/Filtering/Builder/Drop.php',
        __DIR__ . '/src/Plugin/Filtering/Builder/Reject.php',
        __DIR__ . '/src/Plugin/SFTP/Builder/Extractor.php',
        __DIR__ . '/src/Plugin/SFTP/Builder/Loader.php',
        __DIR__ . '/src/Plugin/Batching/Builder/Fork.php',
        __DIR__ . '/src/Plugin/Batching/Builder/Merge.php',
        __DIR__ . '/src/Feature/Rejection/Builder/RabbitMQBuilder.php',
        __DIR__ . '/src/Feature/Rejection/Builder/Rejection.php',
        __DIR__ . '/src/Pipeline/Extractor.php',
        __DIR__ . '/src/Pipeline/Loader.php',
        __DIR__ . '/src/Pipeline/Transformer.php',
    ]]);
};
