<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg,true);
        $toMod = $msg["toMod"];
        $toOneClient = $msg["toOneClient"];
        $msg["from"] = $this->getDynamicName($from);
        $msg = json_encode($msg);
        if(!$toMod){ 
            if(!$toOneClient){
                foreach ($this->clients as $client) {
                    if ($from !== $client) {
                        $client->send($msg);
                    }
                }
            }else{
                foreach ($this->clients as $client) {
                    $dynamic_name = $this->getDynamicName($client);
                    if ($dynamic_name == $toOneClient) {
                        $client->send($msg);
                    }
                }
            }
        }else{
            foreach ($this->clients as $client) {
                $dynamic_name = $this->getDynamicName($client);
                if ($dynamic_name == "MODGODCOMMANDER") {
                    $client->send($msg);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $dynamic_name = $this->getDynamicName($conn);
        $this->updateDatabaseOnDisconnect($dynamic_name);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Function to get the dynamic name from the client
    private function getDynamicName(ConnectionInterface $conn){
        $headers = $conn->httpRequest->getHeaders();
        $cookies = [];
        foreach ($headers as $header => $values) {
            if (strtolower($header) === 'cookie') {
                foreach ($values as $cookie) {
                    $cookiePairs = explode(';', $cookie);
                    foreach ($cookiePairs as $pair) {
                        $parts = explode('=', $pair, 2);
                        $cookies[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : null;
                    }
                }
            }
        }
        $dynamic_name = isset($cookies['dynamic_name']) ? $cookies['dynamic_name'] : null;
        return $dynamic_name;
    }
    // Function to update the database on user disconnect
    private function updateDatabaseOnDisconnect($dynamic_name) {
        echo "Disconnected.\n";
    }
}