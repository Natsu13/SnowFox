<?php

class Container {

    private $services = [];
    private $aliases = [];
    private $loading = [];
    private $serviceDefinitions = [];

    public function __construct() {
        $this->services['container'] = $this;
    }

    public function register($id, $definition) {
        $this->serviceDefinitions[$id] = $definition;
    }

    public function get($id) {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if(isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (isset($this->loading[$id])) {
            throw new Exception("ServiceCircularReferenceException");
        }

        $definition = isset($this->serviceDefinitions[$id]) ? $this->serviceDefinitions[$id] : NULL;

        if($definition == NULL) {
            throw new Exception("ServiceNotFoundException: ".$id);
        }

        if (isset($definition[0])) {
            $definition = unserialize($definition);
        }

        $this->loading[$id] = TRUE;

        try {
            $service = $this->createService($definition, $id);
        }
        catch (\Exception $e) {
            unset($this->loading[$id]);
            unset($this->services[$id]);
        
            throw $e;
        }
      
        unset($this->loading[$id]);
      
        return $service;
    }

    public function set($id, $service, $alias = null) {
        $this->aliases[$alias == null? get_class($service): $alias] = $id;
        $this->services[$id] = $service;
    }

    public function has($id) {
        return isset($this->aliases[$id]) || isset($this->services[$id]) || isset($this->serviceDefinitions[$id]);
    }

    public function initialized($id) {
        if (isset($this->aliases[$id])) {
          $id = $this->aliases[$id];
        }
    
        return isset($this->services[$id]) || array_key_exists($id, $this->services);
    }

    public function createService($definition, $id){
        if(isset($definition["file"])){
            require_once _ROOT_DIR . $definition["file"];
        }

        $arguments = [];
        if(isset($definition["arguments"])) {
            $arguments = $this->resolveParameters($definition["arguments"]);
        }

        if(isset($definition["factory"])) {
            $service = call_user_func_array($factory, $arguments);
        }else{
            $r = new \ReflectionClass($definition['class']);
            $service = $r->newInstanceArgs($arguments);
        }

        if (!isset($definition['shared']) || $definition['shared'] !== FALSE) {
            $this->services[$id] = $service;
        }

        return $service;
    }

    public function resolveParameters($arguments) {
        foreach($arguments as $n => $arg) {
            $id = $arg;
            
            if(isset($this->aliases[$id])) {
                $id = $this->aliases[$id];
            }

            if (isset($this->services[$id])) {
                $arguments[$n] = $this->services[$id];
                continue;
            }

            if(isset($this->serviceDefinitions[$id])) {
                $arguments[$n] = $this->get($id);
                continue;
            }
        }

        return $arguments;
    }
}