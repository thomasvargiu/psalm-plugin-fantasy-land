<?php

declare(strict_types=1);

namespace FunctionalPHP\FantasyLand;

/**
 * @template T
 * @template-extends Functor<T>
 */
interface Traversable extends Functor
{
    /**
     * traverse :: Applicative f => (a -> f b) -> f (t b)
     *
     * Where the `a` is value inside of container.
     *
     * @template A of Applicative
     * @psalm-param callable(T): A $fn
     * @psalm-return A
     */
    public function traverse(callable $fn);
}
