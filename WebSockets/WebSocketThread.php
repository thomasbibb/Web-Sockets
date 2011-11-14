<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SocketThread
 *
 * @author thomasbibb
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
