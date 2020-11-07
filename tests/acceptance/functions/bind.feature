Feature: bind
  In order to use bind
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand as f;
      use FunctionalPHP\FantasyLand\Useful\Identity;
      """

  Scenario: Asserting psalm recognizes return type
    Given I have the following code
      """
      $identity = Identity::of('foo');
      /** @psalm-trace $result */
      $result = f\bind(function (string $a): f\Monad { return Identity::of(2); });
      /** @psalm-trace $value */
      $value = f\bind(function (string $a): f\Monad { return Identity::of(2); }, $identity);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $result: callable(FunctionalPHP\FantasyLand\Monad<string>):FunctionalPHP\FantasyLand\Monad<int(2)> |
      | Trace | $value: FunctionalPHP\FantasyLand\Monad<int(2)>                                                    |
    And I see no other errors
