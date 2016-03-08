<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use PHPUnit_Framework_Assert as Assert;

use WarGame\Domain\Card\Card;
use WarGame\Domain\Card\Deck;
use WarGame\Domain\Card\Rank;
use WarGame\Domain\Card\Suit;
use WarGame\Domain\Game\Battle;
use WarGame\Domain\Game\War;
use WarGame\Domain\Game\WarGame;
use WarGame\Domain\Player\Player;
use WarGame\Domain\Player\PlayerId;
use WarGame\Domain\Player\Table;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context, SnippetAcceptingContext
{
    /**
     * @var Deck
     */
    private $deck;

    /**
     * @var WarGame
     */
    private $warGame;

    /**
     * @var Battle
     */
    private $battle;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var Player
     */
    private $currentBattleWinner;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given there is a french deck of 52 cards
     */
    public function thereIsAFrenchDeck()
    {
        $this->deck = Deck::frenchDeck();
    }

    /**
     * @Given cards are shuffled
     */
    public function cardsAreShuffled()
    {
        $this->deck->shuffle();
    }

    /**
     * @Given there are two players around the table
     */
    public function thereAreTwoPlayersAroundTheTable()
    {
        $this->table = new Table();
        $this->table->welcome(Player::named('Player 1', PlayerId::generate()));
        $this->table->welcome(Player::named('Player 2', PlayerId::generate()));

        Assert::assertTrue($this->table->isFull());
    }

    /**
     * @When I deal all the cards face down, one at a time
     */
    public function iDealAllTheCardsFaceDownOneAtATime()
    {
        while (!$this->deck->isEmpty()) {
            $this->table->getPlayer1()->receiveCard($this->deck->pickFromTheTop());
            $this->table->getPlayer2()->receiveCard($this->deck->pickFromTheTop());
        }
    }

    /**
     * @Then each player has :nbOfCards cards
     */
    public function eachPlayerHasCards($nbOfCards)
    {
        Assert::assertSame($this->table->getPlayer1()->getNbOfCards(), intval($nbOfCards));
        Assert::assertSame($this->table->getPlayer2()->getNbOfCards(), intval($nbOfCards));
    }

    /**
     * @Then player :playerNumber should have following cards:
     */
    public function playerShouldHaveFollowingCards($playerNumber, TableNode $cards)
    {
        $player = intval($playerNumber) === 1 ? $this->table->getPlayer1() : $this->table->getPlayer2();

        $cardsOfThePlayer = $player->getDeck()->getCards();

        foreach ($cards as $id => $card) {
            $rankValue = $card['rank'];
            $suitValue = strtolower($card['suit']);

            $rank = is_numeric($card['rank']) ? new Rank(intval($rankValue)) : Rank::$rankValue();
            $suit = Suit::$suitValue();

            $expectedCard = new Card($rank, $suit);

            Assert::assertTrue($expectedCard->isEquals($cardsOfThePlayer[$id]));
        }
    }

    /**
     * @When the first battle starts
     */
    public function theFirstBattleStarts()
    {
        $this->battle = new Battle(1, $this->table);
    }

    /**
     * @Then following cards should be on the table:
     */
    public function followingCardsShouldBeOnTheTable(TableNode $cards)
    {
        try {
            $this->battle->play();
        } catch (War $e) {}

        $cardsOnTheTable = $this->battle->getAllCards();

        foreach ($cards as $card) {
            $rankValue = $card['rank'];
            $suitValue = strtolower($card['suit']);

            $rank = is_numeric($card['rank']) ? new Rank(intval($rankValue)) : Rank::$rankValue();
            $suit = Suit::$suitValue();

            $expectedCard = new Card($rank, $suit);
            foreach ($cardsOnTheTable as $cardOnTheTable) {
                if ($cardOnTheTable->isEquals($expectedCard)) {
                    continue 2;
                }
            }

            Assert::fail(sprintf('Card "%s" is not on the table', $expectedCard->toString()));
        }
    }

    /**
     * @Given player :playerNumber receives following cards:
     */
    public function playerReceivesFollowingCards($playerNumber, TableNode $cards)
    {
        $player = intval($playerNumber) === 1 ? $this->table->getPlayer1() : $this->table->getPlayer2();

        foreach ($cards as $card) {
            $rankValue = $card['rank'];
            $suitValue = strtolower($card['suit']);

            if ('x' == $rankValue) {
                $player->receiveCard(Card::random());

                continue;
            }

            $rank = is_numeric($card['rank']) ? new Rank(intval($rankValue)) : Rank::$rankValue();
            $suit = Suit::$suitValue();

            $player->receiveCard(new Card($rank, $suit));
        }
    }

    /**
     * @When players finish to play the battle
     */
    public function playersFinishToPlayTheBattle()
    {
        $this->currentBattleWinner = $this->battle->play();
    }

    /**
     * @When players finish to play the war battle
     */
    public function playersFinishToPlayTheWarBattle()
    {
        try {
            $this->currentBattleWinner = $this->battle->play(Battle::BATTLE_IS_IN_WAR);
        } catch (War $e) {}
    }

    /**
     * @Then it's war
     */
    public function itSWar()
    {
        try {
            $this->currentBattleWinner = $this->battle->play();

            Assert::fail('Players are not in war.');
        } catch (War $e) {}
    }

    /**
     * @When it's double war
     */
    public function itSDoubleWar()
    {
        try {
            $this->currentBattleWinner = $this->battle->play(Battle::BATTLE_IS_IN_WAR);

            Assert::fail('Players are not in double war.');
        } catch (War $e) {}
    }

    /**
     * @Then player :playerNumber wins all :numberOfCards cards of the battle
     */
    public function playerWinsAllCardsOfTheBattle($playerNumber, $numberOfCards)
    {
        Assert::assertSame(intval($numberOfCards), $this->battle->numberOfCardsInTheBattle());
        Assert::assertSame($this->currentBattleWinner, intval($playerNumber) === 1 ? $this->table->getPlayer1() : $this->table->getPlayer2());
        Assert::assertSame($this->currentBattleWinner->getNbOfCards(), intval($numberOfCards));
    }

    /**
     * @Given there are following cards in the deck:
     */
    public function thereAreFollowingCardsInTheDeck(TableNode $cards)
    {
        $this->deck = new Deck();

        foreach ($cards as $card) {
            $rankValue = $card['rank'];
            $suitValue = strtolower($card['suit']);

            if ('x' == $rankValue) {
                $this->deck->addToTheTop(Card::random());

                continue;
            }

            $rank = is_numeric($card['rank']) ? new Rank(intval($rankValue)) : Rank::$rankValue();
            $suit = Suit::$suitValue();

            $this->deck->addToTheTop(new Card($rank, $suit));
        }
    }

    /**
     * @When cards are dealt but not shuffled
     */
    public function cardsAreDealtButNotShuffled()
    {
        $this->warGame = new WarGame($this->deck, $this->table);
        $this->warGame->dealCards();
    }

    /**
     * @When players play the game
     */
    public function playersPlayTheGame()
    {
        $this->warGame->play();
    }

    /**
     * @Then player :playerNumber wins the game
     */
    public function playerWinsTheGame($playerNumber)
    {
        $player = intval($playerNumber) === 1 ? $this->table->getPlayer1() : $this->table->getPlayer2();
        $otherPlayer = intval($playerNumber) === 2 ? $this->table->getPlayer1() : $this->table->getPlayer2();

        Assert::assertTrue($this->warGame->getWinner()->getId()->sameValueAs($player->getId()));
        Assert::assertNotTrue($this->warGame->getWinner()->getId()->sameValueAs($otherPlayer->getId()));
    }

    /**
     * @Then they played :nbOfBattles battles
     */
    public function theyPlayedBattles($nbOfBattles)
    {
        Assert::assertSame(count($this->warGame->getBattles()), intval($nbOfBattles));
    }
}
