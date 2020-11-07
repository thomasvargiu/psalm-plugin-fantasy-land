Feature: map
  In order to use map
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use function FunctionalPHP\FantasyLand\map;
      use FunctionalPHP\FantasyLand\Functor;
      use FunctionalPHP\FantasyLand\Useful\Identity;
      """

  Scenario: Asserting psalm recognizes return type
    Given I have the following code
      """
      $identity = Identity::of('foo');
      /** @psalm-trace $result */
      $result = map(function (string $a): int { return 2; });
      /** @psalm-trace $value */
      $value = map(function (string $a): int { return 2; }, $identity);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $result: callable(FunctionalPHP\FantasyLand\Functor<string>):FunctionalPHP\FantasyLand\Functor<int(2)> |
      | Trace | $value: FunctionalPHP\FantasyLand\Functor<int(2)>                                                      |
    And I see no other errors
