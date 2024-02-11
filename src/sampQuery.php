<?php

namespace jhonatanjavierdev\SampQuery;

class SampQuery
{
    private $socket;
    private $server;
    private $port;

    public function __construct($server, $port)
    {
        $this->server = [$server, $port];
        $this->socket = $this->initializeSocket();

        if (!$this->socket) {
            $this->server['online'] = false;
            return;
        }

        $this->sendInitialPacket();
        $this->server['online'] = $this->verifyServer();
    }

    public function __destruct()
    {
        @fclose($this->socket);
    }

    public function isOnline()
    {
        return $this->server['online'] ?? false;
    }

    public function getInfo()
    {
        $this->sendPacket('i');
        $response = $this->readSocket(11);

        $details['password'] = (int) ord($response[0]);
        $details['players'] = (int) $this->toInteger(substr($response, 1, 2));
        $details['maxplayers'] = (int) $this->toInteger(substr($response, 3, 2));

        $details['hostname'] = $this->readString();
        $details['gamemode'] = $this->readString();
        $details['mapname'] = $this->readString();

        return $details;
    }

    public function getBasicPlayers()
    {
        $this->sendPacket('c');
        $response = $this->readSocket(11);

        $playerCount = ord($response[0]);
        $details = [];

        for ($i = 0; $i < $playerCount; ++$i) {
            $nicknameLength = ord($this->readSocket(1));
            $details[] = [
                'nickname' => $this->readString($nicknameLength),
                'score' => (int) $this->toInteger($this->readSocket(4)),
            ];
        }

        return $details;
    }

    public function getDetailedPlayers()
    {
        $this->sendPacket('d');
        $response = $this->readSocket(11);

        $playerCount = ord($response[0]);
        $details = [];

        for ($i = 0; $i < $playerCount; ++$i) {
            $details[] = [
                'playerid' => (int) ord($this->readSocket(1)),
                'nickname' => $this->readString(ord($this->readSocket(1))),
                'score' => (int) $this->toInteger($this->readSocket(4)),
                'ping' => (int) $this->toInteger($this->readSocket(4)),
            ];
        }

        return $details;
    }

    public function getRules()
    {
        $this->sendPacket('r');
        $response = $this->readSocket(11);

        $ruleCount = ord($response[0]);
        $details = [];

        for ($i = 0; $i < $ruleCount; ++$i) {
            $nameLength = ord($this->readSocket(1));
            $ruleName = $this->readString($nameLength);

            $valueLength = ord($this->readSocket(1));
            $details[$ruleName] = $this->readString($valueLength);
        }

        return $details;
    }

    private function initializeSocket()
    {
        $socket = fsockopen('udp://' . $this->server[0], $this->server[1], $error, $message, 2);
        socket_set_timeout($socket, 2);

        return $socket;
    }

    private function sendInitialPacket()
    {
        $ipParts = array_map('intval', explode('.', $this->server[0]));
        $packet = 'SAMP' . implode('', array_map('chr', array_merge($ipParts, [$this->server[1] & 0xFF, $this->server[1] >> 8 & 0xFF]))) . 'p4150';
        fwrite($this->socket, $packet);
    }
    
    private function verifyServer()
    {
        if ($this->readSocket(10) && $this->readSocket(5) == 'p4150') {
            return true;
        }

        return false;
    }

    private function sendPacket($payload)
    {
        $packet = 'SAMP';
        $packet .= implode('', array_map('chr', array_merge(explode('.', $this->server[0]), [$this->server[1] & 0xFF, $this->server[1] >> 8 & 0xFF])));
        $packet .= $payload;

        fwrite($this->socket, $packet);
    }

    private function readSocket($length)
    {
        return fread($this->socket, $length);
    }

    private function readString($length = 4)
    {
        return (string) fread($this->socket, (int) ord($this->readSocket($length)));
    }

    private function toInteger($data)
    {
        if ($data === "") {
            return null;
        }

        $integer = 0;
        $integer += (ord($data[0]));

        if (isset($data[1])) {
            $integer += (ord($data[1]) << 8);
        }

        if (isset($data[2])) {
            $integer += (ord($data[2]) << 16);
        }

        if (isset($data[3])) {
            $integer += (ord($data[3]) << 24);
        }

        if ($integer >= 4294967294) {
            $integer -= 4294967296;
        }

        return $integer;
    }
}
