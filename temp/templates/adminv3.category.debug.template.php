<div  class="row"><div  class="col-md-12"><div  class="tools"><button  class="button"  onclick="createCategory();return false;"><span  class="material-symbols-outlined">add</span> <?php echo t("New category"); ?></button> </div> <div  class="card"> <?php echo table("category", Router::url()."adminv3/article/data_category/", [
                "id" => ["name" => "Id", "width" => 70],
                "title" => ["name" => "Title"],
                "alias" => ["name" => "Alias"],
                "access" => ["name" => "Access", "width" => 300],
                "action" => ["name" => "Action", "width" => 250]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("category"); ?></div> </div> </div> <script> function createCategory(){
        inputBox("<?php echo t('New category'); ?>", "<?php echo t('Enter name for new category'); ?>", "<?php echo t('New category'); ?>", function(value){
            manager.get("<?php echo Router::url(); ?>adminv3/article/category_create/", {name: value },  function (data, isSuccess) {
                if(isSuccess) {
                    manager.loadPage("<?php echo Router::url(); ?>adminv3/article/edit_category/" + data.id, null);
                }
            });
        }, function(){

        });
    }

    function deleteCategory(id){
        var id = id;
        confirmBox("<?php echo t('Delete category'); ?>", "<?php echo t('Are you sure you want to delete this category?'); ?>", function(){
            manager.get("<?php echo Router::url(); ?>adminv3/article/category_delete/" + id, {},  function (data, isSuccess) {
                if(isSuccess) {
                    table_category.reload();
                }
            });
        }, function(){});
    }
</script>