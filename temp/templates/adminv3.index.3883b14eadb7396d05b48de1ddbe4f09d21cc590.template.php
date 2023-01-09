<div  class="row"><div  class="col-md-12"><div  class="tools"><button  class="button"  onclick="createForm();return false;"><span  class="material-symbols-outlined">add</span> <?php echo t("New form"); ?></button>  <a  href="<?php echo Router::url(); ?>adminv3/content/settings/"  class="button"><span  class="material-symbols-outlined">settings</span>   <?php echo t("Settings"); ?></a> </div> <div  class="card"> <?php echo table("forms", Router::url()."adminv3/content/data/", [
                "id" => ["name" => "Id", "width" => 70],                 
                "state" => ["width" => 5],
                "version" => ["name" => "Ver", "title" => "Version", "width" => 70], 
                "title" => ["name" => "Title"],
                "created" => ["name" => "Created", "width" => 200],
                "answers" => ["name" => "Answers", "width" => 100],
                "action" => ["name" => "Action", "width" => 350]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("forms"); ?></div> </div> </div> <script> function createForm(){
        inputBox("<?php echo t('New form'); ?>", "<?php echo t('Enter name for new form'); ?>", "<?php echo t('New form'); ?>", function(value){
            manager.get("<?php echo Router::url(); ?>adminv3/content/create/", {name: value },  function (data, isSuccess) {
                if(isSuccess) {
                    manager.loadPage("<?php echo Router::url(); ?>adminv3/content/edit/" + data.id, null);
                }
            });
        }, function(){});
    }

    function deleteForm(id){
        confirmBox("<?php echo t('Remove form'); ?>", "<?php echo t('Are you sure you want to remove this form?'); ?>", function(){
            manager.get("<?php echo Router::url(); ?>adminv3/content/delete/"+id, {},  function (data, isSuccess) {
                if(isSuccess) {
                    table_forms.reload();
                }
            });
        }, function(){});
    }
</script>