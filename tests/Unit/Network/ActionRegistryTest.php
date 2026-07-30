<?php

namespace App\Tests\Unit\Network;

use App\Network\ActionRegistry;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\UserInterface;
use PHPUnit\Framework\TestCase;

final class ActionRegistryTest extends TestCase
{
    public function testFindResolvesByStateAndName(): void
    {
        $look = $this->fakeAction('look', [ConnectionState::Ingame]);
        $registry = new ActionRegistry([$look]);

        self::assertSame($look, $registry->find(ConnectionState::Ingame, 'look'));
    }

    public function testFindReturnsNullForUnknownName(): void
    {
        $registry = new ActionRegistry([$this->fakeAction('look', [ConnectionState::Ingame])]);

        self::assertNull($registry->find(ConnectionState::Ingame, 'unknown'));
    }

    public function testFindReturnsNullWhenActionNotValidForState(): void
    {
        $registry = new ActionRegistry([$this->fakeAction('look', [ConnectionState::Ingame])]);

        self::assertNull($registry->find(ConnectionState::Connected, 'look'));
    }

    public function testActionRegisteredForMultipleStatesIsFoundInEach(): void
    {
        $quit = $this->fakeAction('quit', [ConnectionState::Connected, ConnectionState::Authed, ConnectionState::Ingame]);
        $registry = new ActionRegistry([$quit]);

        self::assertSame($quit, $registry->find(ConnectionState::Connected, 'quit'));
        self::assertSame($quit, $registry->find(ConnectionState::Authed, 'quit'));
        self::assertSame($quit, $registry->find(ConnectionState::Ingame, 'quit'));
    }

    /**
     * @param list<ConnectionState> $states
     */
    private function fakeAction(string $name, array $states): ActionInterface
    {
        return new class($name, $states) implements ActionInterface {
            public function __construct(
                private readonly string $actionName,
                private readonly array $actionStates,
            ) {
            }

            public function name(): string
            {
                return $this->actionName;
            }

            public function states(): array
            {
                return $this->actionStates;
            }

            public function onReceive(UserInterface $user, string $argument): void
            {
            }
        };
    }
}
