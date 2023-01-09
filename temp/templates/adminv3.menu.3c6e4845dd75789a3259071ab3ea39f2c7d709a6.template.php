<div  class="sort-title"><?php echo $model["box"]; ?></div> <div  class="sort-list"  id="select-items-<?php echo $model['box']; ?>" <?php if((is_bool(  $model['box'] ) && (  $model['box'] )) || !is_bool(  $model['box'] )) { echo " data-box=\"" . ($model['box']) . "\""; } ?>><div  class="empty"  style="display:<?php echo count($model['items']) == 0?'block':'none'; ?>;"><?php echo t("No items"); ?></div>  <?php $index = 0; ?> <?php foreach($model["items"] as $key => $item) { ?><div  class="sort-list-item <?php echo $item['isVisible']?'':'not-active'; ?>"  id="list-item-<?php echo $model['box']; ?>-<?php echo $index; ?>"  title="<?php echo t($item['type']); ?>"><input  type="hidden"  class="value-item item-id"  data-name="item_id" <?php if((is_bool(  $item['id'] ) && (  $item['id'] )) || !is_bool(  $item['id'] )) { echo " value=\"" . ($item['id']) . "\""; } ?> /><div  class="mover"><span  class="material-symbols-outlined select-item-mover">drag_indicator</span> </div> <div  class="item-name"><span  class="menu-icon menu-icon-<?php echo $item['type']; ?>"></span>  <?php echo $item['title']; ?></div> <div  class="mini"><a  href="#"  class="button button-visibility" <?php if((is_bool(  $index ) && (  $index )) || !is_bool(  $index )) { echo " data-index=\"" . ($index) . "\""; } ?>  onclick="menuManager.visibleItem('<?php echo $model['box']; ?>', $(this).data('index')); return false;"  title="<?php echo t('Visibility'); ?>"><span  class="icon"></span> </a> </div> <div  class="mini"><a  href="#"  class="button button-copy" <?php if((is_bool(  $index ) && (  $index )) || !is_bool(  $index )) { echo " data-index=\"" . ($index) . "\""; } ?>  onclick="menuManager.copyItem('<?php echo $model['box']; ?>',$(this).data('index')); return false;"  title="<?php echo t('Copy'); ?>"><span  class="material-symbols-outlined">content_copy</span> </a> </div> <div  class="mini"><a  href="#"  class="button button-edit" <?php if((is_bool(  $index ) && (  $index )) || !is_bool(  $index )) { echo " data-index=\"" . ($index) . "\""; } ?>  onclick="menuManager.editItem('<?php echo $model['box']; ?>',$(this).data('index')); return false;"  title="<?php echo t('Edit'); ?>"><span  class="material-symbols-outlined">edit</span> </a> </div> <div  class="mini"><a  href="#"  class="button button-delete" <?php if((is_bool(  $index ) && (  $index )) || !is_bool(  $index )) { echo " data-index=\"" . ($index) . "\""; } ?>  onclick="menuManager.removeItem('<?php echo $model['box']; ?>',$(this).data('index')); return false;"  title="<?php echo t('Delete'); ?>"><span  class="material-symbols-outlined">delete</span> </a> </div> </div>  <?php $index++; ?> <?php } ?></div> <script> $(function () {
        $("#select-items-<?php echo $model['box']; ?>").sortable({
            handle: ".mover",
            //axis: "y",
            connectWith: ".sort-list",
            animation: 150,
            stop: function(event, ui){
				if(notupdate){
					notupdate = false;
					return;
				}
                var sort = $.map($(this).find(".sort-list-item"), function(e,i){return $(e).find(".item-id").val(); });                                 
                //console.log("stop", sort, $(this).data("box"));

                manager.get("<?php echo Router::url(); ?>adminv3/menu/menu_update_positions/", {sort: sort, language: $("#language-selector a.toggle").data("lang") }, function (data) {
                    if(data.ok) {
                        manager.notification("<?php echo t('Saved'); ?>", "success");
                    }else{
                        manager.notification(data.error, "error");
                    }
                });
            },
			receive: function(event, ui){
				notupdate = true;
                var sort = $.map($(this).find(".sort-list-item"), function(e,i){return $(e).find(".item-id").val(); });
                //console.log(sort, $(this), $(this).parent());
				if(sort == "" || sort.length == 0 || sort == undefined){
					$(this).parent().find(".empty").show();
				}else {
                    $(this).parent().find(".empty").hide(); 
                }                
                
                manager.get("<?php echo Router::url(); ?>adminv3/menu/menu_update_positions/", {sort: sort, box: $(this).data("box"), language: $("#language-selector a.toggle").data("lang") }, function (data) {
                    if(data.ok) {
                        manager.notification("<?php echo t('Saved'); ?>", "success");
                        menuManager.refreshIfEmpty();
                    }else{
                        manager.notification(data.error, "error");
                    }
                });
            }
        });
    });
</script>