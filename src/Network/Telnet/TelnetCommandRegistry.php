<?php

namespace App\Network\Telnet;

use App\Network\In\TelnetCommandInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class TelnetCommandRegistry
{
    /** @var array<string, array<string, TelnetCommandInterface>>|null */
    private ?array $commandsByStateAndName = null;

    /**
     * @param iterable<TelnetCommandInterface> $commands
     */
    public function __construct(
        #[AutowireIterator('app.telnet_command')]
        private readonly iterable $commands,
    ) {
    }

    public function find(TelnetState $state, string $name): ?TelnetCommandInterface
    {
        return $this->commandsByStateAndName()[$state->value][$name] ?? null;
    }

    /**
     * @return array<string, array<string, TelnetCommandInterface>>
     */
    private function commandsByStateAndName(): array
    {
        if ($this->commandsByStateAndName !== null) {
            return $this->commandsByStateAndName;
        }

        $commandsByStateAndName = [];
        foreach ($this->commands as $command) {
            foreach ($command->states() as $state) {
                $commandsByStateAndName[$state->value][$command->name()] = $command;
            }
        }

        return $this->commandsByStateAndName = $commandsByStateAndName;
    }
}
