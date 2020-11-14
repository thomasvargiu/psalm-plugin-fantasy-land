Feature: Apply
  In order to use Apply
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Apply;
      """

  Scenario: Asserting map :: Apply f => f a ~> (a -> b) -> f b
    Given I have the following code
      """
      /** @var Apply<string> $foo */
      $foo = null;
      $function = function (string $a): int {
          return random_int(-1, 1);
      };
      /** @psalm-trace $value */
      $value = $foo->map($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Apply<int> |
    And I see no other errors

  Scenario: Asserting ap() will return InvalidArgument on a different Applicator type
    Given I have the following code
      """
      /** @var Apply<callable(int): int> $foo */
      $foo = null;
      /** @var Apply<string> $ap */
      $ap = null;

      $foo->ap($ap);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidArgument | Type FunctionalPHP\FantasyLand\Apply<string> should be a subtype of FunctionalPHP\FantasyLand\Apply<int> |
    And I see no other errors


  Scenario: Asserting ap() will return InvalidMethodCall when called on non callable Applicator
    Given I have the following code
      """
      /** @var Apply<string> $foo */
      $foo = null;
      /** @var Apply<string> $ap */
      $ap = null;

      $foo->ap($ap);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidMethodCall | Applicative where ap() method is called must contain a callable |
    And I see no other errors