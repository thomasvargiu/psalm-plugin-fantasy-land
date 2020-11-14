Feature: Foldable
  In order to use Foldable
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Foldable;
      """

  Scenario: Asserting reduce :: Foldable f => f a ~> ((b, a) -> b, b) -> b
    Given I have the following code
      """
      /** @var callable(int, string): int $function */
      $function = null;
      /** @var Foldable<string> $foldable */
      $foldable = null;
      /** @psalm-trace $value */
      $value = $foldable->reduce($function, 0);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: int |
    And I see no other errors

  Scenario: Asserting reduce() fails with different value type on reduce function
    Given I have the following code
      """
      /** @var callable(int, int): int $function */
      $function = null;
      /** @var Foldable<string> $foldable */
      $foldable = null;
      /** @psalm-trace $value */
      $value = $foldable->reduce($function, 0);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidScalarArgument | Type string should be a subtype of int |

  Scenario: Asserting reduce() fails with invalid reduce with invalid accumlator
    Given I have the following code
      """
      /** @var callable(string, string): int $function */
      $function = null;
      /** @var Foldable<string> $foldable */
      $foldable = null;
      /** @psalm-trace $value */
      $value = $foldable->reduce($function, 0);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidScalarArgument | Type int should be a subtype of string |
