<?php

class phpMQTT {
    public $socket;
    public $msgid = 1;
    private $keepalive = 10;
    private $address;
    private $port;
    private $clientid;
    private $username;
    private $password;

    public function __construct($host, $port, $clientid) {
        $this->address = $host;
        $this->port = $port;
        $this->clientid = $clientid;
    }

    public function setCredentials($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function connect($clean = true, $timeout = 10) {

        $this->socket = stream_socket_client(
            "ssl://{$this->address}:{$this->port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) return false;

        stream_set_timeout($this->socket, $timeout);

        $protocol = "MQTT";
        $protocol_level = 4;

        $connectFlags = 0;
        if ($clean) $connectFlags |= 0x02;
        if ($this->username) $connectFlags |= 0x80;
        if ($this->password) $connectFlags |= 0x40;

        $variable = $this->str_with_length($protocol)
                  . chr($protocol_level)
                  . chr($connectFlags)
                  . chr($this->keepalive >> 8)
                  . chr($this->keepalive & 0xFF);

        $payload = $this->str_with_length($this->clientid);

        if ($this->username) {
            $payload .= $this->str_with_length($this->username);
        }

        if ($this->password) {
            $payload .= $this->str_with_length($this->password);
        }

        $packet = chr(0x10) . $this->encodeLength(strlen($variable) + strlen($payload));
        $packet .= $variable . $payload;

        fwrite($this->socket, $packet);

        $response = fread($this->socket, 4);

        if (!$response || strlen($response) < 4) return false;

        return ord($response[3]) === 0;
    }

    public function publish($topic, $content, $qos = 0, $retain = false) {

        $header = 0x30;
        if ($retain) $header |= 0x01;

        $packet = $this->str_with_length($topic) . $content;

        $out = chr($header) . $this->encodeLength(strlen($packet)) . $packet;

        return fwrite($this->socket, $out) !== false;
    }

    public function close() {
        if ($this->socket) fclose($this->socket);
    }

    private function str_with_length($str) {
        return chr(strlen($str) >> 8) . chr(strlen($str) & 0xFF) . $str;
    }

    private function encodeLength($len) {
        $encoded = '';
        do {
            $digit = $len % 128;
            $len = intval($len / 128);
            if ($len > 0) $digit |= 0x80;
            $encoded .= chr($digit);
        } while ($len > 0);
        return $encoded;
    }
}