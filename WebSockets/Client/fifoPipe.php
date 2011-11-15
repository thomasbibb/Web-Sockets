<?php

/**
 * Description of fifoPipe
 *
 * @author Thomas A. Bibb
 */
class fifoPipe {
    
    protected $fifoPath;
    
    
    public function __construct($fifoPath, $mode) {
        if (!file_exists($this->fifoPath = $fifoPath)) { 
            umask($mode); 
            posix_mkfifo($fifoPath, $mode); 
        } 
    }
      
    public function fifoPipeRead(){
        $fifo = fopen($this->fifoPath, 'r');
        return fread($fifo, 1024);
    }

    public function fifoPipeWrite($data) {
        $fifo = fopen($this->fifoPath, 'w');
        fwrite($fifo, $data); 
    }
}

?>
