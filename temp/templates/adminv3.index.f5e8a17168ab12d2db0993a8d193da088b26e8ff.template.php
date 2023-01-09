<link  rel="preconnect"  href="https://fonts.googleapis.com"><link  rel="preconnect"  href="https://fonts.gstatic.com"  crossorigin><div  class="admin"><div  class="admin-title"><div  class="admin-title-nofitication notification-success"  style="display:none;"><div  class="notification-progress"></div> <div  class="text">Hello world</div> </div> <div  class="left-side"><div  class="menu-bar"  onclick="manager.toggleLeftMenu();return false;"><span></span><span></span><span></span></div> <div  class="text"> AdminV3
                <div  class="subtitle">powered by <a  href="https://natsu.cz/about/"  target="_blank">SnowFox</a> </div> </div> </div> <div  class="middle-side"><div  class="search-widget"><div  class="search-input"><div  class="icon"></div> <input  type="text"  placeholder="<?php echo t('Search'); ?>" /></div> </div> </div> <div  class="right-side"><ul><li  class="icon"><a  href="#"><i  class="far fa-bell"></i> </a> </li> <li><a  href="#"><img  class="avatar"  src="<?php echo Router::url(); ?>upload/avatars/<?php echo $model['user']['avatar']; ?>" /></a> </li></ul></div> </div> <div  class="admin-content"><div  class="left-menu"><ul  class="admin-left-menu"> <?php foreach($model["icons"] as $key => $icon) { ?> <?php $className = $key==$model["module"]?"selected":""; ?> <?php if($key == "info") { ?><?php $keyMain = ""; ?><?php } else { ?><?php $keyMain = $key;; ?><?php } ?> <?php $hasSubMenu = count($icon["menu"]) > 0; ?><li  class="<?php if($hasSubMenu) { ?>has-submenu closed <?php } ?><?php echo $className; ?>"><div><a  href="<?php echo Router::url(); ?>adminv3/<?php echo $keyMain; ?>"  onclick="return manager.loadPage('<?php echo $key; ?>', this);"><span  class="icon"><i <?php if((is_bool(  $icon['icon'] ) && (  $icon['icon'] )) || !is_bool(  $icon['icon'] )) { echo " class=\"" . ($icon['icon']) . "\""; } ?>></i> </span>  <?php echo t($icon["text"]); ?></a>  <?php if($hasSubMenu) { ?><a  href="#"  class="opener"  onclick="return openMenu(this);"><i  class="fas fa-angle-up"></i> </a>  <?php } ?></div> <?php if(count($icon["menu"]) > 0) { ?><ul  class="admin-sub-menu"> <?php foreach($icon["menu"] as $menu_key => $menu_value) { ?><li><a  href="<?php echo Router::url(); ?>adminv3/<?php echo $key; ?>/<?php echo $menu_value['link']; ?>/"  onclick="return manager.loadPage('<?php echo $key; ?>/<?php echo $menu_value["link"]; ?>/', this);"> <?php echo t($menu_value["text"]); ?></a> </li> <?php } ?></ul>  <?php } ?></li>  <?php } ?></ul> </div> <div  class="admin-page"><div  class="page-title"><h1>Loading...</h1><ul  class="admin-breadcrumb"><li><a  href="#"><?php echo t("Home"); ?></a> </li><li><a  href="#">Loading...</a> </li></ul> </div>    <div  class="content-page"  id="main-page"> Loading...
            </div> </div> </div> </div> <script> var separator = "<?php echo $model['separator']; ?>";
    var manager;
    $(function(){
        var selected = $(".admin-left-menu li.selected");
        if(selected.hasClass("closed")){
            selected.removeClass("closed");
            selected.addClass("open");
        }

        manager = new pageManager(selected, separator);
        manager.loadPage("<?php echo $model['url']; ?>", null, true);
        start();

        DialogService.register(dialogCallback);
    });

    function dialogCallback(type, data) {
        //console.log(">> Window callback", type, data);
        if(type == "created") {
            manager.windows.push(data);
        }
    }
</script></link> </link> 