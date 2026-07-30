<?php

namespace App\Network\Out\Authed;

use App\Entity\Enum\Race;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ChooseRace implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output): void
    {
        $lines = ["Choose your character's race:"];
        foreach (Race::cases() as $race) {
            $lines[] = sprintf('  %s - %s', $race->label(), $this->describeBonuses($race));
        }

        $output->write(implode("\n", $lines) . "\n");
    }

    private function describeBonuses(Race $race): string
    {
        $bonuses = $race->abilityScoreBonuses();

        if (count($bonuses) === 6) {
            return sprintf('+%d to all six abilities', reset($bonuses));
        }

        $parts = [];
        foreach ($bonuses as $ability => $bonus) {
            $parts[] = sprintf('+%d %s', $bonus, ucfirst($ability));
        }

        return implode(', ', $parts);
    }
}
