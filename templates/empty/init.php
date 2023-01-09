<?php
//Here is defined include on head and other function...
$this->page->add_style($this->router->url . "templates/" . $this->template . "/css/bootstrap.min.css");
$this->page->add_style($this->router->url . "templates/" . $this->template . "/style.css");
$this->page->add_script($this->router->url . "templates/" . $this->template . "/js/bootstrap.min.js", false);
$this->page->add_script($this->router->url . "templates/" . $this->template . "/javascript.js", false);

$this->page->add_script($this->router->url . "include/tinymce/tinymce.min.js", false);
//$this->page->add_script("//cdn.tinymce.com/4/tinymce.min.js", false);
$this->page->add_style("https://fonts.googleapis.com/css?family=Dosis:400,700|PT+Mono", "");

$this->config->set("usable_menu", "", true);
$this->config->set("style.enable.custommenu", false, true);
$this->config->set("browser-tab-color", "#343a40", true);