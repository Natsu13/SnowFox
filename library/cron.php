<?php

class Cron {

    public function __construct($core, $debuger, $module_manager) {
        $this->core = $core;
        $this->debuger = $debuger;
        $this->module_manager = $module_manager;
    }

    private function isEnabled() {
        return $this->core->config->getD("cron-enable", "0") == 1;
    }

    public function getHash() {
        return sha1(User::get($this->core->config->getD("superuser", 1))["heslo"]);
    }

    public function execute(){
        if(!$this->isEnabled()){
            echo "Cron is disabled";
            exit;    
        }
        echo "Executing cron...<br/>";
        Utilities::addHistory("cron", "log", "start", array("debug" => $this->debuger->getFullToken()), "Cron started");
        
        $plugin = $this->module_manager->hook_call("cron.register", null, array(), false, true, true);            
        $jobs = $plugin["output"];
            
        foreach($jobs as $job) {                     
            $lastRun = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "cron_log_".$job["call"], " ORDER BY date DESC LIMIT 1")->fetch();

            if($lastRun != null && strtotime($job["every"], intval($lastRun["date"])) > time()) {
                continue;
            }

            try{
                echo "Executing cron job ".$job["name"]."...<br/>";
                    
                //output of the job can be array(array("time" => time(), "text" => "some log line")); soo it will be interpreted as logs
                $cronJob = $this->module_manager->hook_call("cron.job.".$job["call"], array("container" => $this->core->getContainer()), array("state" => true, "output" => array()));
                $cronResult = $cronJob["output"];

                Utilities::addHistory("cron_log", $job["call"], "run", array("state" => $cronResult["state"], "output" => $cronResult["output"]), "Cron job ".$job["call"]." runed");
            } catch(Exception $e) {
                Utilities::addHistory("cron_log", $job["call"], "run", array("state" => false, "output" => $e), "Cron job ".$job["call"]." ended with exception");
            }
        }

        echo "Cron ended...";
        Utilities::addHistory("cron", "log", "end", null, "Cron ended");   
    }
}