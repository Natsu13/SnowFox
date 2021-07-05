<?php
/**
 * Name: Plugin manager
 * Description: Allow you to manage plugins in your admin
 * Version: 1.0
 * Author: Natsu
 * Author-Web: http://natsu.cz/
 * Code: plugin
 */

$this->hook_register("init.setting", "plugin_init", -10);
//#not:release
$this->hook_register("page.plugin.init.setting", "plugin_init_setting", -10);
//#endnot
$this->hook_register("page.plugin", "plugin_page_draw", 0);
$this->hook_register("page.plugin.init.template", "plugin_init_template", -10);
$this->hook_register("init.setting", "plugin_template_set", 0);
$this->hook_register("admin.icons", "plugin_admin_icons", 0);
$this->hook_register("init.permissions", "plugin_perms", 0);

function plugin_admin_icons($t, &$output) {
	$output["plugin"] = array("module" => "plugin", "url" => "plugins/", "icon" => "fas fa-plug", "text" => "Plugins", "showMobile" => false, "file" => "plugin.admin.php");
}

function plugin_perms(&$perms){
	$perms[] = "plugin";
	//#not:release
	$perms[] = "plugin_manager";
	//#endnot
}

function plugin_template_set($t, &$template) {    
}
 
function plugin_init_template($t){
	//$t->root->page->add_script(Router::url()."modules/prizes/script/app.js");
	$t->root->config->set("style", "eshop");
}

function plugin_init($t){
	$t->router->add("plugin[/<action=show>]", "module=plugin&action=<action>");
	$t->router->add("plugin[/<action=show>][/<subact=none>]", "module=plugin&action=<action>&subact=<subact>");
}

//#not:release
function plugin_init_setting($t){
	$t->root->config->set("pre-title","Plugins");
}
//#endnot

function plugin_page_draw($t, &$output){
	//Controller
}

function getUpdateServer(){
	return Config::getS("update-server", "http://localhost/www/SnowLeopard/plugin/");
}