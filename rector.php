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

    $rectorConfig->skip([StringClassNameToClassConstantRector::class]);
};
