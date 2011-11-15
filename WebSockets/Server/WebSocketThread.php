<?php


/**
 * This class is a fundimental part of the WebSockets Server as each request
 * requires is own indivudual thread so that its request can be processed 
 * in parrallel with the listener thread.
 *
 * @author Thomas A. Bibb
 * @version 0.1 
 * 
 */

class WebSocketThread {
   
    protected $threadId;
    protected $pid;
    protected $socket;
    
    
    public function __construct($pid, $socket) {
        $this->pid = $pid;
        $this->threadId = uniqid();
        $this->socket = $socket;
    }
    
    public function getSocket () {
        return $this->socket;
    }
    
    
}

?>
