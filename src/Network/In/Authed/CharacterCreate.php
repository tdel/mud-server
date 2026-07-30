<?php

namespace App\Network\In\Authed;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Room;
use App\Game\Dice\DiceRoller;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Authed\CharacterAlreadyExists;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\ChooseRace;
use App\Network\Out\Authed\InvalidRace;
use App\Network\Out\Authed\NoStartingRoom;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterCreate implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoomRepository $roomRepository,
        private readonly CharacterList $characterListAction,
        private readonly DiceRoller $diceRoller,
    ) {
    }

    public function name(): string
    {
        return 'character-create';
    }

    public function states(): array
    {
        return [ConnectionState::Authed];
    }

    public function onReceive(UserInterface $user, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $user->send(new Usage('character-create <name>'));
            $this->characterListAction->onReceive($user, '');

            return;
        }

        $account = $user->account();

        $existing = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($existing !== null) {
            $user->send(new CharacterAlreadyExists($name));
            $this->characterListAction->onReceive($user, '');

            return;
        }

        $startingRoom = $this->roomRepository->findStartingRoom();

        if ($startingRoom === null) {
            $user->send(new NoStartingRoom());
            $this->characterListAction->onReceive($user, '');

            return;
        }

        $this->promptRace($user, $account, $startingRoom, $name);
    }

    private function promptRace(UserInterface $user, Account $account, Room $startingRoom, string $name): void
    {
        $user->send(new ChooseRace());
        $user->awaitLine(function (string $line) use ($user, $account, $startingRoom, $name): void {
            $race = $this->parseRace($line);

            if ($race === null) {
                $user->send(new InvalidRace(trim($line)));
                $this->promptRace($user, $account, $startingRoom, $name);

                return;
            }

            $this->createCharacter($user, $account, $startingRoom, $name, $race);
        });
    }

    private function parseRace(string $input): ?Race
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim($input)));

        return Race::tryFrom($normalized);
    }

    private function createCharacter(UserInterface $user, Account $account, Room $startingRoom, string $name, Race $race): void
    {
        $scores = $this->rollAbilityScores();

        foreach ($race->abilityScoreBonuses() as $ability => $bonus) {
            $scores[$ability] += $bonus;
        }

        $character = new Character(
            account: $account,
            currentRoom: $startingRoom,
            name: $name,
            race: $race,
            maxHealth: 100,
            strength: $scores['strength'],
            dexterity: $scores['dexterity'],
            constitution: $scores['constitution'],
            intelligence: $scores['intelligence'],
            wisdom: $scores['wisdom'],
            charisma: $scores['charisma'],
        );

        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $user->send(new CharacterCreated($name));
        $user->send(new CharacterStats($character));
        $this->characterListAction->onReceive($user, '');
    }

    /**
     * @return array<string, int>
     */
    private function rollAbilityScores(): array
    {
        $scores = [];

        foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability) {
            $scores[$ability] = $this->rollAbilityScore();
        }

        return $scores;
    }

    private function rollAbilityScore(): int
    {
        // Official 5e method: roll 4d6, drop the lowest single die, sum the rest.
        $dice = $this->diceRoller->roll('4d6')->rolls;
        sort($dice);
        array_shift($dice);

        return array_sum($dice);
    }
}
