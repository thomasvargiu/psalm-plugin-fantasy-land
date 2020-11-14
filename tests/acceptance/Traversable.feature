Feature: Traversable
  In order to use Traversable
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use FunctionalPHP\FantasyLand\Applicative;
      use FunctionalPHP\FantasyLand\Traversable;
      use FunctionalPHP\FantasyLand\Chain;
      """

  Scenario: Asserting traverse :: Applicative f => (a -> f b) -> f (t b)
    Given I have the following code
      """
      /** @var callable(string): Applicative<int> $function */
      $function = null;
      /** @var Traversable<string> */
      $traversable = null;
      /** @psalm-trace $value */
      $value = $traversable->traverse($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | Trace | $value: FunctionalPHP\FantasyLand\Applicative<FunctionalPHP\FantasyLand\Traversable<int>> |
    And I see no other errors

  Scenario: Asserting traverse should throw error on function not returning Applicative
    Given I have the following code
      """
      /** @var callable(string): Chain<string> $function */
      $function = null;
      /** @var Traversable<string> */
      $traversable = null;
      $traversable->traverse($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidArgument | Argument 1 of FunctionalPHP\FantasyLand\Traversable::traverse expects callable(string):FunctionalPHP\FantasyLand\Applicative<mixed>, callable(string):FunctionalPHP\FantasyLand\Chain<string> provided |
    And I see no other errors

  Scenario: Asserting traverse should throw error on function not accepting Traversable type
    Given I have the following code
      """
      /** @var callable(int): Applicative<int> $function */
      $function = null;
      /** @var Traversable<string> */
      $traversable = null;
      $traversable->traverse($function);
      """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | InvalidScalarArgument | Type string should be a subtype of int |
    And I see no other errors