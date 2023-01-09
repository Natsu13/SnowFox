<div  class="row"><div  class="col-md-12"><div  class="tools"><a  class="button"  href="<?php echo Router::url(); ?>adminv3/system/article/"><span  class="material-symbols-outlined">settings</span> <?php echo t("Settings"); ?></a> </div> <div  class="card tools"><label> <?php echo t("User or ip address"); ?>:  
                <input  type="text"  value=""  id="user_or_address"  data-list="<?php echo implode(';', $model['selectList']); ?>" /></label></div> <div  class="card"> <?php echo table("comments", Router::url()."adminv3/article/data_comments/", [
                "id" => ["name" => "Id", "width" => 70],
                "state" => ["width" => 50],
                "date" => ["name" => "Date"],
                "text" => ["name" => "Text"],
                "author" => ["name" => "Author", "width" => 200],
                "ip" => ["name" => "IP"],
                "action" => ["name" => "Action", "width" => 250]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("comments"); ?></div> </div> </div> <script> $(function(){
        $("#user_or_address").on("change", function(){
            table_comments.filter("name", $(this).val());
            table_comments.reload();
        });
    });

    function restoreComments(id){
        manager.get("<?php echo Router::url(); ?>adminv3/article/comments_restore/"+id, {},  function (data, isSuccess) {
            if(isSuccess) {
                table_comments.reload();
            }
        });
    }
    function deleteComments(id){
        manager.get("<?php echo Router::url(); ?>adminv3/article/comments_delete/"+id, {},  function (data, isSuccess) {
            if(isSuccess) {
                table_comments.reload();
            }
        });
    }
</script>