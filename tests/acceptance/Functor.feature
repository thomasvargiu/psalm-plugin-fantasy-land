Feature: Functor
  In order to use Functor
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Functor;
      """

  Scenario: Asserting map :: Functor f => f a ~> (a -> b) -> f b
    Given I have the following code
      """
      /** @var Functor<string> $foo */
      $foo = null;
      /** @var callable(string): int $function */
      $function = null;
      /** @psalm-trace $value */
      $value = $foo->map($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Functor<int>                                                    |
    And I see no other errors
