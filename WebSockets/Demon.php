<?php

//Setup Enviroment
//error_reporting(0);

function __autoload($class) {
    require_once 'Server/'.$class.'.php';
}

$WSServer = new WebSocketServer(8080, '127.0.0.1', 20, WebSocketServer::VERBOSE_NORMAL);
$WSServer->startServer();
    
?>
