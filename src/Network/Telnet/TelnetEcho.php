<?php

namespace App\Network\Telnet;

/**
 * Raw IAC byte sequences to actively negotiate client-side echo, used to
 * mask password entry. Kept separate from IacFilter, which strips inbound
 * IAC noise but explicitly performs no server-side option negotiation.
 */
final class TelnetEcho
{
    public const string OFF = "\xFF\xFB\x01"; // IAC WILL ECHO
    public const string ON = "\xFF\xFC\x01"; // IAC WONT ECHO
}
