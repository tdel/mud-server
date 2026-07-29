<?php

namespace App\Network\In\Authed;

use App\Auth\Client;
use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Room;
use App\Game\Dice\DiceRoller;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\Out\Authed\CharacterAlreadyExists;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\ChooseRace;
use App\Network\Out\Authed\InvalidRace;
use App\Network\Out\Authed\NoStartingRoom;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Usage;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterCreate extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoomRepository $roomRepository,
        private readonly CharacterList $charactersCommand,
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

    public function onClientAction(Client $client, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $client->send(new Usage('character-create <name>'));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $account = $client->account();

        $existing = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($existing !== null) {
            $client->send(new CharacterAlreadyExists($name));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $startingRoom = $this->roomRepository->findStartingRoom();

        if ($startingRoom === null) {
            $client->send(new NoStartingRoom());
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $this->promptRace($client, $account, $startingRoom, $name);
    }

    private function promptRace(Client $client, Account $account, Room $startingRoom, string $name): void
    {
        $client->send(new ChooseRace());
        $client->awaitLine(function (string $line) use ($client, $account, $startingRoom, $name): void {
            $race = $this->parseRace($line);

            if ($race === null) {
                $client->send(new InvalidRace(trim($line)));
                $this->promptRace($client, $account, $startingRoom, $name);

                return;
            }

            $this->createCharacter($client, $account, $startingRoom, $name, $race);
        });
    }

    private function parseRace(string $input): ?Race
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim($input)));

        return Race::tryFrom($normalized);
    }

    private function createCharacter(Client $client, Account $account, Room $startingRoom, string $name, Race $race): void
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

        $client->send(new CharacterCreated($name));
        $client->send(new CharacterStats($character));
        $this->charactersCommand->onClientAction($client, '');
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
