<?php
$this->hook_register("init.setting", "adminv3_init", -10);
$this->hook_register("page.adminv3.init.setting", "adminv3_init_setting", -10);
$this->hook_register("page.adminv3.init.template", "adminv3_init_template", 0);
$this->hook_register("page.global.init", "admin_init_global", 0);

function admin_init_global($t) {
	$t->root->page->add_script("https://www.google.com/recaptcha/api.js");	
}

function adminv3_init($t){	
	$t->router->add("adminv3-<action>", "module=adminv3&action=<action>&adminModule=info&adminAction=index");
	$t->router->add("adminv3[/<admin_module=info>]", "module=adminv3&action=index&adminModule=<admin_module>&adminAction=index&id=1");
    $t->router->add("adminv3[/<admin_module=info>][/<adminAction=index>]", "module=adminv3&action=index&adminModule=<admin_module>&adminAction=<adminAction>&id=1");
	$t->router->add("adminv3[/<admin_module=info>][/<adminAction=index>][/<id=1>]", "module=adminv3&action=index&adminModule=<admin_module>&adminAction=<adminAction>&id=<adminId>");
	$t->router->add("adminv3/login", "module=adminv3&action=login");
}

function adminv3_init_setting($t){
	$t->root->config->set("pre-title",t("Administration"));
	$t->root->config->set("style.menu.left", "hide");
	$t->root->config->set("style.menu.top", "hide");
	$t->root->config->set("style.header", "hide");
	$t->root->config->set("style", "empty");

	$t->root->page->add_style("https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.theme.min.css");

	/* <icons> 
	 * the first css file is for local hosting preconfigured to fill: 0..1; weight: 400; grade: 0; optical_size: 48 
	 * you can go to the css and uncoment the second font-face src to use full configurable symbols but it hase 2mb soo uhh bad!
	 * or here you can just pick any setting you want opsz,wght,FILL,GRAD@48,400,0..1,0
	 * opsz = 48,wght = 400,FILL = 0..1,GRAD = 0
	 * this settings still alow you to change the fill settings of the symbol
	 * https://developers.google.com/fonts/docs/material_icons
	 */
	//$t->root->page->add_style(Router::url()."modules/adminv3/style/font-icon.css");
	$t->root->page->add_style("https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0..1,0");	
	/* </icons> */

	$t->root->page->add_style("https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");		
	$t->root->page->add_script("https://api.mapy.cz/loader.js");
	$t->root->page->add_style(Router::url()."modules/adminv3/script/jquery.datetimepicker.css", false);
	$t->root->page->add_script(Router::url()."modules/adminv3/script/jquery.datetimepicker.full.min.js", false);
	$t->root->page->add_script(Router::url()."modules/adminv3/script.js", false);
}

function adminv3_init_template($t){
	$t->root->page->add_style(Router::url()."modules/adminv3/style.css");
	$t->root->config->set("browser-tab-color", "#484848", true);	
}