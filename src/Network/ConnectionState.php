<?php

namespace App\Network;

enum ConnectionState: string
{
    case Connected = 'connected';
    case Authed = 'authed';
    case Ingame = 'ingame';
}
