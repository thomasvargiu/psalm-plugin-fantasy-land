<?php
declare(strict_types=1);

namespace TMV\PsalmFantasyLand\Hooks;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Chain;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;

class ApplicativeReturnTypeProvider implements AfterMethodCallAnalysisInterface
{
    public static function getClassLikeNames(): array
    {
        return [
            \FunctionalPHP\FantasyLand\Apply::class,
        ];
    }

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
        [$className, $methodName] = explode('::', $appearing_method_id);

        if (! $codebase->classImplements($className, Apply::class)) {
            return;
        }

        if ('ap' !== $methodName) {
            return;
        }

        $parentFQCLN = $statements_source->getParentFQCLN();
        $templateTypeMap = $statements_source->getTemplateTypeMap();
        $classlikeStorage = $codebase->classlike_storage_provider->get($className);
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $applyType = static::getTemplateType($classlikeStorage, Apply::class);

        if (null === $applyType) {
            return;
        }

        $varType = $nodeTypeProvider->getType($expr->var);

        $typeIndex = false;
        if ($applyType->hasTemplate()) {
            /** @var Type\Atomic\TTemplateParam $templateType */
            $templateType = array_values($applyType->getTemplateTypes())[0];
            $typeIndex = array_search($templateType->param_name, array_keys($classlikeStorage->template_types));
        }

        if (! $varType->isSingle()) {
            // throw
            return;
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
            // throw
            return;
        }

        $callable->return_type;

        $type = new Type\Atomic\TGenericObject(
            $className,
            [$callable->return_type]
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
        if (null !== $templateName) {
            $type = $storage->template_type_extends[$definingClass][$templateName] ?? null;
        } else {
            $type = array_values($storage->template_type_extends[$definingClass])[0] ?? null;
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
