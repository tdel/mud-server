<?php

namespace App\Network\In\Connected;

use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Goodbye;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;

final class Quit implements TelnetCommandInterface
{
    public function name(): string
    {
        return 'quit';
    }

    public function states(): array
    {
        return [TelnetState::Connected];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $session->client()->send(new Goodbye());
        $session->close();
    }
}
