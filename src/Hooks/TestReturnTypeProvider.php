<?php
declare(strict_types=1);

namespace TMV\PsalmFantasyLand\Hooks;

use FunctionalPHP\FantasyLand\Useful\Identity;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class TestReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array
    {
        return [
            strtolower(Identity::class),
        ];
    }

    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a method should return
     * but another plugin may be able to determine the type, return null. Otherwise return a mixed union type if
     * something should be returned, but can't be more specific.
     *
     * @param  list<PhpParser\Node\Arg>    $call_args
     * @param  ?array<Type\Union> $template_type_parameters
     * @param lowercase-string $method_name_lowercase
     * @param lowercase-string $called_method_name_lowercase
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name_lowercase = null
    ): ?Type\Union
    {
        if ('map' !== $method_name_lowercase) {
            return null;
        }
        $args = array_map(function (PhpParser\Node\Arg $arg) use ($source) {
            return $source->getNodeTypeProvider()->getType($arg->value);
        }, $call_args);
        return null;
    }
}
