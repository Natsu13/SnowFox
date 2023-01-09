<?php 
if($this->config->get("style.menu.top") != "hide"){
	$this->page->toptoolbar();
}
?>
<div class="topmenu" id="topmenu" <?php echo ($this->config->get("style.menu.top") == "hide"?"style=\"display:none;\"":""); ?>>
	<div>
		<div class="open" onclick="$('#topmenu').toggleClass('expanded');"><i class="fas fa-bars"></i></div>
		<a class="home" href="<?php echo Router::url(); ?>">		
			<?php echo $this->page->title; ?>
		</a>
		<ul class="menu">
			<?php $this->page->menu_draw("top_menu", array("noul" => true, "li_selected_class" => "active", "a_class" => "nav-link", "li_class" => "nav-item")); ?>
			<!--
			<?php 
				$user = User::current();
				$perm = User::permission($user["permission"]);
				if($user == NULL || $user["id"] == -1){
			?>
				<li class="nav-item"><a href="<?php echo Router::url()."login/"; ?>" class="nav-link">Přihlásit se</a></li>
			<?php }else{ ?>
				<li class="nav-item mobile-line"><a href="<?php echo Router::url()."login/"; ?>"><?php echo $user["nick"] ?></a></li>
				<?php if($perm["permission"]["admin"]==1){ ?>
					<li class="nav-item"><a href="<?php echo Router::url()."admin/"; ?>" class="nav-link">Administrace</a></li>				
				<?php } ?>		
				<li class="nav-item"><a href="<?php echo Router::url()."login/?logout"; ?>" class="nav-link">Odlásit se</a></li>				
			<?php } ?>
			-->
		</ul>
	</div>
</div>
<div class="background web-small" <?php echo ($this->config->get("style.menu.top") == "hide"?"style=\"display:none;\"":""); ?>></div>
<div class="background web" <?php echo ($this->config->get("style.menu.top") == "hide"?"style=\"display:none;\"":""); ?>></div>
<div class="big_header" <?php echo ($this->config->get("style.menu.top") == "hide"?"style=\"display:none;\"":""); ?>>	
	<div class="inside">
		<div class="title_holder">
			<div class="title" id="title0">
				<a href="<?php echo Router::url(); ?>">
					<?php if($this->config->get("pre-title") != ""){ ?>
					<i class="fas fa-arrow-left"></i> 
					<?php } ?>
					<?php echo ($this->config->get("pre-title") == "" ? $this->page->title: $this->config->get("pre-title")); ?>
				</a>
			</div>			
		</div>		
	</div>	
</div>
<div class="page <?php echo $this->router->_data["module"][0]; ?>">
	<div class="container">
		<?php $this->page->page_draw(); ?>
	</div>
</div>

<div id="notificatonsAlert"></div>
<script>
var _router_url = "<?php echo $this->router->url; ?>";
var lastshow = 1;
function cards_switch(e){
	var e = $(e);
	var i = e.data("switch");
	if(i == lastshow)
		return;
	
	var _last = lastshow;
	$("#title"+lastshow).css({left: 0});
	$("#title"+lastshow).animate({ opacity: 0, left: -50 }, 300, function(){ $("#title"+_last).hide(); });	
	$("#title"+i).css({opacity: 0, left: 50});
	$("#title"+i).show();	
	$("#title"+i).animate({ opacity: 1,left: 0 }, 300, function(){ });	

	$("#cards"+lastshow).css({left: 0, opacity: 1, top: 0});
	$("#cards"+lastshow).animate({ opacity: 0, left: -50 }, 300, function(){ $("#cards"+_last).hide(); });		
	$("#cards"+i).css({top: 0-$("#cards"+lastshow).outerHeight(true), left: 50, opacity: 0});
	$("#cards"+i).show();
	$("#cards"+i).animate({ opacity: 1,left: 0 }, 300, function(){ $("#cards"+lastshow).css({top: 0 });$(".cards_holder").prepend($("#cards"+lastshow)); });	

	lastshow = i;
}
$( window ).scroll(function() {
	if($(window).scrollTop() > 0){
		$(".topmenu").addClass("nottrans");
	}else{
		$(".topmenu").removeClass("nottrans");
	}
});
</script>