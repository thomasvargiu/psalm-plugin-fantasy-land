<?php
declare(strict_types=1);

namespace TMV\PsalmPluginFantasyLand\Hooks;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Chain;
use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidMethodCall;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;

class ChainReturnTypeProvider implements AfterMethodCallAnalysisInterface
{
    /**
     * @param  MethodCall|StaticCall $expr
     * @param  FileManipulation[] $file_replacements
     */
    public static function afterMethodCallAnalysis(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Type\Union &$return_type_candidate = null
    ): void
    {
        if (! $expr instanceof MethodCall) {
            return;
        }

        [$className, $methodName] = explode('::', $declaring_method_id);

        if ('bind' !== $methodName) {
            return;
        }

        $classlikeStorage = $codebase->classlike_storage_provider->get($className);

        if (Chain::class !== $className
            && ! $codebase->classExtendsOrImplements($className, Chain::class)
            && false === array_search(Chain::class, $classlikeStorage->parent_interfaces)
        ) {
            return;
        }

        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $arg1 = $expr->args[0] ?? null;

        if (null === $arg1) {
            return;
        }

        $argType = $nodeTypeProvider->getType($arg1->value);

        if (null === $argType) {
            return;
        }

        if (! $argType->isSingle()) {
            return;
        }

        $callable = CallableTypeComparator::getCallableFromAtomic(
            $codebase,
            array_values($argType->getAtomicTypes())[0]
        );

        if (null === $callable || null === $callable->return_type) {
            return;
        }

        $return_type_candidate = $callable->return_type;
    }
}
