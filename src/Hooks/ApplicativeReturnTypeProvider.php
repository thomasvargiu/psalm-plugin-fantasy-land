<?php
declare(strict_types=1);

namespace TMV\PsalmFantasyLand\Hooks;

use FunctionalPHP\FantasyLand\Apply;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;

class ApplicativeReturnTypeProvider implements AfterMethodCallAnalysisInterface
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

        [$className, $methodName] = explode('::', $appearing_method_id);

        if (! $codebase->classImplements($className, Apply::class)) {
            return;
        }

        if ('ap' !== $methodName) {
            return;
        }

        $classlikeStorage = $codebase->classlike_storage_provider->get($className);
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        if (null === $classlikeStorage->template_types) {
            return;
        }

        $applyType = static::getTemplateType($classlikeStorage, Apply::class);

        if (null === $applyType) {
            return;
        }

        $varType = $nodeTypeProvider->getType($expr->var);

        if (null === $varType || ! $varType->isSingle()) {
            // throw
            return;
        }

        $typeIndex = false;
        if ($applyType->hasTemplate()) {
            $templateType = array_values($applyType->getTemplateTypes())[0];
            $typeIndex = array_search($templateType->param_name, array_keys($classlikeStorage->template_types));
        }

        if (false === $typeIndex) {
            return;
        }

        $varAtomicType = array_values($varType->getAtomicTypes())[0];

        if (! $varAtomicType instanceof Type\Atomic\TGenericObject) {
            return;
        }

        $callable = CallableTypeComparator::getCallableFromAtomic($codebase, array_values($varAtomicType->type_params[$typeIndex]->getAtomicTypes())[0]);

        if (null === $callable) {
            if (IssueBuffer::accepts(
                new InvalidArgument(
                    'Applicative where ap() method is called must contain a callable',
                    new CodeLocation($statements_source, $expr),
                    $method_id
                ),
                $statements_source->getSuppressedIssues()
            )) {
                // keep soldiering on
            }
            return;
        }

        $callable->return_type;

        if (null === $callable->return_type) {
            return;
        }

        $typeParams = $varAtomicType->type_params;
        $typeParams[$typeIndex] = $callable->return_type;

        /** @psalm-var non-empty-list<Type\Union> $typeParams */

        $type = new Type\Atomic\TGenericObject(
            $className,
            $typeParams
        );
        $return_type_candidate = new Type\Union([$type]);
    }

    private static function getTemplateType(
        ClassLikeStorage $storage,
        string $definingClass,
        ?string $templateName = null,
        ?Type\Union $lastType = null
    ): ?Type\Union
    {
        $templateTypes = $storage->template_type_extends[$definingClass] ?? [];

        if (null !== $templateName) {
            $type = $templateTypes[$templateName] ?? null;
        } else {
            $type = array_values($templateTypes)[0] ?? null;
        }

        if (null === $type) {
            return $lastType;
        }
        $types = array_values($type->getTemplateTypes());

        if (0 === count($types)) {
            return $type;
        }
        $resultType = $types[0];

        return static::getTemplateType($storage, $resultType->defining_class, $resultType->param_name, $type);
    }
}
