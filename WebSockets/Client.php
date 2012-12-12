<?php

//Setup Enviroment
//error_reporting(0);

function __autoload($class) {
    require_once 'Client/'.$class.'.php';
}

/**
 * Simple use of the API to stop the WebSocket Server
 */

$WSClient = new WebSocketClient('fifo.input');
$WSClient->stopServer();

?>
