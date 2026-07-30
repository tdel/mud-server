<?php

namespace App\Network\Out\Ingame;

use App\Entity\Character;
use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterStats implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly Character $character,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $c = $this->character;

        $output->write(sprintf(
            "== %s ==\n%s: %s  %s: %s\n%s: %d  %s: %d  %s: %d\n%s: %d  %s: %d  %s: %d\n",
            Ansi::character($c->name),
            Ansi::health('Santé'),
            Ansi::health("{$c->currentHealth}/{$c->maxHealth}"),
            Ansi::mana('Mana'),
            Ansi::mana("{$c->currentMana}/{$c->maxMana}"),
            Ansi::stat('Force'),
            $c->strength,
            Ansi::stat('Dextérité'),
            $c->dexterity,
            Ansi::stat('Constitution'),
            $c->constitution,
            Ansi::stat('Intelligence'),
            $c->intelligence,
            Ansi::stat('Sagesse'),
            $c->wisdom,
            Ansi::stat('Charisme'),
            $c->charisma,
        ));
    }
}
