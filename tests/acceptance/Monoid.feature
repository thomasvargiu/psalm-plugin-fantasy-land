Feature: Monoid
  In order to use Monoid
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Monoid;
      """

  Scenario: Asserting mempty :: Monoid m => () -> m
    Given I have the following code
      """
      /** @var Monoid<string> $foo */
      $foo = null;
      /** @psalm-trace $monoid */
      $monoid = $foo::mempty();
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $monoid: FunctionalPHP\FantasyLand\Monoid |
    And I see no other errors

  Scenario: Asserting concat :: Semigroup a => a ~> a -> a
    Given I have the following code
      """
      /** @var Monoid<string> $applySemigroup */
      $applySemigroup = null;
      /** @var Monoid<string> $semigroup */
      $semigroup = null;
      /** @psalm-trace $value */
      $value = $semigroup->concat($applySemigroup);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Monoid<string> |
    And I see no other errors