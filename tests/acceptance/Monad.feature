Feature: Monad
  In order to use Monad
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Monad;
      """

  Scenario: Asserting of :: Applicative f => a -> f a
    Given I have the following code
      """
      /** @var Monad<null> $foo */
      $foo = null;
      /** @psalm-trace $monad */
      $monad = $foo::of('foo');
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $monad: FunctionalPHP\FantasyLand\Monad<string(foo)> |
    And I see no other errors

  Scenario: Asserting map :: Monad f => f a ~> (a -> b) -> f b
    Given I have the following code
      """
      /** @var Monad<string> $foo */
      $foo = null;
      /** @var callable(string): int $function */
      $function = null;
      /** @psalm-trace $value */
      $value = $foo->map($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Monad<int> |
    And I see no other errors

  Scenario: Asserting ap :: does not recognize type
    Given I have the following code
      """
      /** @var Monad<callable(string): int> $foo */
      $foo = null;
      /** @var Monad<string> $ap */
      $ap = null;
      /** @psalm-trace $value */
      $value = $foo->ap($ap);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Monad<int> |
    And I see no other errors

  Scenario: Asserting bind :: Monad m => m a ~> (a -> m b) -> m b
    Given I have the following code
      """
      /** @var Monad<string> $foo */
      $foo = null;
      /** @var callable(string): Monad<int> $function */
      $function = null;
      /** @psalm-trace $value */
      $value = $foo->bind($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Monad<int> |
    And I see no other errors
