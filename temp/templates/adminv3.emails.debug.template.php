<div  class="row"><div  class="col-md-12"><div  class="tools"><a  href="<?php echo Router::url(); ?>adminv3/system/settings_emails/"  class="button"><span  class="material-symbols-outlined">settings</span>   <?php echo t("Settings"); ?></a> </div> <div  class="card tools"><label  class="input"> <?php echo t("Filter by receiver"); ?>: 
                <input  type="text"  id="receiver" /></label> </div> <div  class="card"> <?php echo table("emails", Router::url()."adminv3/system/data_emails/", [
                "id" => ["name" => "Id", "width" => 70], 
                "from" => ["name" => "From"],
                "to" => ["name" => "To"],
                "subject" => ["name" => "Subject", "width" => 250],
                "user" => ["name" => "User"],
                "date" => ["name" => "Date", "width" => 250],
                "action" => ["name" => "Action", "width" => 150]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("emails"); ?></div> </div> </div> <script> $(function(){
        $("#receiver").on("keyup", function(){
            table_emails.filter("receiver", $(this).val());
            table_emails.reload();
        });
    });
</script>