<?php

class phpMQTT {
    private $socket;
    private $address;
    private $port;
    private $clientid;
    private $username;
    private $password;
    private $keepalive = 10;
    private $lastError = '';

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

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = @stream_socket_client(
            "ssl://{$this->address}:{$this->port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->lastError = "Erro conexão: $errstr ($errno)";
            return false;
        }

        stream_set_timeout($this->socket, $timeout);

        $protocol = "MQTT";
        $protocol_level = 4;

        $connectFlags = 0;
        if ($clean) $connectFlags |= 0x02;
        if ($this->username) $connectFlags |= 0x80;
        if ($this->password) $connectFlags |= 0x40;

        $variable = $this->str($protocol)
                  . chr($protocol_level)
                  . chr($connectFlags)
                  . chr($this->keepalive >> 8)
                  . chr($this->keepalive & 0xFF);

        $payload = $this->str($this->clientid);

        if ($this->username) $payload .= $this->str($this->username);
        if ($this->password) $payload .= $this->str($this->password);

        $packet = chr(0x10) . $this->len(strlen($variable) + strlen($payload)) . $variable . $payload;

        fwrite($this->socket, $packet);

        $response = fread($this->socket, 4);

        if (!$response || strlen($response) < 4) {
            $this->lastError = "Sem resposta do broker";
            return false;
        }

        if (ord($response[3]) !== 0) {
            $this->lastError = "Conexão recusada, código: " . ord($response[3]);
            return false;
        }

        return true;
    }

    public function publish($topic, $msg, $qos = 0, $retain = false) {
        $header = 0x30;
        if ($retain) $header |= 0x01;

        $packet = $this->str($topic) . $msg;
        $out = chr($header) . $this->len(strlen($packet)) . $packet;

        $written = @fwrite($this->socket, $out);
        if ($written === false) {
            $this->lastError = 'Publish write failed';
            return false;
        }
        return true;
    }

    public function close() {
        if ($this->socket) fclose($this->socket);
    }

    private function str($str) {
        return chr(strlen($str) >> 8) . chr(strlen($str) & 0xFF) . $str;
    }

    private function len($len) {
        $string = '';
        do {
            $digit = $len % 128;
            $len = intval($len / 128);
            if ($len > 0) $digit |= 0x80;
            $string .= chr($digit);
        } while ($len > 0);
        return $string;
    }

    // retorna a última mensagem de erro (útil para debug sem imprimir diretamente)
    public function getLastError() {
        return $this->lastError;
    }
}