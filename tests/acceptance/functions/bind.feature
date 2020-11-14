Feature: bind
  In order to use bind
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Monad;
      use function FunctionalPHP\FantasyLand\bind;
      """

  Scenario: Asserting psalm recognizes return type
    Given I have the following code
      """
      /** @var Monad<string> $monad */
      $monad = null;
      /** @var callable(string): Monad<int> $function */
      $function = null;
      /** @psalm-trace $result */
      $result = bind($function);
      /** @psalm-trace $value */
      $value = bind($function, $monad);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $result: callable(FunctionalPHP\FantasyLand\Monad<string>):FunctionalPHP\FantasyLand\Monad<int> |
      | Trace | $value: FunctionalPHP\FantasyLand\Monad<int>                                                    |
    And I see no other errors
