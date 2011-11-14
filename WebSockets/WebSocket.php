<?php

/**
 * WebSockets is a new HTML5 draft which enables you the developer
 * to push data in particular JSON to the remote user/browser.
 *
 * @author Thomas A. Bibb
 * @version 0.1 
 * 
 */
class WebSocket {
    
    protected $_server = null;
    protected $_sockets = array();
    protected $_serverVerbose = null;
    protected $_threads = array();
    protected $_log = array();
    protected $_listener = null;
    protected $_config = array();
    
    public static $verboseLevel;
    public static $prerequsits;
    public static $signals;
    
    const VERBOSE_NORMAL = 10;
    const VERBOSE_INFORMATIVE = 20;
    const VERBOSE_HIGH = 30;
    const SIGTERM = 15;
    const SIGHUP = 1;
    const SIGKILL = 9;
    
    public function __construct($port=8081, $address='127.0.0.1', $maxConn=20, 
            $verboseLevel=self::VERBOSE_NORMAL) {
        
        $this->prerequsits();
        $this->initVerboseLevels();

        if (!in_array($verboseLevel, self::$verboseLevel)) {
            throw new Exception(
                    'Unknown verbose level {'.$verboseLevel.'}'
            );
        }
        
        $this->_config = array(
            'port' => $port,
            'address' => $address,
            'macConn' => $maxConn,
            'verboseLevel' => $verboseLevel
        );
        
        $this->_serverVerbose = $verboseLevel;
    }
       
    public function __get($name) {
        if (!isset($this->_config[$name])) {
            throw new Exception (
                'Unknown config varibal {'.$name.'}'
            );
        }
    }
    
    public function startServer() {
        
        /**
         * Create Socket
         */
        if(!$this->_server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new Exception(
                    'Unable to create socket'
            );
        }
                        
        if(!socket_option($this->_server, SOL_SOCKET, SO_REUSEADDR)) {
            throw new Exception(
                    'Unable to set socket options'
            );
        }
          
        if (!socket_bind($this->_server, $this->address, $this->port)) {
            throw new Exception(
                    'Unable to bind to {'.$this->address.':'.$this->port.'}'
            );
        }
        
        if (!socket_listen($this->_server, $this->maxConn)) {
            throw new Exception(
                    'Unable to listen on {'.$this->address.':'.$this->port.'}'
            );
        }
        
        $this->setLog("Server Started", self::VERBOSE_NORMAL);
        $this->setLog("Server socket  : ".$this->_server, self::VERBOSE_INFORMATIVE);
        
        //Start the listener thread
        $listenerPid = pcntl_fork();  
        
        if ($listenerPid == -1) {
            throw new Exception(
                    'Unable to fork listener process'
            );
        } elseif ($listenerPid) {
            $this->_listener = $listenerPid;
            $this->setLog("Listener processed started : ".$listenerPid, self::VERBOSE_INFORMATIVE);
            
        } else {
            while(true){
              $changed = $sockets;
              socket_select($changed,$write=NULL,$except=NULL,NULL);
              foreach($changed as $socket){
                if($socket==$master){
                  $client=socket_accept($master);
                  if($client<0){ console("socket_accept() failed"); continue; }
                  else{ connect($client); }
                }
                else{
                  $bytes = @socket_recv($socket,$buffer,2048,0);
                  if($bytes==0){ disconnect($socket); }
                  else{
                    $user = getuserbysocket($socket);
                    if(!$user->handshake){ dohandshake($user,$buffer); }
                    else{ process($user,$buffer); }
                  }
                }
              }
            }
        }
    }
        

    /**
     * Stops the listener process, the socket 
     * will remain binded. 
     * 
     * @param int $signal 
     */
    public function stopServer($signal=self::SIGTERM) {
        if (!in_array($signal, self::$signals)) {
            throw new Exception('Unknown signal');
        }
        
        posix_kill($this->_listener, $signal);
    }
    
    /**
     * Aids the correct creation of a thread
     * 
     * @param int $pid
     * @return WebSocketThread 
     */
    public function createThread($pid) {
        return $this->_threads[] = new WebSocketThread($pid);
    }
    
    
    /**
     * Signal a particular thread
     * 
     * @param WebSocketThread $thread
     * @param int $signal 
     */
    public function signalThread(WebSocketThread $thread, $signal) {
        if (!in_array($signal, self::$signals)) {
            throw new Exception('Unknown signal');
        }
        
        posix_kill($thread->getPid(), $signal);
    }

    /**
     * 
     * Debug/init fluff
     * 
     */
    
    /**
     * Returns all the log messages applicable to the verboseLevel
     * set, this method defaults to the highest possible verboseLevel.
     *
     * @param int $verboseLevel
     * @return mixed array or string 
     */
    public function getLog($verboseLevel=self::VERBOSE_HIGH) {
              
        if (!in_array($verboseLevel, self::$verboseLevel)) {
            throw new Exception(
                    'Unknown verbose level {'.$verboseLevel.'}'
            );
        }
        
        foreach ($this->_log as $message) {
            if ($message['verboseLevel'] <= $verboseLevel) {
                $messages[$message];
            }
        }
        
        return $messages;
    }
    
    /**
     * Returns a log message based upon its particular Id
     * 
     * @param int $msgId
     * @return array message
     */
    public function getLogMessage($msgId) {
        if (!isset($this->_log[$msgId])) {
            throw new Exception(
                'Unknown msgId {'.$msgId.'}'    
            );
        }
        
        return $this->_log[$msgId];
    }
    
    /**
     * Returns a msgId for the particular message 
     * and echos the message depending on the verbose level
     *  
     * @param string $message
     * @return int msgId 
     */
    public function setLog($message, $verboseLevel) {
        
        $this->_log[] = array(
            'timestamp'     => date('Y-m-d H:i:s'),
            'message'       => $message,
            'verboseLevel'  => $verboseLevel
        );
        
        echo '['.date('Y-m-d H:i:s').'] '.$message."\n";
        
        return $i = count($this->_log) == 1 ? 0 : $i--;  
    }
      
    /**
     * initialise verboseLevels
     */
    public function initVerboseLevels() {
        self::$verboseLevel = array(
            self::VERBOSE_NORMAL,
            self::VERBOSE_INFORMATIVE,
            self::VERBOSE_HIGH
        );
    }
    
    /**
     * initalise singals for processes
     */
    public function initSignals() {
        self::$signals = array(
            self::SIGTERM,
            self::SIGHUP,
            self::SIGKILL
        );
        
        foreach (self::$signals as $sig) {
            pcntl_signal($sig, "sig_handler");
        }
    }
    
    /*
     * initalise prerequsits
     */
    public function initPrerequsits() {
        self::$prerequsits = array(
            'pcntl_fork'
        );
    }
    
    /*
     * check prerequsits are installed
     */
    public function prerequsits() {
        $this->initPrerequsits();
        
        foreach (self::prerequisite as $preReq) {
            if (!function_exists($preReq)) {
                throw new Exception(
                        $preReq.' functions not available, please install'
                );
            }
        }
    }
    
}


?>
