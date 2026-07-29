<?php

namespace App\Network\In\Connected;

use App\Auth\Client;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\Out\Connected\Goodbye;

final class Quit extends AbstractClientAction
{
    public function name(): string
    {
        return 'quit';
    }

    public function states(): array
    {
        return [ConnectionState::Connected];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $client->send(new Goodbye());
        $client->close();
    }
}
