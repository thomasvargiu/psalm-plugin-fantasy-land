Feature: curryN
  In order to use curryN
  As a Psalm user
  I need Psalm to typecheck function

  Background:
    Given I have the default psalm configuration
    And I have the default code preamble

  Scenario: Asserting psalm recognizes return type with 1 literal parameter
    Given I have the following code
      """
      $function = function (string $a, int $b, string $c): string {
          return $a . $b . $c;
      };
      /** @psalm-trace $curried */
      $curried = f\curryN(1, $function, ['foo']);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $curried: callable(int, string):string |
    And I see no other errors

  Scenario: Asserting psalm throw error with invalid keyed params
    Given I have the following code
      """
      $function = function (string $a, int $b, string $c): string {
          return $a . $b . $c;
      };
      /** @psalm-trace $curried */
      $curried = f\curryN(1, $function, [2]);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message |
      | InvalidArgument | Argument 0 requires string, int provided |
      | Trace           | $curried: callable(int, string):string   |
    And I see no other errors
