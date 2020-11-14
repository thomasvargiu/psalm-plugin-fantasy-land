Feature: emptyy
  In order to use emptyy
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Monoid;
      use function FunctionalPHP\FantasyLand\emptyy;
      """

  Scenario: Asserting psalm recognizes return type
    Given I have the following code
      """
      /** @var Monoid<string> */
      $dummy = null;
      /** @psalm-trace $check */
      $check = $dummy::mempty();
      /** @psalm-trace $result */
      $result = emptyy($dummy);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $check: FunctionalPHP\FantasyLand\Monoid<string> |
      | Trace | $result: FunctionalPHP\FantasyLand\Monoid<string> |
    And I see no other errors
