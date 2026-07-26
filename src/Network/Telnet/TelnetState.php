<?php

namespace App\Network\Telnet;

enum TelnetState: string
{
    case Connected = 'connected';
    case Authed = 'authed';
    case Ingame = 'ingame';
}
