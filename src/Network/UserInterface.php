<?php

namespace App\Network;

use App\Entity\Account;
use App\Game\PlayerInstance;

interface UserInterface
{

    public function awaitLine(\Closure $handler): void;

    public function account(): Account;

    public function player(): PlayerInstance;

    public function attachPlayer(PlayerInstance $player): void;

    public function state(): ConnectionState;

    public function setState(ConnectionState $state): void;

    public function attachAccount(Account $account): void;

    public function send(OutputMessageInterface $message): void;

    public function promptMasked(string $prompt, \Closure $onLine): void; // Should be agnostic !

    public function close(): void;
}