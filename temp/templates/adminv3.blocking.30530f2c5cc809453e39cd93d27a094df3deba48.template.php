<div  class="row"><div  class="col-md-12"><div  class="tools"><button  class="button"  onclick="newBlock();return false;"><span  class="material-symbols-outlined">add</span> <?php echo t("New block"); ?></button> </div> <div  class="card tools"><label> <?php echo t("User or ip address"); ?>:  
                <input  type="text"  value=""  id="user_or_address"  data-list="<?php echo implode(';', $model['selectList']); ?>" /></label><label><input  type="checkbox"  value="1"  id="activeOnly" /> <?php echo t("Only active"); ?></label></div> <div  class="card"> <?php echo table("blocks", Router::url()."adminv3/users/data_blocking/", [
                "id" => ["name" => "Id", "width" => 50], 
                "state" => ["width" => 50],
                "type" => ["name" => "Type", "width" => 150],
                "banned" => ["name" => "Banned", "width" => 200],
                "bannedBy" => ["name" => "Banned by", "width" => 200],
                "expires" => ["name" => "Expires", "width" => 250],
                "info" => ["name" => "Info", "width" => 350],
                "action" => ["name" => "Action", "width" => 150]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("blocks"); ?></div> </div> </div> <div  class="template"  id="new-block-content"><form  action="<?php echo Router::url(); ?>adminv3/users/new_blocking/"  method="post"><div  class="row"><div  class="col-md-4 static-text"><?php echo t("Block"); ?></div> <div  class="col-md-8"><input  type="text"  value=""  placeholder="<?php echo t('IP or Nick'); ?>"  data-list="<?php echo implode(';', $model['selectList']); ?>"  name="block" /></div>      </div> <div  class="row"><div  class="col-md-4 static-text"><?php echo t("Action"); ?></div> <div  class="col-md-8"><select  name="action"  style="width: 100%;"><option  value="all"><?php echo t("All"); ?></option>  <?php foreach(User::$blockType as $n => $row) { ?><option <?php if((is_bool(  $row ) && (  $row )) || !is_bool(  $row )) { echo " value=\"" . ($row) . "\""; } ?>><?php echo $row; ?></option>  <?php } ?></select> </div>      </div> <div  class="row"><div  class="col-md-4 static-text"><?php echo t("Length"); ?></div> <div  class="col-md-8"><input  type="text"  name="hours"  style='width:70%;'  placeholder="0"  class="price"  data-postfix='<?php echo t("hours"); ?>' /></div>      </div> <div  class="row"><div  class="col-md-4 static-text"><?php echo t("Information"); ?></div> <div  class="col-md-8"><input  type="text"  name="information" /></div>      </div> <div  class="row"><div  class="col-md-4 static-text"><?php echo t("Internal info"); ?></div> <div  class="col-md-8"><input  type="text"  name="internalinfo" /></div>      </div> <button  type="submit"  class="hidden submit button button-primary"  name="block-user"><?php echo t('Block'); ?></button>  </form> </div> <script> var createDialog;

    function newBlock(){
        createDialog = new Dialog();
        createDialog.setTitle("<?php echo t('New block'); ?>");
        createDialog.setButtons([Dialog.CLOSE, Dialog.CREATE]);
        createDialog.dialogHtml.append($("#new-block-content"));
        $("#new-block-content").removeClass("template");        
        createDialog.Show();
        var butt = createDialog.getButtons();
        $(butt[1]).click(function () {
            btnLoading($(butt[1]), true);
            manager.submitForm("[name=block-user]", function(data){
                btnLoading($(butt[1]), false);
                if(data.error != undefined && data.error != "") {
                    manager.notification(data.error, "error");
                    return;
                }                
                createDialog.Close();
                table_blocks.reload();
            }) 
        });
        $(butt[0]).click(function () {createDialog.Close(); });
    }

    function deleteBlocking(id){
        manager.get("<?php echo Router::url(); ?>adminv3/users/delete_blocking/"+id, {},  function (data, isSuccess) {
            if(isSuccess) {
                table_blocks.reload();
            }
        });
    }

    $(function(){
        $("#activeOnly").on("change", function(){
            table_blocks.filter("active", $(this).is(":checked")?1:0).reload();
        });
        $("#user_or_address").on("change", function(){
            table_blocks.filter("name", $(this).val()).reload();
        });
    });
</script>