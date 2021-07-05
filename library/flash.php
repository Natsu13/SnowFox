<?php
/**
 * Updated to 1.1
 */
class Flash {
    private $key = "flash_messages";
    private $messages = [];
    private $cache = [];

    public static $OK = 1;
    public static $ERROR = 2;
    public static $WARNING = 3;
    public static $POPUP_OK = 11;
    public static $POPUP_ERROR = 12;
    public static $POPUP_WARNING = 13;

    public function __construct(){
        $this->messages = json_decode($_COOKIE[$this->key], true);        
        Bootstrap::$self->getContainer()->set('flash', $this);
    }

    public function push($message, $state = null) {
        $state_name = "ok";
        $state_popup = false;
        switch($state) {
            case Flash::$ERROR: 
                $state_name = "error_big"; break;
            case Flash::$WARNING: 
                $state_name = "warning"; break;
            case Flash::$POPUP_OK: 
                $state_popup = true; break;
            case Flash::$POPUP_ERROR: 
                $state_popup = true; $state_name = "error"; break;
            case Flash::$POPUP_WARNING: 
                $state_popup = true; $state_name = "warning"; break;
        }

        $this->cache[] = array("message" => $message, "state" => $state_name, "time" => time(), "isPopup" => $state_popup);        
    }

    public function get() {
        return $this->messages;
    }

    public function count() {
        return count($this->messages);
    }

    public function store() {       
        if(count($this->cache) > 0) {
            Cookies::set($this->key, json_encode($this->cache));
        }
    }

    /**
     * Flush the message buffer 
     */
    public function flush() {        
        if($_GET["__type"] == "page") {
            Cookies::delete($this->key); //Don't delete this when there is not a page render
        }
    }
}