<?php

namespace App\Tests\Unit\Network;

use App\Network\ActionDispatcher;
use App\Network\ActionRegistry;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\ActionNotFound;
use App\Network\UserInterface;
use App\Tests\Support\InMemoryUser;
use PHPUnit\Framework\TestCase;

final class ActionDispatcherTest extends TestCase
{
    public function testDispatchInvokesTheMatchingAction(): void
    {
        $action = new class implements ActionInterface {
            /** @var list<string> */
            public array $received = [];

            public function name(): string
            {
                return 'say';
            }

            public function states(): array
            {
                return [ConnectionState::Ingame];
            }

            public function onReceive(UserInterface $user, string $argument): void
            {
                $this->received[] = $argument;
            }
        };

        $dispatcher = new ActionDispatcher(new ActionRegistry([$action]));
        $user = new InMemoryUser();
        $user->setState(ConnectionState::Ingame);

        $dispatcher->dispatch($user, 'say', 'hello world');

        self::assertSame(['hello world'], $action->received);
        self::assertSame([], $user->sent);
    }

    public function testDispatchSendsActionNotFoundForUnknownAction(): void
    {
        $dispatcher = new ActionDispatcher(new ActionRegistry([]));
        $user = new InMemoryUser();

        $dispatcher->dispatch($user, 'nonexistent', '');

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }

    public function testDispatchSendsActionNotFoundWhenActionInvalidForCurrentState(): void
    {
        $action = new class implements ActionInterface {
            public function name(): string
            {
                return 'look';
            }

            public function states(): array
            {
                return [ConnectionState::Ingame];
            }

            public function onReceive(UserInterface $user, string $argument): void
            {
                \PHPUnit\Framework\Assert::fail('onReceive() should not be called for a state the action does not support.');
            }
        };

        $dispatcher = new ActionDispatcher(new ActionRegistry([$action]));
        $user = new InMemoryUser();
        $user->setState(ConnectionState::Connected);

        $dispatcher->dispatch($user, 'look', '');

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }
}
