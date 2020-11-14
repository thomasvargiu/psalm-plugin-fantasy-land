Feature: Chain
  In order to use Chain
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Chain;
      """

  Scenario: Asserting bind :: Chain m => m a ~> (a -> m b) -> m b
    Given I have the following code
      """
      /** @var Chain<string> $foo */
      $foo = null;
      /** @var callable(string): Chain<int> $function */
      $function = null;
      /** @psalm-trace $value */
      $value = $foo->bind($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Chain<int> |
    And I see no other errors

  Scenario: Asserting ap :: Chain
    Given I have the following code
      """
      /** @var Chain<callable(string): int> $foo */
      $foo = null;
      /** @var Chain<string> $ap */
      $ap = null;
      /** @psalm-trace $value */
      $value = $foo->ap($ap);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Chain<int> |
    And I see no other errors

  Scenario: Asserting map :: Chain f => f a ~> (a -> b) -> f b
    Given I have the following code
      """
      /** @var Chain<string> $foo */
      $foo = null;
      /** @var callable(string): int $function */
      $function = null;
      /** @psalm-trace $value */
      $value = $foo->map($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Chain<int>                                                    |
    And I see no other errors