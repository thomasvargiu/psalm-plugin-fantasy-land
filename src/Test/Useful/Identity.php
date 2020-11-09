<?php
declare(strict_types=1);

namespace TMV\PsalmFantasyLand\Test\Useful;

use FunctionalPHP\FantasyLand;

/**
 * @template T
 * @template-implements FantasyLand\Monad<T>
 */
class Identity implements FantasyLand\Monad
{
    const of = 'FunctionalPHP\FantasyLand\Useful\Identity::of';

    /**
     * @psalm-var T
     * @var mixed
     */
    private $value;

    /**
     * @template U
     * @psalm-param U $value
     * @psalm-return Identity<U>
     * @inheritdoc
     *
     * @param mixed $value
     * @return Identity
     */
    public static function of($value)
    {
        return new self($value);
    }

    /**
     * @psalm-param T $value
     * @param mixed $value
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @inheritdoc
     * @template U
     * @psalm-param callable(T): U $function
     * @psalm-return Identity<U>
     */
    public function map(callable $function): FantasyLand\Functor
    {
        return static::of($function($this->value));
    }

    /**
     * @template U
     * @psalm-param FantasyLand\Apply<U> $applicative
     * @psalm-assert Identity<U> $applicative
     * @psalm-return (T is callable ? Identity : never-return)
     *
     * @param FantasyLand\Apply $applicative
     * @return FantasyLand\Apply
     */
    public function ap(FantasyLand\Apply $applicative): FantasyLand\Apply
    {
        if (! $applicative instanceof Identity) {
            throw new \RuntimeException('Applicative should be of the same type: ' . Identity::class);
        }

        return $applicative->map($this->value);
    }

    /**
     * @inheritdoc
     */
    public function bind(callable $function)
    {
        return $function($this->value);
    }
}
