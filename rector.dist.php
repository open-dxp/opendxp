<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\DataProviderArrayItemsNewLinedRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Symfony\Symfony73\Rector\Class_\GetFunctionsToAsTwigFunctionAttributeRector;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bundles',
        __DIR__ . '/lib',
        __DIR__ . '/models',
    ])
    ->withSkip([
        StringClassNameToClassConstantRector::class            => [
            __DIR__ . '/lib/Bootstrap.php',
        ],
        ReturnEarlyIfVariableRector::class                     => [
            __DIR__ . '/lib/Navigation/Page/Document.php',
        ],
        RemoveUnreachableStatementRector::class                => [
            __DIR__ . '/lib/Twig/Extension/DocumentEditableExtension.php',
        ],
        GetFunctionsToAsTwigFunctionAttributeRector::class     => [
            __DIR__ . '/lib/Twig/Extension/DocumentEditableExtension.php',
            __DIR__ . '/lib/Twig/Extension/HelpersExtension.php',
            __DIR__ . '/lib/Twig/Extension/OpenDxpObjectExtension.php',
        ],
        GetFiltersToAsTwigFilterAttributeRector::class         => [
            __DIR__ . '/lib/Twig/Extension/DocumentEditableExtension.php',
            __DIR__ . '/lib/Twig/Extension/HelpersExtension.php',
            __DIR__ . '/lib/Twig/Extension/OpenDxpObjectExtension.php',
        ],
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/models/Version/Adapter/ProxyVersionStorageAdapter.php',
        ],
        DisallowedEmptyRuleFixerRector::class,
        ExplicitBoolCompareRector::class,
        JoinStringConcatRector::class,
        // todo: buggy?
        NullToStrictStringFuncCallArgRector::class,
        // todo: we should get rid of this!
        CompleteDynamicPropertiesRector::class,
        // @see https://github.com/rectorphp/rector/issues/9587
        ForeachItemsAssignToEmptyArrayToAssignRector::class,
    ])
    ->withIndent(' ', 4)
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true)
    ->withComposerBased(symfony: true)
    ->withAttributesSets(symfony: true)
    ->withSymfonyContainerPhp(__DIR__ . '/../../var/cache/dev/App_KernelDevDebugContainer.php')
    ->withRules([
        DataProviderArrayItemsNewLinedRector::class,
        ArraySpreadInsteadOfArrayMergeRector::class,
    ])
    ->withTypeCoverageLevel(0);