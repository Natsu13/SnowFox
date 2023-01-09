<div  class="row"><div  class="col-md-12"><div  class="tools"><a  href="#"  onclick="createRedirect();return false;"  class="button"><span  class="material-symbols-outlined">add</span>   <?php echo t("New redirecting"); ?></a> </div> <div  class="alert alert-warning"> <?php echo t("These redirects are preceded by other modular and system redirects, in addition to ajax"); ?></div> <div  class="card"> <?php echo table("redirecting", Router::url()."adminv3/system/data_redirecting/", [
                "id" => ["name" => "Id", "width" => 70], 
                "state" => ["width" => 30], 
                "name" => ["name" => "Name", "width" => 250],
                "from" => ["name" => "From", "width" => 350],
                "to" => ["name" => "To", "width" => 350],
                "action" => ["name" => "Action", "width" => 200]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("redirecting"); ?></div> </div> </div> <script> function deleteRedirect(btn, id){
        btnLoading($(btn), true);
        manager.get("<?php echo Router::url(); ?>adminv3/system/delete_redirecting/"+id, {},  function (data, isSuccess) {
            if(isSuccess) {
                table_redirecting.reload();
            }
        });
    }

    function createRedirect(){
        inputBox("<?php echo t('New redirect'); ?>", "<?php echo t('Enter name for new redirect'); ?>", "<?php echo t('New redirect'); ?>", function(value){
            manager.get("<?php echo Router::url(); ?>adminv3/system/create_redirecting/", {name: value },  function (data, isSuccess) {
                if(isSuccess) {
                    manager.loadPage("<?php echo Router::url(); ?>adminv3/system/edit_redirecting/" + data.id, null);
                }
            });
        }, function(){

        });
    }
</script>