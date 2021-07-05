<?php
class Select {
    private $options;
    private $selected;
    private $name;
    private $id;
    private $width;

    function __construct($name, $id = "", $width = 200){
        if($name == "")
            throw new Exception('You need to specify the $name parameter');
        $this->options = array();
        $this->selected = 0;        
        $this->name = $name;        
        $this->id = $id == ""? $name: $id;
        $this->width = $width;
    }

    public function addOption($name, $value = null, $isdisabled = false){
        $this->options[] = array(
            "name" => $name,
            "value" => $value == ""? count($this->options): $value,
            "disabled" => $isdisabled
        );
        return $this;
    }

    public function select($value) {
        $this->selected = $value;
        return $this;
    }

    public function render(){        
        if(substr($this->width, strlen($this->width) - 1, 1) != "%"){
            $this->width.="px";
        }
        $output = "<select name=\"".$this->name."\" style=\"width:".$this->width."\"";
        if($this->id != "")
            $output.= " id=\"".$this->id."\"";
        $output.= ">";
        foreach($this->options as $key => $option){
            $output.= "<option";
            if($option["value"] !== null)
                $output.= " value=\"".$option["value"]."\"";
            if($option["disabled"] === true)
                $output.= " disabled=\"disabled\"";
            if($this->selected == $option["value"])
                $output.= " selected=\"selected\"";
            $output.= ">";
            $output.= $option["name"];
            $output.= "</option>";
        }
        $output.= "</select>";
        return $output;
    }
}