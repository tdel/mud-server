<?php

namespace App\Network;

use App\Network\In\ActionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActionRegistry
{
    /** @var array<string, array<string, ActionInterface>>|null */
    private ?array $actionsByStateAndName = null;

    /**
     * @param iterable<ActionInterface> $actions
     */
    public function __construct(
        #[AutowireIterator('app.action')]
        private readonly iterable $actions,
    ) {
    }

    public function find(ConnectionState $state, string $name): ?ActionInterface
    {
        return $this->actionsByStateAndName()[$state->value][$name] ?? null;
    }

    /**
     * Forces the lazy action map to build now. Call once at server boot so
     * a wiring problem (e.g. two actions claiming the same name/state)
     * surfaces immediately instead of on some player's first command.
     */
    public function warmUp(): void
    {
        $this->actionsByStateAndName();
    }

    /**
     * @return array<string, array<string, ActionInterface>>
     */
    private function actionsByStateAndName(): array
    {
        if ($this->actionsByStateAndName !== null) {
            return $this->actionsByStateAndName;
        }

        $actionsByStateAndName = [];
        foreach ($this->actions as $action) {
            foreach ($action->states() as $state) {
                $actionsByStateAndName[$state->value][$action->name()] = $action;
            }
        }

        return $this->actionsByStateAndName = $actionsByStateAndName;
    }
}
