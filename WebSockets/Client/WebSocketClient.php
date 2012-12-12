<?php

/**
 * This class enables a developer to control the running Demon process and its 
 * child thread processes.  At present a pipe is required for communication
 * a administration socket may be added at a later date.
 *
 * @author Thomas A. Bibb
 */
class WebSocketClient {
    
    protected $_fifoPipe;
    
    public function __construct($pipePath) {
        $this->_fifoPipe = new fifoPipe($pipePath, 0600);
    }
        
    public function stopServer() {
        $data['server']['state'] = false;
        $this->_fifoPipe->fifoPipeWrite(json_encode($data));
    }
    
    public function getLog() {
        $data['request']['method'] = 'getLog';
    }

}

?>
