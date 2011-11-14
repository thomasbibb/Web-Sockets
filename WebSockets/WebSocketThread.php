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
    
    
    public function __construct($pid) {
        $this->pid = $pid;
        $this->threadId = uniqid();
    }
    
    
    
    
}

?>
