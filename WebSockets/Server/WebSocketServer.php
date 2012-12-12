<?php

/**
 * WebSockets is a new HTML5 draft which enables you the developer
 * to push data in particular JSON to the remote user/browser.
 *
 * @author Thomas A. Bibb
 * @version 0.1 
 * 
 */
class WebSocketServer {
    
    protected $_server = null;
    protected $_socketRead = array();
    protected $_serverVerbose = null;
    protected $_connection = array();
    protected $_log = array();
    protected $_listener = null;
    protected $_config = array();
    protected $_fifoPipe;
    
    public static $verboseLevel;
    public static $prerequisite;
    public static $signals;
    
    const VERBOSE_NORMAL = 10;
    const VERBOSE_INFORMATIVE = 20;
    const VERBOSE_HIGH = 30;
    const SIGTERM = 15;
    const SIGHUP = 1;
    const SIGKILL = 9;
    
    public function __construct($port=8081, $address='127.0.0.1', $maxConn=20, 
            $verboseLevel=self::VERBOSE_NORMAL) {
        
        $this->prerequisite();
        $this->initVerboseLevels();

        if (!in_array($verboseLevel, self::$verboseLevel)) {
            throw new Exception(
                    'Unknown verbose level {'.$verboseLevel.'}'
            );
        }
        
        $this->_config = array(
            'port' => $port,
            'address' => $address,
            'maxConn' => $maxConn,
            'verboseLevel' => $verboseLevel,
            'fifoPath' => 'fifo.input'
        );
        
        $this->_serverVerbose = $verboseLevel;
    }
       
    public function __get($name) {
        if (!isset($this->_config[$name])) {
            throw new Exception (
                'Unknown config varibal {'.$name.'}'
            );
        }
        
        return $this->_config[$name];
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
                        
        if(!socket_set_option($this->_server, SOL_SOCKET, SO_REUSEADDR, 1)) {
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
        $this->setLog("Server socket : ".$this->_server, self::VERBOSE_INFORMATIVE);
        
        //Add socket to socketRead array
        $this->_socketRead[] = $this->_server;
        
        //Start the listener thread
        $listenerPid = pcntl_fork();  
        
        if ($listenerPid == -1) {
            throw new Exception(
                    'Unable to fork listener process'
            );
        } elseif ($listenerPid) {
            $this->_listener = $listenerPid;
            $this->setLog('Listening on {'.$this->address.':'.$this->port.'} with pid '. $listenerPid, self::VERBOSE_NORMAL);
            
        } else {
            while(true){
                              
              socket_select($this->_socketRead, $write=NULL, $except=NULL,NULL);
              
              foreach($this->_socketRead as $socket) {
                
                  if($socket == $this->_server) {
                    $client = socket_accept($this->_server);
                  
                  if($client <0) { 
                      console("socket_accept() failed"); 
                      continue; 
                  } else { 
                      $this->createConnection($client);
                  }
                } else {
                  $bytes = @socket_recv($this->_server,$buffer,2048,0);
                  
                  if($bytes==0) { 
                      $this->destoryConnection($socket); 
                  } else {
                    $user = getuserbysocket($socket);
                    
                    if(!$user->handshake){ 
                        dohandshake($user,$buffer); 
                    } else { 
                        process($user,$buffer); 
                    }
                  }
                }
                
              }
            }
        }
    } 
    
    /**
     * Stops the listener process and closes all open sockets 
     * by connection.
     * 
     * @param int $signal 
     * @todo introduce a more elegent way of destorying the
     * sockets array.
     */
    public function stopServer() {
        
        $this->initSignals();
        
        foreach ($this->_connection as $conn) {
            /* say goodbye to connection */
            socket_close($conn->getSocket());
        }
        
        posix_kill($this->_listener, self::SIGTERM);
        socket_close($this->_server);
        unset($this->sockets);
        
        $this->setLog("Server Stopped", self::VERBOSE_INFORMATIVE);
    }
    
    /**
     * Aids the correct creation of a connection thread
     * 
     * @param int $pid
     * @return WebSocketThread 
     */
    public function createConnection($socket) {
        
        $connPid = pcntl_fork();  
        
        if ($threadPid == -1) {
            throw new Exception(
                    'Unable to create new connection thread'
            );
        } elseif ($threadPid) {
            $this->_connection[$connPid] = new WebSocketThread($connPid, $socket);
            $this->setLog("New connection with pid {".$threadPid."}", self::VERBOSE_INFORMATIVE);
        } else {
            /* the actual thread */
        }
    }
    
    /**
     * Find a socket by a connection
     * 
     * @param type $socket
     * @return type 
     */
    public function findConnectionBySocket ($socket) {
        foreach ($this->_connection as $conn) {
            if ($conn->getSocket() == $socket) {
                return $conn;
            }
        }
        return false;
    }
    
    /** 
     * Destorys the connection, socket and it's the 
     * assocated process.
     * 
     * @param type $socket 
     */
    public function destoryConnection($socket) {
        $conn = $this->findConnectionBySocket($socket);
        unset($this->_connection[$conn->getPid()]);
        posix_kill($conn->getPid(), self::SIGTERM);
        socket_close($socket);
    }
            
    /**
     * Signal a particular thread
     * 
     * @param WebSocketThread $thread
     * @param int $signal 
     */
    public function signalConnection(WebSocketThread $thread, $signal) {       
        if (!in_array($signal, self::$signals)) {
            throw new Exception('Unknown process signal');
        }
        
        //Gracefully halt the server
        if ($signal===self::SIGTERM) {
            
        }
        
        posix_kill($thread->getPid(), $signal);
    }
    
    public function signalListnerThread($signal) {
        
        switch ($signal) {
            case self::SIGTERM:
                exit;
                break;
            case self::SIGKILL:
                exit;
                break;
            case self::SIGHUP:
                //do nothing
        }
        
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
     * @param int $verboseLevel
     * @return int msgId 
     */
    public function setLog($message, $verboseLevel=self::VERBOSE_NORMAL) {
        $this->_log[] = array(
            'timestamp'     => date('Y-m-d H:i:s'),
            'message'       => $message,
            'verboseLevel'  => $verboseLevel
        );
        
        if ($this->_serverVerbose <= $verboseLevel) {
            echo '['.date('Y-m-d H:i:s').'] '.$message."\n";
        }
        
        $i=0;
        return $i = count($this->_log) == 1 ? 0 : $i-1;  
    }
      
    /**
     * lazy load verboseLevels
     */
    public function initVerboseLevels() {
        if (self::$verboseLevel === null) {
            self::$verboseLevel = array(
                self::VERBOSE_NORMAL,
                self::VERBOSE_INFORMATIVE,
                self::VERBOSE_HIGH
            );
        }
    }
    
    /**
     * lazy load singals for processes
     */
    public function initSignals() {
        if (self::$signals === null) {
            self::$signals = array(
                self::SIGTERM,
                self::SIGHUP,
                self::SIGKILL
            );

            foreach (self::$signals as $sig) {
                pcntl_signal($sig, array(&$this,"signalListnerThread"));
            }
        }
    }
    
    /*
     * lazy load application prerequisites
     */
    public function initPrerequisite() {
        if (self::$prerequisite === null) {
            self::$prerequisite = array(
                'pcntl_fork',
                'socket_create',
                'socket_select',
                'socket_set_option',
            );
        }
    }
    
    /*
     * check prerequsits are installed
     */
    public function prerequisite() {
        $this->initPrerequisite();
        
        foreach (self::$prerequisite as $preReq) {
            if (!function_exists($preReq)) {
                throw new Exception(
                        $preReq.' function is not available, please install'
                );
            }
        }
    }
    
}


?>
