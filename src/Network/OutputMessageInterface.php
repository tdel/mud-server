<?php

namespace App\Network;

/**
 * Marker for anything that can be sent to a Player. Transport-agnostic on
 * purpose — how a given message renders for a specific transport (telnet,
 * a future HTTP/websocket client, ...) is defined by a transport-specific
 * sub-interface (e.g. App\Network\Telnet\OutputTelnetMessageInterface).
 */
interface OutputMessageInterface
{
}
