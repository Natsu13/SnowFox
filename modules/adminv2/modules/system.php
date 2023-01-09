<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];
$user = User::current();
$superuser = $t->root->config->getD("superuser", 1);

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
		echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/'>".t("Articles")."</a></li>";
		echo "<li ".($action == "emails"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/emails/'>".t("Emails")."</a></li>";
		echo "<li ".($action == "redirecting"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/redirecting/'>".t("Redirecting")."</a></li>";		
		echo "<li ".($action == "login"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/login/'>".t("Login")."</a></li>";	
		echo "<li ".($action == "cookies"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/cookies/'>".t("Cookies")."</a></li>";
		echo "<li ".($action == "ftp"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/ftp/'>".t("FTP")."</a></li>";
		echo "<li ".($action == "infobar"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/infobar/'>".t("Infobar")."</a></li>";
		if($user["id"] == $superuser){
			echo "<li ".($action == "lock"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/lock/'>".t("Lock")."</a></li>";
			echo "<li ".($action == "cron"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/cron/'>".t("Cron")."</a></li>";
			echo "<li ".($action == "variables"?"class=select":"")."><a href='".$t->router->url_."adminv2/system/variables/'>".t("Variables")."</a></li>";
		}
	echo "</ul>";
}

if($action == "infobar"){
	if($_GET["__type"] != "ajax"){
		echo "<div class=\"bottommenu hide-mobile\">";
			echo "<a href=\"".Router::url()."adminv2/system/infobar-new/\">".t("New toolbar")."</a>";
		echo "</div><br/><br/>";
	}

	if($who == "edit") {
        $result = dibi::query("SELECT * FROM :prefix:toolbar WHERE id=%i", $_GET["id"])->fetch();
        if($result == NULL){
            header("location:".Router::url()."adminv2/system/infobar/");
        }else{            
            
            if($_GET["__type"] == "ajax"){
                if(isset($_GET["viewcond"])){
                    $data = json_decode($result["data"]);

                    if($result["data"] == ""){
                        $result["data"] = "[]";
                    }
                    echo $result["data"];
                }
                else if(isset($_GET["removecond"])){
                    $id = $_GET["removecond"];
                    $data = json_decode($result["data"]);

                    $linq = new LinQ($data);
                    $data = $linq->Where(function($x, $i)use($id){ return $x->id != $id; })->ToArray();

                    dibi::query('UPDATE :prefix:toolbar SET ', array("data" => json_encode($data)), 'WHERE `id`=%s', $_GET["id"]);

                    echo json_encode(array("ok" => true));
                }
                else if(isset($_GET["addcond"])){
                    $data = json_decode($result["data"], true);
                    $cond = json_decode($_GET["cond"]);

                    $cond->id = Utilities::GUID();
                    $data[] = $cond;

                    dibi::query('UPDATE :prefix:toolbar SET ', array("data" => json_encode($data)), 'WHERE `id`=%s', $_GET["id"]);

                    echo json_encode(array("ok" => true));
                }
                else if(isset($_GET["conditons"])){                    
                    echo "<form action=# method=post id=cond_form>";
                        echo "<ul class='radio-list' id='condition_type'>";
                            $types = array(
                                //array("name" => "Show only in category", "id" => "category"), //actualy module!
                                array("name" => "Show only <i>x</i> times for user", "id" => "showonly"),
                                array("name" => "Show only for <i>type</i> of user", "id" => "showonlytype"),
                                array("name" => "Show every <i>x</i> hour/day/month", "id" => "showonlytime", "description" => "This mean if user close this it will be show again after this period"),
                                array("name" => "Show only from <i>x</i> to <i>x</i> hour in day", "id" => "showonlyintime")                                
                            );
                            if($result["type"] == "popup_window" || $result["type"] == "confirm_page"){
                                $types[] = array("name" => "User must click on <i>selected</i> button", "id" => "mustclick");
                            }
                            foreach($types as $key => $type){
                                echo "<li onclick=\"$('#condition_type li').removeClass('selected');$(this).addClass('selected');$(this).find('input[type=radio]').prop('checked', true);\">";
                                    echo "<div class=control><input type=radio name=type value='".$type["id"]."'></div>";
                                    echo "<div class=text>";
                                        echo "<div class=name>".t($type["name"])."</div>";
                                        if(isset($type["description"])){
                                            echo "<div class=description>".t($type["description"])."</div>";
                                        }
                                    echo "</div>";
                                echo "</li>";
                            }
                        echo "</ul>";

                        //Category picker - module!
						/*
                        echo "<div style='display:none;' id='condition_category' class='condition_second'>";
                            echo "<div class=title>".t("Select category")."</div>";
                            echo "<div class=title_sub>".t("it will show in subcategories too")."</div>";
                            echo "<div class=catpicker>";
                                drawCat();
                            echo "</div>";
                        echo "</div>";
						*/

                        //How many times picker
                        echo "<div style='display:none;' id='condition_showonly' class='condition_second'>";
                            echo "<div class=title>".t("Show only <i>x</i> times for user")."</div>";
                            echo "<div class=title_sub>".t("It's better to use it with other condition")."</div>";
                            echo "<div>";
                                echo "<div class=\"form-group\">";
                                    echo "<div class=\"column\">";
                                        echo "<div class=\"form-section\">";
                                            echo "<label class=name>".t("How many times?")."</label>";
                                            echo "<input type=text name='showonly_times' value=1>";
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        //User permission picker
                        echo "<div style='display:none;' class='condition_second' id='condition_showonlytype'>";
                            echo "<div class=title>".t("Show only for <i>type</i> of user")."</div>";
                            //echo "<div class=title_sub>".t("And above level")."</div>";

                            echo "<div class=\"form-group\">";
                                echo "<div class=\"column\">";
                                    echo "<div class=\"form-section\">";
                                        echo "<label><input type=checkbox name=condition_showonlytype_above> ".t("Include level above")."</label>";                                        
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";

                            echo "<ul class='radio-list'>";
                                $resul_perm = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
                                foreach($resul_perm as $n => $row){
                                    echo "<li onclick=\"$('#condition_showonlytype ul li').removeClass('selected');$(this).addClass('selected');$(this).find('input[type=radio]').prop('checked', true);\">";
                                        echo "<div class=control><input type=radio name=showonlytype value='".$row["id"]."'></div>";
                                        echo "<div class=text>";
                                            echo "<div class=name style='color:".$row["color"].";'>".$row["name"]."</div>";
                                            echo "<div class=description>".t("Level").": ".$row["level"]."</div>";
                                        echo "</div>";
                                    echo "</li>";
                                }
                            echo "</ul>";
                        echo "</div>";

                        //Show every x hour/day/month
                        echo "<div style='display:none;' id='condition_showonlytime' class='condition_second'>";
                            echo "<div class=title>".t("Show every <i>x</i> hour/day/month")."</div>";
                            echo "<div class=title_sub>".t("This mean if user close this it will be show again after this period")."</div>";
                            echo "<div>";
                                echo "<div class=\"form-group\">";
                                    echo "<div class=\"column\">";
                                        echo "<div class=\"form-section\">";
                                            echo "<label class=name>".t("How many times?")."</label>";
                                            echo "<input type=text class=input name='showonlytime_time' value=1>";
                                        echo "</div>";
                                        echo "<div class=\"form-section\">";
                                            echo "<label class=name>".t("How?")."</label>";
                                            echo "<select name='showonlytime_how' style='width:100%;'>";
                                                echo "<option value='hour'>".t("Hour")."</option>";
                                                echo "<option value='day'>".t("Day")."</option>";
                                                echo "<option value='month'>".t("Month")."</option>";
                                            echo "</select>";
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        //Show from x to x hour
                        echo "<div style='display:none;' id='condition_showonlyintime' class='condition_second'>";
                            echo "<div class=title>".t("Show only from <i>x</i> to <i>x</i> hour in day")."</div>";
                            echo "<div class=title_sub>".t("For example if you want show text \"We ship your item today if you buy it until 14:00\" after 14 this baner will be hidden")."</div>";
                            echo "<div>";
                                echo "<div class=\"form-group\">";
                                    echo "<div class=\"column\">";
                                        echo "<div class=\"form-section\">";
                                            echo "<label class=name>".t("From")."</label>";
                                            echo "<div class=full-input>";
                                                echo "<input type=text name='showonlyintime_from_hour' value=00 style='text-align:center;'>";
                                                echo "<span>:</span>";
                                                echo "<input type=text name='showonlyintime_from_minutes' value=00 style='text-align:center;'>";
                                            echo "</div>";    
                                        echo "</div>";
                                        echo "<div class=\"form-section\">";
                                            echo "<label class=name>".t("To")."</label>";
                                            echo "<div class=full-input>";
                                                echo "<input type=text name='showonlyintime_to_hour' value=14 style='text-align:center;'>";
                                                echo "<span>:</span>";
                                                echo "<input type=text name='showonlyintime_to_minutes' value=00 style='text-align:center;'>";
                                            echo "</div>";    
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        echo "<div class=inline-win-actions>";
                        echo "<input type=submit class='btn btn-secondary' style='display:none;' name=prev value='".t("previous")."' onclick='_cond_prev();return false;'> ";
                            echo "<input type=submit class='btn btn-primary' name=next value='".t("next")."' onclick='_cond_next();return false;'>";
                        echo "</div>";
                    echo "</form>";
                    ?>
                    <script>
                    var step = 1;
                    function _cond_prev(){
                        $("#cond_form input[name='prev']").hide();
                        $(".condition_second").hide();
                        $("#condition_type").show();
                        step = 1;
                    }
                    function _cond_next(){                                                
                        var selected = $("#condition_type input[type=radio]:checked").val();

                        if(step == 2){
                            if(_cond_data() == null)
                                return;
                        }else{
                            if(selected != undefined) {
                                $("#condition_type").hide();
                                $("#cond_form input[name='prev']").show();
                            }
                            /*if(selected == "category"){ //module
                                $("#condition_category").show();
                            }else */if(selected == "showonly"){
                                $("#condition_showonly").show();
                            }else if(selected == "showonlytype"){
                                $("#condition_showonlytype").show();
                            }else if(selected == "showonlytime"){
                                $("#condition_showonlytime").show();
                            }else if(selected == "showonlyintime") {
                                $("#condition_showonlyintime").show();
                            }
                        }

                        step++;
                    }
                    var _cond_obj = {type: ""};
                    function _cond_data(){
                        _cond_obj = {type: $("#condition_type input[type=radio]:checked").val()};

                        /*if(_cond_obj.type == "category"){ //module
                            _cond_obj.category = $("#catli_1 input[type=radio]:checked").val();
                            if(_cond_obj.category == undefined || _cond_obj.category == "") return null;
                        }else */if(_cond_obj.type == "showonly"){
                            _cond_obj.howmany = $("#cond_form input[name=showonly_times]").val();                            
                        }else if(_cond_obj.type == "showonlytype"){
                            _cond_obj.perm = $("#cond_form input[name=showonlytype]:checked").val();
                            _cond_obj.perm_up = $("#cond_form input[name=condition_showonlytype_above]").is(":checked");
                            if(_cond_obj.perm == undefined || _cond_obj.perm == "") return null;
                        }else if(_cond_obj.type == "showonlytime"){
                            _cond_obj.time = $("#cond_form input[name=showonlytime_time]").val();
                            _cond_obj.how = $("#cond_form select[name=showonlytime_how] option:selected").val();
                        }else if(_cond_obj.type == "showonlyintime") {
                            _cond_obj.from_hour = $("#cond_form input[name=showonlyintime_from_hour]").val();
                            _cond_obj.from_minutes = $("#cond_form input[name=showonlyintime_from_minutes]").val();
                            _cond_obj.to_hour = $("#cond_form input[name=showonlyintime_to_hour]").val();
                            _cond_obj.to_minutes = $("#cond_form input[name=showonlyintime_to_minutes]").val();
                        }

                        $.getJSON("<?php echo Router::url() ; ?>adminv2/system/infobar-edit/<?php echo $_GET["id"] ?>/?__type=ajax&addcond", 
                            {cond: JSON.stringify(_cond_obj)}, 
                            function(data) {
                                dialogMain.Close();
                                _cond_reload();
                            }
                        );                        

                        return _cond_obj;
                    }
                    </script>
                    <?php
                }
                exit;
            }

            if(isset($_POST["edit"])) {
                $data = array(
                    "active_from" => strtotime($_POST["active_from"]),
                    "active_until" => ($_POST["active_until"]==""?0:strtotime($_POST["active_until"])),
                    "name" => $_POST["name"],
                    "active" => isset($_POST["active"])?1:0,
                    "alias" => $_POST["alias"],
                    "title" => $_POST["title"],
                    "text" => $_POST["text"]
                );
                dibi::query('UPDATE :prefix:toolbar SET ', $data, 'WHERE `id`=%s', $_GET["id"]);
		        header("location:".$t->router->url_."adminv2/system/infobar-edit/".$_GET["id"]."/ok");
            }

            if($t->router->_data["state"][0] == "ok"){
                $t->root->page->error_box(t("Toolbar has been updated"), "ok", true);
            }

            echo "<div class=content>";
                echo "<form action=# method=post>";
                    echo "<div class=row>";
                        echo "<div class='col-sm-8 col-xs-12'>";

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'>".t("Name")."</label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<input type='text' name='name' class='form-control' value='".$result["name"]."'>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'>".t("Type")."</label>";
                                echo "<div class='col-sm-10'>";
                                    $type = Infobar::getById($result["type"]);
                                    echo "<ul class='radio-list full-width'>";
                                        echo "<li>";
                                            echo "<div class=controll>".$type["picto"]."</div>";
                                            echo "<div class=text>";
                                                echo "<div class=name>".t($type["name"])."</div>";
                                                echo "<div class=description>".t($type["description"])."</div>";
                                            echo "</div>";
                                        echo "</li>";
                                    echo "</ul>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'></label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<label><input type='checkbox' name='active' value=1 ".Utilities::check($result["active"])."> ".t("Active")."</lable>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'>".t("Active from")."</label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<input type='text' name='active_from' id='datetimepicker-from' class='form-control' value='".date("d.m.Y", $result["active_from"])."'>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'>".t("Active until")."</label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<input type='text' name='active_until' id='datetimepicker-until' class='form-control erasable' value='".($result["active_until"] == 0?"":date("d.m.Y", $result["active_until"]))."'>";
                                    echo "<div class=desc>".t("keep it empty if you want unlimited visibility")."</div>";
                                echo "</div>";
                            echo "</div>";

                            $active = ($result["active"] == 1 && $result["active_from"] <= time() && ($result["active_until"] == "" || $result["active_until"] >= time()));
                            if($active){
                                echo "<div class='form-group row mb-2'>";
                                    echo "<label class='col-sm-2 col-form-label'></label>";
                                    echo "<div class='col-sm-10'>";
                                        echo "<span class=state-active></span>".t("This toolbar is active");
                                    echo "</div>";
                                echo "</div>";
                            }

                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'>".t("Alias")."</label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<input type='text' name='alias' class='form-control' value='".$result["alias"]."'>";
                                echo "</div>";
                            echo "</div>";

                            if($result["type"] == "popup_window" || $result["type"] == "confirm_page"){
                                echo "<div class='form-group row mb-2'>";
                                    echo "<label class='col-sm-2 col-form-label'>".t("Title")."</label>";
                                    echo "<div class='col-sm-10'>";
                                        echo "<input type='text' name='title' class='form-control' value='".$result["title"]."'>";
                                    echo "</div>";
                                echo "</div>";
                            }

                            echo "<div class='form-group row mb-2'>";
                                echo "<div class='col-sm-12'>";
                                    echo "<textarea name=text style='width:100%;' class=tinimce id=intereditor rows=10>".htmlentities($result["text"])."</textarea>";
                                echo "</div>";
                            echo "</div>";  
                            
                            echo "<div class='form-group row mb-2'>";
                                echo "<label class='col-sm-2 col-form-label'></label>";
                                echo "<div class='col-sm-10'>";
                                    echo "<input type=submit name=edit value='".t("edit")."' class='btn btn-primary'>";
                                echo "</div>";
                            echo "</div>";   

                        echo "</div>";
                        echo "<div class='col-sm-4 col-xs-12'>";
                            echo "<a href=# class='button button-full-width' onclick=\"loadConditions();return false;\"><i class=\"fas fa-plus\"></i> ".t("Add visiblity condition")."</a>";
                            echo "<div class=\"card\"><div class=content><ul class=list id='list-cond'><li>".t("Loading")."...</li></ul></div></div>";
                            
                            $view_rows = dibi::query("SELECT count(*) FROM :prefix:toolbar_interaction WHERE toolbar_id = %i", $_GET["id"], "AND type='view'")->fetchSingle();
                            $view_rows_user_result = dibi::query("SELECT count(id), user FROM toolbar_interaction WHERE toolbar_id = %i", $_GET["id"], "AND type='view' GROUP BY user");
                            $view_rows_user = $view_rows_user_result->count();
                            $interaction = dibi::query("SELECT count(*) FROM :prefix:toolbar_interaction WHERE toolbar_id = %i", $_GET["id"], "AND type='click'")->fetchSingle();
                            $closed_rows = dibi::query("SELECT count(*) FROM :prefix:toolbar_interaction WHERE toolbar_id = %i", $_GET["id"], "AND type='close'")->fetchSingle();
                            echo "<div class='columns'>";
                                echo "<div>";
                                    echo "<div class='stat_widget'>";                                
                                        echo "<div class=title>".t("Views")."</div>";                                
                                        echo "<div class=count><div class=top>".$view_rows."</div><div class=sub>".t("User")." ".$view_rows_user."</div></div>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div>";
                                    echo "<div class='stat_widget'>";                                
                                        echo "<div class=title>".t("Interaction")."</div>";                                
                                        echo "<div class=count>".$interaction."</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                            echo "<div class='columns'>";
                                echo "<div>";
                                    echo "<div class='stat_widget'>";                                
                                        echo "<div class=title>".t("Closed")."</div>";                                
                                        echo "<div class=count>".$closed_rows."</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";

                        echo "</div>";
                        
                    echo "</div>";
                echo "</form>";
            echo "</div>";

            echo "<script>";
                //echo "var _cond_category = [];";
                echo "var _cond_perms = [];";
				/* 
                $result = dibi::query('SELECT * FROM :prefix:eshop_category'); //module
                foreach($result as $n => $row){
                    echo "_cond_category[".$row["id"]."] = {name: \"".$row["name"]."\"};";
                }*/
                $resul_perm = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
                foreach($resul_perm as $n => $row){
                    echo "_cond_perms[".$row["id"]."] = {name: \"".$row["name"]."\", color: \"".$row["color"]."\"};";
                }
            echo "</script>";
			
            ?>

                <div id=main_dialog style="display:none;max-width:500px;">  
                    Loading...   
                </div>

            <script>
                var dialogMain;

                function _cond_reload(){
                    $.getJSON( "<?php echo Router::url() ; ?>adminv2/system/infobar-edit/<?php echo $_GET["id"] ?>/?__type=ajax&viewcond", function( data ) {
                        $("#list-cond").html("");

                        if(data.length == 0){
                            var li = $("<li></li>");
                            li.html("<?php echo t("No conditions"); ?>");

                            $("#list-cond").append(li);
                        }

                        for(var key in data){
                            var cond = data[key];

                            var text = "";
                            /*if(cond.type == "category"){ // module
                                text = "<?php echo t("Show only in this category"); ?> <b>"+_cond_category[cond.category].name+"</b>";
                            }else */if(cond.type == "showonly"){
                                text = "<?php echo t("Show only") ?> " + cond.howmany + "× <?php echo t("for user") ?>";
                            }else if(cond.type == "showonlytype"){
                                var perm = _cond_perms[cond.perm];
                                text = "<?php echo t("Show only for") ?> <b style='color:" + perm.color + ";'>" + perm.name + "</b>";
                                if(cond.perm_up){
                                    text+= " <?php echo t("and above"); ?>";
                                }
                            }else if(cond.type == "showonlytime"){
                                var t = "<?php echo t("hour"); ?>";
                                if(cond.how == "day"){ t = "<?php echo t("day"); ?>"; }
                                else if(cond.how == "month"){ t = "<?php echo t("month"); ?>"; }

                                text = "<?php echo t("Show only every") ?> <b>" + cond.time + " "+t+"</b>";
                            }else if(cond.type == "showonlyintime") {
                                text = "<?php echo t("Show only from"); ?> <b>"+cond.from_hour+":"+cond.from_minutes+"</b> <?php echo t("to"); ?> <b>"+cond.to_hour+":"+cond.to_minutes+"</b>";
                            }

                            var li = $("<li></li>");
                            var divs = $("<div class=remv><?php echo t("Click to delete"); ?></div>");
                            li.html(text);
                            li.append(divs);
                            li.data("id", cond.id);
                            li.on("click", function(e){
                                e.preventDefault();

                                var _cond_remove_id = $(this).data("id");
                                var dialog = new Dialog();
                                dialog.setTitle("<?php echo t("Remove condition"); ?>");
                                dialog.setButtons(Dialog.OK_CLOSE);
                                dialog.dialogHtml.html("<div class=cnt><?php echo t("Are you sure you want to remove this condition?"); ?></div>");
                                dialog.Show();
                                butt = dialog.getButtons();
                                $(butt[0]).click(function(){ dialog.Close(); });
                                $(butt[1]).click(function(){ 
                                    btnLoading($(this), true);
                                    $.getJSON( "<?php echo Router::url() ; ?>adminv2/system/infobar-edit/<?php echo $_GET["id"] ?>/?__type=ajax&removecond="+_cond_remove_id, function( data ) {
                                        dialog.Close(); 
                                        _cond_reload();
                                    });
                                });
                            });

                            $("#list-cond").append(li);
                        }
                    });
                }

                function loadConditions(){                    
                    dialogMain.Load("<?php echo Router::url() ; ?>adminv2/system/infobar-edit/<?php echo $_GET["id"] ?>/?__type=ajax&conditons");
                }

                $(function(){
                    dialogMain = new Dialog(400);
                    dialogMain.setTitle("Conditions show");
                    //dialogMain.setButtons(Dialog.OK_CLOSE);     
                    //var btn = dialogMain.getButtons();
                    //$(btn[1]).click(function(){ btnLoading($(this), true); });
                    //$(btn[0]).click(function(){ dialogMain.Close(); });
                    dialogMain.html.dialogHtml.append($("#main_dialog"));   
                    $("#main_dialog").show();                           

					$.datetimepicker.setLocale('cs');
					$('#datetimepicker-from').datetimepicker({
						timepicker:false,
						theme:'dark',
						startDate:new Date(),
						format:'d.m.Y'
					});
                    $('#datetimepicker-until').datetimepicker({
						timepicker:false,
						theme:'dark',
						startDate:new Date(),
						format:'d.m.Y'
					});

                    _cond_reload();
				});
            </script>
            <?php
        }
    }else if($who == "new"){
		if(isset($_POST["add"])){
            $data = array(
                "name" => t("New toolbar"),
                "active_from" => time(),
                "type" => $_POST["type"],
                "active" => 0
            );
            dibi::query('INSERT INTO :prefix:toolbar', $data);            
            header("location:".Router::url()."adminv2/system/infobar-edit/".$result["id"]."/");
        }
        echo "<h2>".t("Add new toolbar")."</h2>";        
        echo "<form action=# method=post>";
            echo "<ul class='radio-list' id='bartype'>";
                foreach(Infobar::getTypes() as $key => $bar){
                    echo "<li onclick=\"$('#bartype li').removeClass('selected');$(this).addClass('selected');$(this).find('input[type=radio]').prop('checked', true);\">";
                        echo "<div class=control><input type=radio name=type value='".$bar["id"]."'></div>";
                        echo "<div class=control>".$bar["picto"]."</div>";
                        echo "<div class=text>";
                            echo "<div class=name>".t($bar["name"])."</div>";
                            echo "<div class=description>".t($bar["description"])."</div>";
                        echo "</div>";
                    echo "</li>";
                }
            echo "</ul>";
            echo "<input type=submit class='btn btn-primary' name=add value='".t("add")."'>";
        echo "</form>";
	}else{
		echo "<div class=content>";
		echo "<div class=row>";            
            for($i = 0; $i < count(Infobar::getTypes()); $i++){
                echo "<div class='col-sm-6 col-12 col-md-3'>";
                    echo "<div class=main-card-title>".t(Infobar::getTypes()[$i]["name"])."</div>";
                    echo "<div class=card>";
                        echo "<div class=content>";
                            echo "<ul class=list id=sortable>";

                                $result = dibi::query('SELECT * FROM :prefix:toolbar WHERE type = %s', Infobar::getTypes()[$i]["id"],' ORDER BY active_from DESC');

                                if($result->count() == 0){
                                    echo "<li class='swipable' data-swipable='false'>";
                                        echo "<div class='main'>";
                                            echo "<div class=title><div class=desc>";
                                                echo t("No items");
                                            echo "</div></div>";
                                        echo "</div>";
                                    echo "</li>";
                                }

                                foreach ($result as $n => $row) {
                                    $active = ($row["active"] == 1 && $row["active_from"] <= time() && ($row["active_until"] == "" || $row["active_until"] >= time()));

                                    echo "<li class='swipable' data-swipable='true' data-id='".$row["id"]."'>";
                                        echo "<div class='back red left small'>".t("delete")."</div>";
                                        echo "<div class='back blue right small'>".($row["active"] == 1? t("deactive"): t("active"))."</div>";
                                        echo "<div class='main'>";
                                            echo "<div class=title>";
                                                if($active){
                                                    echo "<span class=state-active></span>";
                                                }
                                                echo $row["name"];
                                            echo "</div>";
                                        echo "</div>";
                                    echo "</li>";
                                }
                            echo "</ul>";
                        echo "</div>";                        
                    echo "</div>";
                echo "</div>";
            }
        echo "<div>";

        ?>
        <script>
        $(function(){
            $(".swipable").on("click", function(){
                if(typeof $(this).data("id") != "undefined"){
                    window.location.href = "<?php echo $t->router->url_."adminv2/system/infobar-edit/"; ?>" + $(this).data("id");
                }
            });
            $(".swipable").on("swiped", function(e, direction){
                if(direction == "right"){
                    window.location.href = "<?php echo $t->router->url_."adminv2/system/infobar/?active=" ?>" + $(this).data("id");
                }else{
                    window.location.href = "<?php echo $t->router->url_."adminv2/system/infobar/?delete="; ?>" + $(this).data("id");
                }
            });
            
            setTimeout(function(){
                actionButton.setText("<?php echo t("New toolbar"); ?>")
                actionButton.changeIcon("fas fa-plus");
                actionButton.show();
                actionButton.onclick(function(){
                    window.location.href = "<?php echo Router::url()."adminv2/system/infobar-new/"; ?>";
                });
            }, 100);
        });
        </script>
        <?php
		echo "</div>";
	}
}
else if($action == "ftp"){
	if($_GET["__type"] == "ajax"){
		if(isset($_GET["saveFile"])) {
			$file = _ROOT_DIR . $_GET["saveFile"];
			file_put_contents($file, $_GET["text"]);

			echo json_encode(array("ok" => true));
		}
		else if(isset($_GET["file"])) {
			$file = _ROOT_DIR . $_GET["file"];
			if(!file_exists($file)) {
				echo json_encode(array("error" => "FILE_NOT_EXISTS", "message" => "File not exists"));
				exit;
			}

			echo json_encode(array(
				"name" => $_GET["name"],
				"text" => file_get_contents(_ROOT_DIR . $_GET["file"])
			));
		}
		else if(isset($_GET["list"])) {
			$dir = _ROOT_DIR . $_GET["list"];
			if(!is_dir($dir)){
				echo json_encode(array("error" => "FOLDER_NOT_EXISTS", "message" => "Folder not exists"));
				exit;
			}

			$list = array("files" => [], "dirs" => []);			
			$cdir = scandir($dir);
			foreach ($cdir as $key => $value) { 
				$fpath = $dir . DIRECTORY_SEPARATOR . $value;

				$perms = fileperms($fpath);
				// Owner
				$info .= (($perms & 0x0100) ? 'r' : '-');
				$info .= (($perms & 0x0080) ? 'w' : '-');
				$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
				// Group
				$info .= (($perms & 0x0020) ? 'r' : '-');
				$info .= (($perms & 0x0010) ? 'w' : '-');
				$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
				// World
				$info .= (($perms & 0x0004) ? 'r' : '-');
				$info .= (($perms & 0x0002) ? 'w' : '-');
				$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

				if (!in_array($value,array(".",".."))) {		
					if(is_dir($fpath)){
						$list["dirs"][] = array(
							"name" => $value, 
							"isdir" => true,
							"size" => "",
							"lastmodify" => "",
							"perms" => $perms
						);
					}else{
						$list["files"][] = array(
							"name" => $value, 
							"isdir" => false,
							"size" => filesize($fpath),
							"lastmodify" => date("d.m.y H:i:s", filemtime($fpath)),
							"perms" => $perms
						);	
					}					
				}
			}			
			usort($list["files"], function($a, $b){ return strcmp(strtolower($a["name"]), strtolower($b["name"])); });		
			usort($list["dirs"], function($a, $b){ return strcmp(strtolower($a["name"]), strtolower($b["name"])); });
			$result = array_merge($list["dirs"], $list["files"]);
			//usort($fileList, function($a, $b){ if($a["isdir"]){ return -1; } return 1; });
			echo json_encode($result);
		}
		exit;
	}
	?>

	<link rel="stylesheet" href="<?php echo Router::url() ?>/include/codemirror/lib/codemirror.css">
    <link rel="stylesheet" href="<?php echo Router::url() ?>/include/codemirror/addon/display/fullscreen.css">
    <link rel="stylesheet" href="<?php echo Router::url() ?>/include/codemirror/addon/dialog/dialog.css">
    <link rel="stylesheet" href="<?php echo Router::url() ?>/include/codemirror/addon/search/matchesonscrollbar.css">
    <script src="<?php echo Router::url() ?>/include/codemirror/lib/codemirror.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/mode/javascript/javascript.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/mode/xml/xml.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/mode/css/css.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/mode/htmlmixed/htmlmixed.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/selection/active-line.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/edit/matchbrackets.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/edit/closebrackets.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/edit/closetag.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/display/fullscreen.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/scroll/annotatescrollbar.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/matchesonscrollbar.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/searchcursor.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/match-highlighter.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/fold/xml-fold.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/edit/matchtags.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/mode/overlay.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/dialog/dialog.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/searchcursor.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/search.js"></script>
    <script src="<?php echo Router::url() ?>/include/codemirror/addon/search/jump-to-line.js"></script>

	<div class="verical-full-height">
		<div class="editor-text">
			<div class="toolbar">
				<button onclick="save()";>Save</button>
				<button onclick="closeEditor();">Close</button>
				<span id="file-name"></span>
			</div>
			<textarea id=code style="height: 100%;"></textarea>
		</div>
        <div class="box">
            <div class="column" id="col1">
				FTP1
            </div>
            <div class="column" id="col2">
                FTP2
            </div>
        </div>
    </div>
	<script>
		var ftp, ftp2;
		function closeEditor() {
			$(".editor-text").hide();		
		}
		function save(){
			ftp.saveFile(editor.getValue(), function(){ $(".editor-text").hide(); });			
		}
		var editor;
        $(function(){
            ftp = new FTP("#col1");
            ftp2 = new FTP("#col2");
			
			ftp.onResize(function(width, height){
				if($(".CodeMirror").hasClass("CodeMirror-fullscreen")){
                    $(".adminheader").css("z-index", 1);
                }else{
                    $(".adminheader").css("z-index", 400);
                    $(".CodeMirror").css("width", 0);
                    $(".CodeMirror").css("height", 0);
                    $(".CodeMirror").css("width", width);
                    $(".CodeMirror").css("height", height - $(".toolbar").outerHeight() - 10);
                }
			});
			ftp.onOpenEditor(function(url, name, text){
				$(".editor-text").show();
				editor.setValue(text);
				$("#file-name").text(name);
			});

			editor = CodeMirror.fromTextArea(document.getElementById("code"), {
				lineNumbers: true,
				styleActiveLine: true,
				matchBrackets: true,
				autoCloseBrackets: true,
				autoCloseTags: true,
				mode: "text/html",
				extraKeys: {
					"F11": function(cm) {                    
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						resizeBrowser();
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						resizeBrowser();
					}
				},
				//highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true},
				matchTags: {bothTags: true},
				extraKeys: {
					"Ctrl-J": "toMatchingTag",
					"Alt-F": "findPersistent"
				}
			});
			editor.on('change', editor => {				
				//editorChange(editor.getValue())
			});

			setTimeout(function(){$(".editor-text").hide();}, 1);
        });
	</script>
	<style>.page.adminv2 .admin{padding-bottom: 0px !important;}</style>
	<?php
}
else if($action == "cookies"){
	$cookieText = $t->root->config->getD("cookie-text", t("This website uses cookies. By continuing to browse this site, you agree to their use."));
	$cookieAccept = $t->root->config->getD("cookie-text-accept", t("I accept"));
	$cookieAcceptShow = $t->root->config->getD("cookie-accept-show", 1);
	$cookieMore = $t->root->config->getD("cookie-more", "https://policies.google.com/technologies/cookies");
	$cookieNoJs = $t->root->config->getD("cookie-no-js", "window['ga-disable-GA_MEASUREMENT_ID'] = true;");
	$cookieJs = $t->root->config->getD("cookie-js", "cookieEnabled = true;");
	
	if(isset($_POST["update"])){
		$cookieText = $t->root->config->update("cookie-text", $_POST["cookie-text"]);
		$cookieAccept = $t->root->config->update("cookie-text-accept", $_POST["accept-text"]);
		$cookieAcceptShow = $t->root->config->update("cookie-accept-show", $_POST["cookie-accept-show"]);
		$cookieMore = $t->root->config->update("cookie-more", $_POST["cookie-more"]);
		$cookieNoJs = $t->root->config->update("cookie-no-js", $_POST["cookie-no-js"]);
		$cookieJs = $t->root->config->update("cookie-js", $_POST["cookie-js"]);
		$t->root->page->error_box(t("updated"), "ok", true);
	}	

	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\">";
						echo "<label><input type=radio name=cookie-accept-show value='1' ".Utilities::check($cookieAcceptShow == 1)."> ".t("Show the bar with the consent of cookies, and must be accepted")."</label><br/>";
						echo "<label><input type=radio name=cookie-accept-show value='2' ".Utilities::check($cookieAcceptShow == 2)."> ".t("Display a panel with information about cookies")."</label><br/>";
						echo "<label><input type=radio name=cookie-accept-show value='0' ".Utilities::check($cookieAcceptShow == 0)."> ".t("Don't show the cookie bar")."</label>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Text for cookie")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=cookie-text>".$cookieText."</textarea></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Accept button text")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=accept-text value='".$cookieAccept."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("More button link")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=cookie-more value='".$cookieMore."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Javascript code that will be executed without consent")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=cookie-no-js rows=3>".$cookieNoJs."</textarea></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Javascript code that will be executed with consent")."</label>";
					echo "<div class=\"col-sm-9\"><textarea name=cookie-js rows=3>".$cookieJs."</textarea></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("The name of cookies what will be created after accept")."</label>";
					echo "<div class=\"col-sm-9 form-static\"><span class=code>cookieAccept</span></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";				
			echo "</form>";
		echo "</div>";
		echo "<div class=right-side>";			
			echo "<div>";
				echo "<div class='card small' style='max-width:100%;'><div class=content><ul class=list>";
				echo "<li class=title><b>".t("Found cookies")."</b></li>";
				$result = dibi::query('SELECT * FROM :prefix:cookies');
				foreach ($result as $n => $row) {
					echo "<li>";
						echo "<div class=option>".Strings::str_time($row["created"])."</div>";				
						echo $row["name"];
						echo "<div class=small>";
							echo $row["location"];
						echo "</div>";
						echo "<div class=clear></div>";
					echo "</li>";
				}
				echo "</ul></div></div>";
			echo "</div>";
		echo "</div>";
	echo "</div>";	
}
elseif($action == "lock" and $user["id"] == $superuser){
	if(isset($_POST["update"])){
		$t->root->config->update("lock-enable", (isset($_POST["enable-lock"])?1:0));
		$t->root->config->update("lock-password", $_POST["lock-password"]);
		$t->root->page->error_box(t("updated"), "ok", true);
	}
	$enableLock = $t->root->config->getD("lock-enable", "0");
	$lockPassword = $t->root->config->getD("lock-password", "");

	$maintenance = false;
	if(file_exists(_ROOT_DIR."/maintenance.html")) {$maintenance = true;}	

	if(isset($_GET["maintenance"])) {
		$t->root->page->maintenanceMode(!$maintenance, "manualy by user");		
		header("location:?");
	}		

	echo "<h1>Lock</h1>";
	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<a class='button red' href='".Router::url()."adminv2/system/lock/?maintenance'>".t($maintenance?"Turn off maintenance mode":"Turn on maintenance mode")."</a>";
		
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\">";
						echo "<label><input type=checkbox name=enable-lock value='1' ".Utilities::check($enableLock == 1)."> ".t("Enable page lock")."</label>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Password")."</label>";
					echo "<div class=\"col-sm-9\">";
						echo "<input type=text class=form-controll name=lock-password value='".$lockPassword."'>";
					echo "</div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";
			echo "</form>";
		echo "</div>";
	echo "</div>";	
}
elseif($action == "cron" and $user["id"] == $superuser){

	if($_GET["__type"] == "ajax"){
        if($who == "show"){
			$id = $_GET["id"];
			$data = [];
			$result = dibi::query("SELECT * FROM :prefix:history WHERE parent=%s", "cron_log_".$_GET["id"], " ORDER BY date DESC LIMIT 20");
			foreach($result as $n => $cron) {
				$cronData = Config::sload($cron["data"]);

				$data[] = array(
					"id" => $cron["id"],
					"date" => Strings::str_time($cron["date"]),
					"data" => $cronData,
					"text" => $cron["text"],
					"type" => $cron["type"],
					"x" => is_array($cronData)
				);
			}

			echo json_encode($data);
        }
        exit();
    }

	if(isset($_POST["update"])){
		$t->root->config->update("cron-enable", (isset($_POST["enable-cron"])?1:0));
		$t->root->page->error_box(t("updated"), "ok", true);
	}
	$enableCron = $t->root->config->getD("cron-enable", "0");

	$lastCron = Utilities::getHistory("cron", "log", "start");

	$plugin = $t->root->module_manager->hook_call("cron.register", null, array(), false, true, true);            
	$jobs = $plugin["output"];

	if(count($lastCron) > 0) {
		$crons = $lastCron->fetchAll();
		$lastCron = $crons[0];
		$prevCron = $crons[1];
						
		$diff = Strings::time_difference($lastCron["date"], $prevCron["date"]);
		$minutes = $diff["minute"];
		$hours = $diff["hour"];

		if($hours > 0) {
			$timeTo = $hours." ".t("hours");
		}else{
			$timeTo = $minutes." ".t("minutes");
		}
	}

	echo "<div class=row>";
	echo "<div class='col-md-8 col-12'>";	
		echo "<div class=row>";
			echo "<div class='col-12 col-md-6 margin-card'>";		
				echo "<div class=card data-expandable='false'>";
					echo "<div class=title>".t("List of cron jobs")."</div>";
					echo "<div class=content id=cont1 style='max-height: 500px !important;overflow: auto;'>";
						echo "<ul class=list>";
							foreach($jobs as $job) {
								echo "<li onclick=\"loadLog('".$job["call"]."');return false;\">";
									echo "<div class=badge style='float: right;'>".t($job["every"])."</div>";
									echo "<div class=title>".$job["name"]."</div>";
									echo "<div class=desc>".$job["description"]."</div>";
								echo "</li>";
							}
						echo "</ul>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
			echo "<div class='col-12 col-md-6 margin-card'>";		
				echo "<div class=card data-expandable='false'>";
					echo "<div class=title>".t("Log")."</div>";
					echo "<div class=content id=cont2 style='max-height: 500px !important;overflow: auto;'>";
						echo "<ul class=list id='log'>";
							echo "<li><div class=desc>".t("Clic on cron job to see the logs")."</div></li>";
						echo "</ul>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
		echo "</div>";
		?>
		<script>
		function checkSize(){			
			setTimeout(function(){
				var c1 = $("#cont1");
				var c2 = $("#cont2");

				if(!isMobile()){			
					if(c2.outerHeight() > c1.outerHeight()) {
						c1.animate({"height": c2.outerHeight()}, 300);
					}
				}else{
					c1.css("height", "initial");
				}
				checkSize();
			}, 1000);
		}
		$(function(){
			checkSize();
		});
		function loadLog(parent) {
			$.getJSON('<?php echo Router::url()."adminv2/system/cron-show/"; ?>'+parent+'/?__type=ajax', function(data){
				$("#log").html("");
				if(data.length == 0) {
					$('#log').html("<li><div class=desc><?php echo t("No logs found"); ?></div></li>"); 
				}else{					
					for(var key in data) {
						var l = data[key];

						var li = $("<li></li>");
						li.on("click", function(){ showDialogLog($(this).data("logs")); });
						console.log(l.data);
						li.data("logs", l.data);
						var time = $("<div class='badge' style='float: right;'></div>");
						time.html(l.date);
						li.append(time);
						var name = $("<div class=title></div>");
						name.html(l.text);
						li.append(name);
						$("#log").append(li);
					}
				}
			});
		}
		var dialogLog = null;
		$(function(){   
			dialogLog = new Dialog(800);
			dialogLog.setTitle("Logs");
			dialogLog.setButtons(Dialog.CLOSE);     
			var btn = dialogLog.getButtons();
			$(btn[0]).click(function(){ dialogLog.Close(); }); 
		});
		function showDialogLog(logs){
			dialogLog.html.dialogHtml.html("");

			if(typeof logs.output != "undefined") {
				var div = $("<ul class=list></div>");
				for(var key in logs.output) {
					var log = logs.output[key];
					var li = $("<li></li>");
					var logli = $("<div class=lilog></div>");
					
					var time = $("<div class=time></div>");
					var date = new Date(log.time * 1000);
					time.html(date.toLocaleTimeString());
					logli.append(time);

					var text = $("<div class=text></div>");
					text.html(log.text);
					logli.append(text);

					li.append(logli);
					div.append(li);
				}
				
				dialogLog.html.dialogHtml.append(div);
			}else{
				dialogLog.html.dialogHtml.append(logs);
			}

			dialogLog.Show();
		}
		</script>
		<?php
		echo "</div>";
		echo "<div class='col-md-4 col-12'>";		
			echo "<h2>".t("cron settings")."</h2>";
			echo "<form method=post><table class=tabfor style='width:700px;'>";
				echo "<tr><td></td><td><label><input type=checkbox name=enable-cron value='1' ".Utilities::check($enableCron)."> ".t("enable cron")."</label></td></tr>";
				echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></td></tr>";
			echo "</table>";
			echo "</form>";

			if(count($lastCron) > 0) {						
				echo "<br>";
				$t->root->page->error_box(t("Last cron run").": ".Strings::str_time($lastCron["date"]).". Rozmezí: ".$timeTo, "ok", false);
			}

			$cron = Bootstrap::getContainer()->get('cron');
			$hash = $cron->getHash();
			echo "<div style='margin-top:10px;'>".t("Cron address for run")."</div>";
			echo "<div><a href='".Router::url()."cron/".$hash."' target=_blank>/cron/".$hash."</a></div>";
			echo "<div class=desc>".t("if you change superuser the url will be changed too")."</div>";
		echo "</div>";
	echo "</div>";
}
elseif($action == "login"){
	if(isset($_POST["update"])){
		$t->root->config->update("ttl", $_POST["ttl"]);
		$t->root->config->update("tts", $_POST["tts"]);
		$t->root->config->update("onlyttl", (isset($_POST["onlyttl"])?1:0));
		$t->root->page->error_box(t("updated"), "ok", true);
	}

	$ttl = $t->root->config->getD("ttl", "+24 hour");
	$tts = $t->root->config->getD("tts", "+8 hour");
	$onlyttl = $t->root->config->getD("onlyttl", 0);
	echo "<div class=content>";
		echo "<div class=left-side>";
			echo "<form method=post>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Time of long login")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=ttl value='".$ttl."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\">".t("Time of short login")."</label>";
					echo "<div class=\"col-sm-9\"><input type=text class='form-control' name=tts value='".$tts."'></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><label><input type=checkbox name=onlyttl value='1' ".Utilities::check($onlyttl)."> ".t("only ttl")."</label></div>";
				echo "</div>";
				echo "<div class=\"form-group row mb-2\">";
					echo "<label class=\"col-sm-3 col-form-label\"></label>";
					echo "<div class=\"col-sm-9\"><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></div>";
				echo "</div>";
			echo "</form>";
		echo "</div>";
	echo "</div>";
}
elseif($action == "variables" and $user["id"] == $superuser){	
	/*echo "<div class=hide-mobile>";
		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=100>".t("name")."</th><th width=200>".t("value")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:settings');
		foreach ($result as $n => $row) {		
			echo "<tr>";
				echo "<td valign=top><b>".$row["name"]."</b></td>";
				echo "<td>".str_replace("\n", "<br>", $row["value"])."</td>";
			echo "</tr>";
		}
		echo "</table>";
	echo "</div>";*/
	echo "<div>";
		echo "<div class='card small'><div class=content><ul class=list>";
		$result = dibi::query('SELECT * FROM :prefix:settings');
		foreach ($result as $n => $row) {
			echo "<li>";
				echo "<div class=option>".str_replace("\n", "<br>", $row["value"])."</div>";				
				echo $row["name"];
				echo "<div class=clear></div>";
			echo "</li>";
		}
		echo "</ul></div></div>";
	echo "</div>";
}
elseif($action == "redirecting"){
	if($who == "edit"){ 
		echo "<h1>".t("Editing redirect")."</h1>";		

		$result = dibi::query("SELECT * FROM :prefix:redirecting WHERE id=%i", $t->router->_data["id"][0])->fetch();
		if($result == NULL){
			$t->root->page->draw_error(t("called item does not exist!"));
		}else{
			if(isset($_POST["update"])){
				$pole = array(
							"name" => $_POST["name"],
							"_from" => $_POST["from"],
							"_to" => $_POST["to"],
							"minop" => $_POST["minop"],
							"active" => isset($_POST["active"]),
							"redirect" => isset($_POST["redirect"]),
						);
		
				dibi::query('UPDATE :prefix:redirecting SET ', $pole, "WHERE `id`=%s", $t->router->_data["id"][0]);
				header("location: ".$t->router->url_."adminv2/system/redirecting-edit/".$t->router->_data["id"][0]."/ok");
			}

			if($t->router->_data["state"][0] == "ok"){
				$t->root->page->error_box(t("redirecting was updated"), "ok", true);
			}
		
			echo "<form method=post><table class=tabfor style='width:700px;'>";
				echo "<tr><td width=120><label>".t("Name")."</label></td><td width=330><input type=text name=name value='".$result["name"]."'></td></tr>";
				echo "<tr><td><label>".t("From")."</label></td><td><input type=text name=from value='".$result["_from"]."'></td></tr>";
				echo "<tr><td></td><td><pre>".t("For example").": ".htmlentities("<module>[/<id>][/<page=1>]")."</pre></td></tr>";
				echo "<tr><td><label>".t("To")."</label></td><td><input type=text name=to value='".$result["_to"]."'></td></tr>";
				echo "<tr><td></td><td><pre>".t("For example").": ".htmlentities("module=<module>&id=<id>&page=<page>")."</pre></td></tr>";
				echo "<tr><td><label>".t("Minimal permission").": </label></td><td>";
					echo "<select id=permission name=minop style='width:300px;'>";
					$resul_ = dibi::query('SELECT * FROM :prefix:permission ORDER BY level DESC');
					foreach ($resul_ as $n => $row) {
						echo "<option value='".$row["id"]."' ".($row["id"] == $result["minop"]?"selected":"").">".$row["name"]." (Level: ".$row["level"].")</option>";
					}
					echo "<option value=0  ".(0 == $result["minop"]?"selected":"").">Neregistrovaný uživatel</option></select>";
				echo "</td></tr>";
				echo "<tr><td></td><td><label><input type=checkbox name=redirect value='1' ".Utilities::check($result["redirect"])."> ".t("Redirect")."</label></td></tr>";
				echo "<tr><td></td><td><label><input type=checkbox name=active value='1' ".Utilities::check($result["active"])."> ".t("Active")."</label></td></tr>";
				echo "<tr><td></td><td><input type=submit class='blue button' name=update value='".t("edit")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}elseif($who == "new"){
		$data = array(
			"name" 		=> t("New redirecting"),
			"active"	=> 0
		);
		$result = dibi::query('INSERT INTO :prefix:redirecting', $data);

		header("location: ".$t->router->url_."adminv2/system/redirecting-edit/".dibi::InsertId());
	}elseif($who == "delete"){
		$result = dibi::query('DELETE FROM :prefix:redirecting WHERE id=%i', $_GET["id"]);
		header("location: ".$t->router->url_."adminv2/system/redirecting/");
	}else{
		echo "<div class=bottommenu><a href='".$t->router->url_."adminv2/system/redirecting-new/'>".t("new redirecting")."</a></div>";
		echo "<h1>".t("Redirecting settings")."</h1>";
		echo "<span class=desc>".t("These redirects are preceded by other modular and system redirects, in addition to ajax")."</span>";

		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=10>ID</th><th width=250>".t("name")."</th><th width=200>".t("from")."</th><th width=200>".t("to")."</th><th width=120>".t("action")."</th></tr>";
		$result = dibi::query('SELECT * FROM :prefix:redirecting ORDER BY id DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				echo "<td>".$row["id"]."</td>";
				echo "<td>".$row["name"]."".($row["active"] == 0?" <span class=desc>".t("not active")."</span>":"")."</td>";
				echo "<td>".$row["_from"]."</td>";
				echo "<td>".$row["_to"]."</td>";				
				echo "<td valign=top>";
					echo "<a href='".$t->router->url_."adminv2/system/redirecting-edit/".$row["id"]."/' class=xbutton><i class=\"fas fa-pencil-alt\"></i> ".t("edit")."</a>";
					echo " <a href='".Router::url()."adminv2/system/redirecting-delete/".$row["id"]."/'><i class=\"far fa-trash-alt\"></i> ".t("delete")."</a>";
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
elseif($action == "show"){
	if(isset($_POST["update"])){
		$pole = array(
					"comment-max-url" => $_POST["maxurl"],
					"comment-timeout" => $_POST["timeout"]
				);

		foreach($pole as $key => $value){
			dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
			$t->root->config->update($key, $value);
		}
		$t->root->page->error_box(t("the changes have been saved."), "ok", true);
	}

	if($t->root->config->get("comment-max-url") == null) $t->root->config->set("comment-max-url", "2");
	if($t->root->config->get("comment-timeout") == null) $t->root->config->set("comment-timeout", "20");

	echo "<h1>".t("Article settings")."</h1>";
	echo "<form method=post><table class=tabfor style='width:700px;'>";
	echo "<tr><td width=280><label>".t("Maximum number of links")."</label></td><td width=330><input type=text name=maxurl value='".$t->root->config->get("comment-max-url")."'></td><td width=20></td></tr>";
	echo "<tr><td></td><td><span class=desc>".t("in one comment post")."</span></td></tr>";
	echo "<tr><td><label>".t("Timeout in posting comments")."</label></td><td width=430><input type=text data-postfix='".t("second")."' class=price placeholder=0 name=timeout value='".$t->root->config->get("comment-timeout")."'></td></tr>";
	echo "<tr><td></td><td><span class=desc>".t("only for not loget users")."</span></td></tr>";
	echo "<tr><td></td><td><input type=submit class='btn btn-primary' name=update value='".t("edit")."'></td></tr>";
	echo "</table>";
	echo "</form>";
}else if($action == "emails"){
	if($who == "text"){
		$result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $t->router->_data["id"][0])->fetch();
		echo $result["message"];
	}
	else if($who == "show"){
		$result = dibi::query("SELECT * FROM :prefix:emails WHERE id=%i", $t->router->_data["id"][0])->fetch();
		echo "<h1>".$result["subject"]."</h1>";
		$user = User::get($result["user"], true);
		echo "<b>Odesláno</b> ".Strings::str_time($result["time"])."<br>";
		echo "<b>Přihlášený uživatel</b> ".$user["login"];
		echo "<div style='padding:4px;'></div>";
		echo "<a href=# onclick=\"$('#ifp').animate({width: '100%'}, 500, function(){});return false;\">Web</a> | <a href=# onclick=\"$('#ifp').animate({width: '450px'}, 500, function(){});return false;\">Mobile</a>";
		echo "<div style='background: white;padding: 13px;border: 1px solid silver;'>";		
			echo "<iframe style='width:100%;height:550px;border: 0px;margin:0px auto;display:block;' id=ifp src='".Router::url()."/adminv2/system/emails-text/".$result["id"]."/?__type=ajax'></iframe>";
		echo "</div>";
	}else{
		echo "<h1>".t("Emails settings")."</h1>";

		if(isset($_POST["change"])){
			$pole = array(
						"email-enable" => (isset($_POST["enable_email"]) and $_POST["enable_email"] == 1?1:0),
						"email-webmaster" => $_POST["email"]
					);

			foreach($pole as $key => $value){
				dibi::query('UPDATE :prefix:settings SET ', array("value" => $value), "WHERE `name`=%s", $key);
				$t->root->config->update($key, $value);
			}
			$t->root->page->error_box(t("the changes have been saved."), "ok", true);
		}

		if($t->root->config->get("email-webmaster") == null) $t->root->config->set("email-webmaster", User::get(1)["email"]);
		$em = $t->root->config->get("email-webmaster");

		echo "<form action=# method=post><input type=checkbox value=1 name=enable_email ".Utilities::check($t->root->config->getD("email-enable", "1"))."> Povolit odesílání emailu (Jinak se bude ukládat pouze kopie zde)";
		echo "<br>Hlavní email: <input type=text name=email value='".$em."'> <input type=submit class='btn btn-primary' name=change value='Upravit'></form>";

		$paginator = new Paginator(15, Router::url()."adminv2/system/emails/?page=(:page)");
		$result = $paginator->query('SELECT * FROM :prefix:emails ORDER BY time DESC');

		echo $paginator->getPaginator();

		echo "<table class='table table-sm tablik'>";
		echo "<tr><th width=30>ID</th><th width=150>".t("from")."</th><th width=150>".t("to")."</th><th width=400>".t("subject")."</th><th width=120>".t("action")."</th></tr>";
		//$result = dibi::query('SELECT * FROM :prefix:emails ORDER BY time DESC');
		foreach ($result as $n => $row) {
			echo "<tr>";
				echo "<td>".$row["id"]."</td>";
				echo "<td>".$row["_from"]."</td>";
				echo "<td>".$row["_to"]."</td>";
				echo "<td>".$row["subject"];
					$user = User::get($row["user"], true);
					echo "<div style='font-size:12px;'>".$user["nick"]." ( ".Strings::str_time($row["time"])." )</div>";
				echo "</td>";
				echo "<td valign=top>";
					echo "<a href='".$t->router->url_."adminv2/system/emails-show/".$row["id"]."/'>".t("show")."</a>";
				echo "</td>";
			echo "</tr>";
		}
		echo "</table>";

		echo $paginator->getPaginator();
	}
}
