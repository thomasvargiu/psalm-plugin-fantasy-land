<?php
declare(strict_types=1);

namespace TMV\PsalmPluginFantasyLand\Hooks;

use FunctionalPHP\FantasyLand\Apply;
use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidMethodCall;
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

        [$className, $methodName] = explode('::', $declaring_method_id);

        if ('ap' !== $methodName) {
            return;
        }


        if (null === $return_type_candidate) {
            return;
        }

        $classlikeStorage = $codebase->classlike_storage_provider->get($className);

        if (Apply::class !== $className
            && ! $codebase->classExtendsOrImplements($className, Apply::class)
            && false === array_search(Apply::class, $classlikeStorage->parent_interfaces)
        ) {
            return;
        }

        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $varType = $nodeTypeProvider->getType($expr->var);

        if (null === $varType) {
            // throw
            return;
        }

        $varAtomicType = array_values($varType->getAtomicTypes())[0];

        $callable = null;
        $typeIndex = null;
        if ($classlikeStorage->name === Apply::class) {
            $typeIndex = 0;
        }

        if (null === $typeIndex) {
            if (null === $classlikeStorage->template_type_extends) {
                return;
            }

            $applyType = static::getTemplateType($classlikeStorage, Apply::class);

            if (null === $applyType) {
                return;
            }

            if ($applyType->hasTemplate()) {
                $typeIndex = static::getTemplateTypeIndex($classlikeStorage, $applyType);

                if (null === $typeIndex) {
                    return;
                }

                if (! $varAtomicType instanceof Type\Atomic\TGenericObject) {
                    return;
                }
            } elseif ($applyType->isSingle() && $applyType->hasCallableType()) {
                $callable = CallableTypeComparator::getCallableFromAtomic(
                    $codebase,
                    array_values($applyType->getAtomicTypes())[0]
                );
            }
        }

        if (! $varAtomicType instanceof Type\Atomic\TGenericObject) {
            return;
        }

        $typeParam = $varAtomicType->type_params[$typeIndex] ?? null;

        if (null === $callable && null !== $typeParam) {
            $callable = CallableTypeComparator::getCallableFromAtomic($codebase, array_values($typeParam->getAtomicTypes())[0]);
        }

        if (null === $callable) {
            if (IssueBuffer::accepts(
                new InvalidMethodCall(
                    'Applicative where ap() method is called must contain a callable',
                    new CodeLocation($statements_source, $expr)
                ),
                $statements_source->getSuppressedIssues()
            )) {
                // keep soldiering on
            }
            return;
        }

        $callableParam = $callable->params[0] ?? null;
        $applicativeParam = $expr->args[0] ?? null;

        if (null === $applicativeParam) {
            return;
        }

        $applicativeParamType = $nodeTypeProvider->getType($applicativeParam->value);

        if (null === $applicativeParamType) {
            return;
        }

        $expectedType = new Type\Union([
            new Type\Atomic\TGenericObject(Apply::class, [$callableParam->type ?? Type::getEmpty()]),
        ]);
        if (! $callableParam || ! UnionTypeComparator::isContainedBy(
                $codebase,
                $applicativeParamType,
                $expectedType
            )) {
            if (IssueBuffer::accepts(
                new InvalidArgument(
                    'Type ' . $applicativeParamType->getId() . ' should be a subtype of '
                    . $expectedType->getId(),
                    new CodeLocation($statements_source, $applicativeParam->value),
                    $declaring_method_id
                ),
                $statements_source->getSuppressedIssues()
            )) {
                // keep soldiering on
            }
            return;
        }

        if (null === $callable->return_type) {
            return;
        }

        $returnType = $return_type_candidate;

        foreach (array_values($returnType->getAtomicTypes()) as $type) {
            if (! $type instanceof Type\Atomic\TGenericObject) {
                continue;
            }

            $applyClassStorage = $codebase->classlike_storage_provider->get($type->value);
            $applyType = static::getTemplateType($applyClassStorage, Apply::class);

            if (null === $applyType) {
                return;
            }

            $index = static::getTemplateTypeIndex($applyClassStorage, $applyType);
            /** @var Type\Union $currentType */
            $currentType = $type->type_params[$index] ?? Type::getMixed();

            if (null !== $index && $currentType->hasMixed()) {
                /** @psalm-suppress PropertyTypeCoercion */
                $type->type_params[$index] = $callable->return_type;
            }
        }
    }

    private static function getTemplateTypeIndex(ClassLikeStorage $storage, Type\Union $type): ?int
    {
        if (null === $storage->template_types || ! $type->hasTemplate()) {
            return null;
        }

        $templateType = array_values($type->getTemplateTypes())[0];
        $typeIndex = array_search($templateType->param_name, array_keys($storage->template_types));

        return false !== $typeIndex ? $typeIndex : null;
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

        if (! $type->hasTemplate()) {
            return $type;
        }
        $types = array_values($type->getTemplateTypes());

        if (0 === count($types)) {
            return $type;
        }
        $resultType = $types[0];

        return static::getTemplateType($storage, $resultType->defining_class, $resultType->param_name, $type);
    }
}
