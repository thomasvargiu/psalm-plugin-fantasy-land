<?php
declare(strict_types=1);

namespace TMV\PsalmFantasyLand\Hooks;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

class CurryNReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('FunctionalPHP\FantasyLand\curryN'),
        ];
    }

    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a function should
     * return but another plugin may be able to determine the type, return null. Otherwise return a mixed union type
     * if something should be returned, but can't be more specific.
     *
     * @param  list<\PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?Type\Union
    {
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return null;
        }

        if (! in_array($function_id, static::getFunctionIds(), true)) {
            return null;
        }

        $codebase = $statements_source->getCodebase();

        if (3 !== count($call_args)) {
            return null;
        }

        $numberOfArgumentsArg = $call_args[0];
        $functionArg = $call_args[1];
        $argsArg = $call_args[2];

        /** @var Type\Union $callableType */
        $functionArgType = $statements_source->getNodeTypeProvider()->getType($functionArg->value) ?? Type::getMixed();
        $functionClosure = CallableTypeComparator::getCallableFromAtomic($codebase, array_values($functionArgType->getAtomicTypes())[0]);

        /** @var Type\Union|null $numberOfArgumentsArgType */
        $numberOfArgumentsArgType = $statements_source->getNodeTypeProvider()->getType($numberOfArgumentsArg->value);

        /** @var Type\Union|null $argsArgType */
        $argsArgType = $statements_source->getNodeTypeProvider()->getType($argsArg->value);

        if (!$functionClosure || ! $numberOfArgumentsArgType || ! $numberOfArgumentsArgType->isSingleIntLiteral()) {
            return null;
        }

        /** @var Type\Atomic\TLiteralInt|null $literalInt */
        $literalInt = array_values($numberOfArgumentsArgType->getLiteralInts())[0] ?? null;

        if (null === $literalInt) {
            return null;
        }

        /** @var Type\Atomic\TKeyedArray|null $argsKeyedArray */
        $argsKeyedArray = array_values(array_filter($argsArgType->getAtomicTypes(), function (Type\Atomic $type) {
            return $type instanceof Type\Atomic\TKeyedArray;
        }))[0] ?? null;

        if ($argsKeyedArray) {
            $indexedTypes = array_values($argsKeyedArray->properties);
            /** @var FunctionLikeParameter[] $applyParams */
            $applyParams = array_slice(array_values($functionClosure->params ?? []), 0, $literalInt->value);
            foreach ($applyParams as $i => $param) {
                if (! UnionTypeComparator::isContainedBy(
                    $codebase,
                    $indexedTypes[$i] ?? Type::getNull(),
                    $param->type ?? Type::getMixed()
                )) {
                    if (IssueBuffer::accepts(
                        new InvalidArgument(
                            'Argument ' . $i . ' requires ' . ($param->type ?? Type::getMixed()) . ', ' . ($indexedTypes[$i] ?? 'none') . ' provided',
                            new CodeLocation($statements_source, $argsArg),
                            $function_id
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                }
            }
        }

        return new Type\Union([
            new Type\Atomic\TCallable(
                'callable',
                array_slice(array_values($functionClosure->params ?? []), $literalInt->value),
                $functionClosure->return_type
            )
        ]);
    }
}
