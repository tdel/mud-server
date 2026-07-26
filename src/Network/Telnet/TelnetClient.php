<?php

namespace App\Network\Telnet;

use App\Auth\Client;
use App\Network\OutputMessageInterface;

final class TelnetClient extends Client
{
    public function __construct(
        private readonly TelnetSession $session,
    ) {
    }

    public function send(OutputMessageInterface $message): void
    {
        if (!$message instanceof OutputTelnetMessageInterface) {
            throw new \LogicException(sprintf('%s cannot be sent over telnet.', $message::class));
        }

        $this->session->send($message, $this);
    }
}
