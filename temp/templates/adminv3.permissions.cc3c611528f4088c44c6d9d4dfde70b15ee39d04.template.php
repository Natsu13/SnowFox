<div  class="row"><div  class="col-md-12"><div  class="tools"><button  class="button"  onclick="createPermission();return false;"><span  class="material-symbols-outlined">add</span> <?php echo t("New permission"); ?></button> </div> <div  class="card"> <?php echo table("permission", Router::url()."adminv3/users/data_permission/", [
                "id" => ["name" => "Id", "width" => 70], 
                "state" => ["width" => 5],
                "name" => ["name" => "Name", "width" => 450],
                "amount" => ["name" => "Amount", "width" => 70],
                "level" => ["name" => "Level", "width" => 70],
                "color" => ["name" => "Color", "width" => 70],
                "action" => ["name" => "Action", "width" => 150]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("permission"); ?></div> </div> </div> <script> function deletePermission(id){
        confirmBox("<?php echo t('Remove permission'); ?>", "<?php echo t('Are you sure you want to remove this permission?'); ?>", function(){
            manager.get("<?php echo Router::url(); ?>adminv3/users/delete_permissions/"+id, {},  function (data, isSuccess) {
                if(isSuccess) {
                    table_permission.reload();
                }
            });
        }, function(){});
    }

    function createPermission(){
        inputBox("<?php echo t('New permission'); ?>", "<?php echo t('Enter name for new permission'); ?>", "<?php echo t('New permission'); ?>", function(value){
            manager.get("<?php echo Router::url(); ?>adminv3/users/create_permission/", {name: value },  function (data, isSuccess) {
                if(isSuccess) {
                    manager.loadPage("<?php echo Router::url(); ?>adminv3/users/edit_permission/" + data.id, null);
                }
            });
        }, function(){

        });
    }
</script>