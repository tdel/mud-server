<?php

namespace App\Network\In;

use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;

interface TelnetCommandInterface
{
    /**
     * The command word a player types to trigger this command (e.g. "look").
     */
    public function name(): string;

    /**
     * @return list<TelnetState> the states this command is usable in
     */
    public function states(): array;

    public function execute(TelnetSession $session, string $argument): void;
}
