Feature: Play the battles
  In order to win the war game
  As a player
  I need to win battles one by one

  Background:
    Given there are two players around the table

  Scenario: One of the two cards is higher than the other
    Given player 1 receives following cards:
      | rank | suit  |
      | 7    | clubs |
    And player 2 receives following cards:
      | rank | suit  |
      | 5    | clubs |
    When players play a battle
    Then player 1 wins all 2 cards of the battle

  Scenario: Single war
    Given player 1 receives following cards:
      | rank | suit   |
      | 3    | spades |
      | x    |        |
      | x    |        |
      | x    |        |
      | 8    | clubs  |
    And player 2 receives following cards:
      | rank | suit   |
      | ace  | hearts |
      | x    |        |
      | x    |        |
      | x    |        |
      | 8    | hearts |
    When players play a battle
    Then player 2 wins all 10 cards of the battle
    And there was 1 war

  Scenario: Double war
    Given player 1 receives following cards:
      | rank | suit   |
      | ace  | hearts |
      | x    |        |
      | x    |        |
      | x    |        |
      | 3    | spades |
      | x    |        |
      | x    |        |
      | x    |        |
      | 8    | clubs  |
    And player 2 receives following cards:
      | rank | suit   |
      | king | spades |
      | x    |        |
      | x    |        |
      | x    |        |
      | 3    | hearts |
      | x    |        |
      | x    |        |
      | x    |        |
      | 8    | hearts |
    When players play a battle
    Then player 1 wins all 18 cards of the battle
    And there were 2 wars
