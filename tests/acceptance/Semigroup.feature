Feature: Semigroup
  In order to use Semigroup
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Semigroup;
      """

  Scenario: Asserting concat :: Semigroup a => a ~> a -> a
    Given I have the following code
      """
      /** @var Semigroup<string> $applySemigroup */
      $applySemigroup = null;
      /** @var Semigroup<string> $semigroup */
      $semigroup = null;
      /** @psalm-trace $value */
      $value = $semigroup->concat($applySemigroup);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Semigroup<string> |
    And I see no other errors

  Scenario: Asserting concat() fails on different wrapped type
    Given I have the following code
      """
      /** @var Semigroup<int> $applySemigroup */
      $applySemigroup = null;
      /** @var Semigroup<string> $semigroup */
      $semigroup = null;
      /** @psalm-trace $value */
      $value = $semigroup->concat($applySemigroup);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidScalarArgument | Argument 1 of FunctionalPHP\FantasyLand\Semigroup::concat expects FunctionalPHP\FantasyLand\Semigroup<string>, FunctionalPHP\FantasyLand\Semigroup<int> provided |
