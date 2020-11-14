Feature: map
  In order to use map
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Monad;
      use function FunctionalPHP\FantasyLand\map;
      """

  Scenario: Asserting psalm recognizes return type
    Given I have the following code
      """
      /** @var Monad<string> $monad */
      $monad = null;
      /** @psalm-trace $result */
      $result = map(function (string $a): int { return 2; });
      /** @psalm-trace $value */
      $value = map(function (string $a): int { return 2; }, $monad);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $result: callable(FunctionalPHP\FantasyLand\Functor<string>):FunctionalPHP\FantasyLand\Functor<int(2)> |
      | Trace | $value: FunctionalPHP\FantasyLand\Functor<int(2)>                                                      |
    And I see no other errors
