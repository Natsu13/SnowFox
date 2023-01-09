<div  class="row"><div  class="col-md-12"><div  class="tools"><button  class="button"  onclick="createUser();return false;"><span  class="material-symbols-outlined">add</span> <?php echo t("New user"); ?></button> </div> <div  class="card tools"><label> <?php echo t("Filter by permission"); ?>: 
                <select  id="permission"><option  value=''><?php echo t("All permissions"); ?></option>  <?php foreach($model["permissionList"] as $key => $perm) { ?><option <?php if((is_bool(  $perm['id'] ) && (  $perm['id'] )) || !is_bool(  $perm['id'] )) { echo " value=\"" . ($perm['id']) . "\""; } ?>><?php echo $perm['name']; ?></option>  <?php } ?></select> </label><label><input  type="checkbox"  value="1"  id="activeOnly" /> <?php echo t("Only active"); ?></label></div> <div  class="card"> <?php echo table("users", Router::url()."adminv3/users/data/", [
                "id" => ["name" => "Id", "width" => 70], 
                "state" => ["width" => 50],
                "name" => ["name" => "Name", "width" => 300],
                "email" => ["name" => "Email"],
                "permission" => ["name" => "Permission", "width" => 250],
                "ip" => ["name" => "IP Address", "width" => 250],
                "action" => ["name" => "Action", "width" => 250]
            ]); ?></div> <div  class="card"> <?php echo tablePaginator("users"); ?></div> </div> </div> <script> function createUser(){

    }

    $(function(){
        $("#permission").on("change", function(){
            table_users.filter("permission", $(this).find("option:selected").val());
            table_users.reload();
        });
        $("#activeOnly").on("change", function(){
            table_users.filter("active", $(this).is(":checked")?1:0);
            table_users.reload();
        });
    });
</script>