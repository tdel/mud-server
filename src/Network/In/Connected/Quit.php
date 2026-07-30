<?php

namespace App\Network\In\Connected;

use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Connected\Goodbye;
use App\Network\UserInterface;

final class Quit implements ActionInterface
{
    #[\Override]
    public function name(): string
    {
        return 'quit';
    }

    #[\Override]
    public function states(): array
    {
        return [ConnectionState::Connected];
    }

    #[\Override]
    public function onReceive(UserInterface $user, string $argument): void
    {
        $user->send(new Goodbye());
        $user->close();
    }
}
