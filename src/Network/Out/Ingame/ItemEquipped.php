<?php

namespace App\Network\Out\Ingame;

use App\Auth\Client;
use App\Entity\Enum\EquipmentSlot;
use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ItemEquipped implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
        private readonly EquipmentSlot $slot,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("You equip the %s (%s).\n", Ansi::item($this->name), $this->slot->value));
    }
}
