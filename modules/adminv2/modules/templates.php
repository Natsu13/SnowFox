<?php
$action = $t->router->_data["action"][0];
$who = $t->router->_data["who"][0];
$user = User::current();

if($_GET["__type"] != "ajax"){
	echo "<ul class='menu sub'>";
        echo "<li ".($action == "show"?"class=select":"")."><a href='".$t->router->url_."adminv2/templates/'>".t("list")."</a></li>";
        echo "<li ".($action == "templates"?"class=select":"")."><a href='".$t->router->url_."adminv2/templates/templates/'>".t("templates")."</a></li>";
	echo "</ul>";
}

if(!file_exists(_ROOT_DIR . "/views/_templates/")){
    mkdir(_ROOT_DIR . "/views/_templates/", 0777);
}

if($action == "templates"){
    if(isset($_GET["load"])) {        
        echo json_encode(array("text" => file_get_contents(_ROOT_DIR . "/views/".$_GET["load"])));
        exit;
    }
    if(isset($_POST["save"])) {        
        file_put_contents(_ROOT_DIR . "/views/".$_POST["save"], $_POST["text"]);
        echo json_encode(array("ok" => "Saved"));
        exit;
    }
    if(isset($_POST["createfile"])){
        $file = $_POST["createfile"].".view";
        if(file_exists(_ROOT_DIR . "/views/".$file)){
            echo json_encode(array("error" => "File alerady exists!"));
            exit;
        }
        $f = fopen(_ROOT_DIR . "/views/".$file,"w");
        fclose($f);
        echo json_encode(array("ok" => "Saved", "file" => explode("/", $file)[1], "full" => $file));
        exit;
    }
    echo "<div class='content padding'>";
        echo "<div class='browser'>";
            echo "<div class=left-menu>";
            echo "<div class=search-holder>";
                echo "<input type=text class=search-input placeholder='".t("Search files")."...'>";
            echo "</div>";
            echo "<ul class=file-list>";
            $base_dir = _ROOT_DIR."/views/";
            $i = 0;
            foreach(scandir($base_dir) as $file) {
                if($file == '.' || $file == '..' || $file == "_templates") continue;
                $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
                if(is_dir($dir)) {
                    $i++;
                    $sub_dir = $base_dir.$file."/";
                    echo "<li class=folder>";
                        echo "<div onclick=\"$(this).parent().toggleClass('open');return false;\">";
                            echo "<a href=# class=add-btn onclick=\"addFile($('#file-list-".$i."'), '".$file."');event.stopPropagation();return false;\"><i class=\"fas fa-plus\"></i></a>";
                            echo $file;                             
                        echo "</div>";
                        echo "<ul class='file-list file-list-sub' id='file-list-".$i."'>";                        
                        foreach(scandir($sub_dir) as $sfile) {
                            if($sfile == '.' || $sfile == '..') continue;
                            $sdir = $sub_dir.DIRECTORY_SEPARATOR.$sfile;
                            if(is_file($sdir)) {
                                echo "<li class=file onclick=\"openEditor('".$file."/".$sfile."');return false;\">";
                                    echo "<div>".$sfile."</div>";
                                echo "</li>";
                            }
                        }
                        echo "</ul>";
                    echo "</li>";
                }
            }
            echo "</ul>";

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
            <?php
            echo "</div>";
            echo "<div class=content>";
                echo "<ul class=top-tabs>";
                    
                echo "</ul>";
                echo "<div class=edit-code>";
                    echo "<div class=file-prop>";
                        echo "<div class=file-prop-name>test.view</div>";
                        echo "<div class=file-prop-buttons>";
                            echo "<button class='btn btn-small btn-modern btn-default' id=helpbtn>?</button>";
                            echo "<button class='btn btn-small btn-modern btn-danger'>Delete</button>";
                            echo "<button class='btn btn-small btn-modern btn-default'>Rename</button>";
                            echo "<button class='btn btn-small btn-modern btn-primary btn-disabled' onclick=\"save();\" id=save-btn>Save</button>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class=file-content>";
                        echo "<textarea id=code></textarea>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    ?>
    <style>
        .cm-view {color: #4a4a4a;}
    </style>
    <script>
        $("#helpbtn").click(function(){
            var text = "<h4>Shortcut</h4>";
            text+= "<b>Ctrl-J</b> Jump to matching tag<br>";
            text+= "<b>Ctrl-F</b> Start searching<br>";
            text+= "<b>Ctrl-G</b> Find next<br>";
            text+= "<b>Shift-Ctrl-G</b> Find previous<br>";
            text+= "<b>Shift-Ctrl-F</b> Replace<br>";
            text+= "<b>Alt-F</b> Persistent search (dialog doesn't autoclose, enter to find next, Shift-Enter to find previous)<br>";
            text+= "<b>Alt-G</b> Jump to line<br>";
            messageBox("Help", text);
        });
        CodeMirror.defineMode("view", function(config, parserConfig) {
            var viewOverlay = {
                token: function(stream, state) {
                    var ch;
                    //console.log("xxx");
                    if (stream.match("{")) {                        
                        while ((ch = stream.next()) != null)
                            if (ch == "}" /*&& stream.next() == "}"*/) {
                                //stream.eat("}");
                                return "view";
                            }
                    }
                    while (stream.next() != null && !stream.match("{", false)) {}
                    return null;
                }
            };
            return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/html"), viewOverlay);
        });
        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
            lineNumbers: true,
            styleActiveLine: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            autoCloseTags: true,
            mode: "view",
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
            if(skipEdit) {
                skipEdit = false;
                return;
            }
            editorChange(editor.getValue())
        });
        
        var editorOpened = [];

        function resizeBrowser(){
            if(editorOpened.length == 0){
                $(".browser").css("height", $(window).outerHeight()- 200);
                $(".edit-code").hide();
            }else{
                if($(".CodeMirror").hasClass("CodeMirror-fullscreen")){
                    $(".adminheader").css("z-index", 1);
                }else{
                    $(".adminheader").css("z-index", 400);
                    $(".edit-code").show();
                    $(".top-tabs").css("width", 0);
                    $(".CodeMirror").css("width", 0);
                    $(".CodeMirror").css("height", 0);
                    var fc = $(".file-content");
                    $(".CodeMirror").css("width", fc.outerWidth());
                    $(".CodeMirror").css("height", fc.outerHeight());
                    $(".browser").css("height", $(window).outerHeight()- 200);
                    $(".top-tabs").css("width", fc.outerWidth());
                }
            }
        }
        resizeBrowser();
        $(window).on("resize", function(){ resizeBrowser(); });
        setTimeout(function(){ resizeBrowser(); }, 10000);

        var openedEditor = -1;
        var lastOpen = -1;
        var editorId = 0;
        var skipEdit = true;
        var saveScroll = 0;
        function openEditor(file){
            var file = file;

            if(editorOpened.some(function(x){ return x.file == file; })) {
                var editor = editorOpened.find(function(x){ return x != null && x.file == file; });
                openedEditor = editor.id;
                renderTabs();                
                resizeBrowser();
                return;
            }
            
            $.getJSON("<?php echo Router::url()."adminv2/templates/templates/?__type=ajax"; ?>", { load: file })
            .done(function( data ) {
                var text = data.text;                

                editorId++;
                var model = {
                    content: text,
                    old: text,
                    file: file,
                    name: file.split("/")[1],
                    id: editorId,
                    tab: null,
                    document: CodeMirror.Doc(text, "view")
                };

                openedEditor = model.id;
                editorOpened[model.id] = model;
                skipEdit = true;
                
                saveScroll = $(".top-tabs").scrollLeft();
                renderTabs();
                $(".top-tabs").scrollLeft(saveScroll);
                $(".top-tabs").animate({scrollLeft: $(".top-tabs").outerWidth()}, 500);
                resizeBrowser();
            }); 
        }

        var lastEditor = null;
        function renderTabs(){
            saveScroll = $(".top-tabs").scrollLeft();

            $(".top-tabs").html("");
            var saveScrollTo = null;
            for(var key in editorOpened) {                
                var _editor = editorOpened[key];
                var tab = $("<li class=top-tab></li>");
                if(_editor.id == openedEditor) {
                    tab.addClass("active");
                    $(".file-prop").show();

                    if(lastOpen != openedEditor){
                        $(".file-prop-name").text(_editor.name);
                        skipEdit = true;
                        //editor.setValue(_editor.content);
                        var buf = _editor.document;
                        if (buf.getEditor()) buf = buf.linkedDoc({sharedHist: true});
                        var old = editor.swapDoc(buf);
                        var linked = old.iterLinkedDocs(function(doc) {linked = doc;});
                        if (linked) {
                            lastEditor.document = linked;
                            old.unlinkDoc(linked);                            
                        }
                        editor.focus();
                        
                        setTimeout(function() {
                            editor.refresh();
                            editor.focus();
                        },1);

                        lastOpen = openedEditor;

                        $(".top-tabs").scrollLeft(saveScroll);
                        if(_editor.tab != null){
                            saveScrollTo = tab;                           
                        }

                        lastEditor = _editor;
                    }                    
                }    

                if(_editor.content != _editor.old) {
                    tab.addClass("changed");
                    $("#save-btn").removeClass("btn-disabled");
                }else{
                    tab.removeClass("changed");
                    $("#save-btn").addClass("btn-disabled");
                }            
                
                var title = $("<div class=tab-title></div>");
                title.text(_editor.name);
                tab.append(title);
                var a = $("<a href=#></a>");
                a.html("<i class=\"fas fa-times\"></i>");
                a.data("id", _editor.id);
                a.click(function(){
                    removeTab($(this).data("id"));
                });
                tab.append(a);
                tab.data("id", _editor.id);
                tab.on("click", function(){
                    switchTab($(this).data("id"));
                });
                _editor.tab = tab;
                $(".top-tabs").append(tab);                
            }

            if(saveScrollTo != null){
                $(".top-tabs").scrollLeft(saveScroll);                
                scroll = saveScrollTo.offset().left - $(".top-tabs").offset().left; 
                $(".top-tabs").animate({scrollLeft: scroll}, 500);
            }
        }

        function switchTab(id){
            openedEditor = id;
            renderTabs();
        }

        function editorChange(text){
            editorOpened[openedEditor].content = text;
            renderTabs();
        }

        function save(){
            var file = editorOpened[openedEditor].file;
            var text = editorOpened[openedEditor].content;
            editorOpened[openedEditor].old = editorOpened[openedEditor].content;
            renderTabs();

            $.post("<?php echo Router::url()."adminv2/templates/templates/?__type=ajax"; ?>", { save: file, text: text })
            .done(function( data ) {
                var data = JSON.parse(data);
                NotificationCreate("", "The changes have been saved.", "#", "ok");
            });
        }

        function addFile(elem, folder){
            var elem = elem;
            var folder = folder;
            inputBox("New file", "Enter name for new file", "new-file", function(value){
                $.post("<?php echo Router::url()."adminv2/templates/templates/?__type=ajax"; ?>", { createfile: folder+"/"+value })
                .done(function( data ) {
                    var data = JSON.parse(data);

                    if(data.ok){
                        NotificationCreate("", "The file has been created.", "#", "ok");

                        var f = $("<li class=file></li>");
                        var fd = $("<div></div>");
                        fd.html(data.file);
                        f.append(fd);
                        f.data("file", data.full);
                        f.click(function(e){
                            openEditor($(this).data("file"));
                            e.preventDefault();
                        });
                        elem.append(f);
                    }else{
                        NotificationCreate("", data.error, "#", "error");
                    }
                });
            }, function(){});
        }
    </script>
    <?php
}
elseif($action == "delete"){
    dibi::query('DELETE FROM :prefix:templates WHERE `id`=%s', $t->router->_data["id"][0]);
    header("location:".$t->router->url_."adminv2/templates/");
}elseif($action == "edit") {        
    $result = dibi::query('SELECT * FROM :prefix:templates WHERE id = %i', $t->router->_data["id"][0])->fetch();
    if($result == null){
        header("location:".$t->router->url_."adminv2/templates/");
        exit;
    }

    $plugin = $t->root->module_manager->hook_call("templates.types", null, array(), false, true, true);            
    $templates = $plugin["output"];
    $dataLinq = new LinQ($templates);
    $data = $dataLinq->FirstOrNull(function($e) use($result){ return $e["code"] == $result["code"]; });    

    if(isset($_GET["preview"])){
        ob_start();
        if(file_exists(_ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$_GET["rand"].".view")){
            $t->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$_GET["rand"].".view", $data["dummy"]);
            unlink(_ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$_GET["rand"].".view");
        }else{
            $t->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", $data["dummy"]);
        }
        $text = ob_get_contents();
        ob_end_clean();
        echo $text;
        exit;
    }
    if(isset($_GET["saveDraft"])) {
        file_put_contents(_ROOT_DIR . "/views/_templates/".$result["hash"]."-temp-".$_GET["rand"].".view", $_POST["text"]);
        echo json_encode(array("ok" => true));
        exit;
    }
    if(isset($_POST["rebuild"])) {
        $t->root->page->template_parse(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", null, true);        
        header("location:".$t->router->url_."adminv2/templates/edit/".$_GET["id"]."?ok");
    }
    if(isset($_POST["update"])){
        file_put_contents(_ROOT_DIR . "/views/_templates/".$result["hash"].".view", $_POST["template"]);
        $update = array(
            "name" => $_POST["name"],
            "code" => $_POST["code"]
        );
        dibi::query('UPDATE :prefix:templates SET ', $update, "WHERE `id`=%i", $_GET["id"]);
        header("location:".$t->router->url_."adminv2/templates/edit/".$_GET["id"]."?ok");
    }

    if(isset($_GET["ok"])){
        $t->root->page->error_box(t("The changes have been saved."), "ok", true);
    }

    if(!file_exists(_ROOT_DIR . "/views/_templates/".$result["hash"].".view")){
        $f = fopen(_ROOT_DIR . "/views/_templates/".$result["hash"].".view","w");
        fclose($f);
    }
    $code = file_get_contents(_ROOT_DIR . "/views/_templates/".$result["hash"].".view");
    $rand = Strings::random(10);
    ?>
    <div id="window_preview" class="width-700" style="display:none;">
        <div style="padding: 8px 2px;font-size: 14px;border-top: 1px solid #e2e2e2;">Subject <b id="subject"></b></div>
        <iframe style="width:100%;height:500px;border: 1px solid silver;background:white;" id="preview-iframe"></iframe>
    </div>
    <script>
    var dialogPreview;
    $(function(){
        dialogPreview = new Dialog();
        dialogPreview.setTitle("Preview");
        dialogPreview.setButtons(Dialog.CLOSE);     
        var btn = dialogPreview.getButtons();
        $(btn[0]).click(function(){ dialogPreview.Close(); });
        dialogPreview.html.dialogHtml.append($("#window_preview"));   
        $("#window_preview").show(); 
        
        $("#btn-cat-preview").on("click", function(e) {            
            e.preventDefault();
            $("#subject").html("<?php echo $result["name"]; ?>");            
            dialogPreview.ShowLoading();
            $.post("<?php echo Router::url()."adminv2/templates/edit/".$result["id"]."/?__type=ajax&saveDraft&rand=".$rand; ?>", { text: $("#templt").val() })
            .done(function( data ) {
                $("#preview-iframe").attr("src", "<?php echo Router::url()."adminv2/templates/edit/".$result["id"]."/?__type=ajax&preview&rand=".$rand; ?>");
                setTimeout(function(){dialogPreview.Show();}, 200);
            });            
        });  
    });
    </script>    
    <?php
    echo "<div class='content padding'>";
        echo "<div class=left-side>";
            echo "<form method=post action=# id='edit'>";
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Name")."</label><div class=\"col-sm-9\">";
                    echo "<input type=text class=\"form-control\" value='".$result["name"]."' name=name>";
                echo "</div></div>";         
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Code")."</label><div class=\"col-sm-9\">";
                    echo "<input type=text class=\"form-control\" value='".$result["code"]."' name=code>";
                echo "</div></div>";    
                echo "<div class=\"form-group row mb-2\"><label class=\"col-sm-3 col-form-label\">".t("Template")."</label><div class=\"col-sm-9\">";                
                    echo "<textarea class=\"form-control\" name=template id=templt rows=10>".$code."</textarea>";
                echo "</div></div>"; 
                echo "<div class=\"form-group row mb-2\"><label class='d-none d-sm-inline col-sm-3'></label><div class='col-12 col-sm-9'>";
                    echo "<input type=submit class='blue btn btn-primary' name=update id='btn-cat-update' value='".t("edit")."'> ";
                    if($data != null){
                        echo "<input type=submit class='blue btn btn-primary' name=update id='btn-cat-preview' value='".t("preview")."'> ";
                    }
                    echo "<input type=submit class='blue btn btn-secondary' name=rebuild id='btn-rebuild' value='".t("rebuild")."'>";
                echo "</div></div>";	
            echo "</form>";
        echo "</div>";
        echo "<div class=right-side>";
            echo "<div class=\"expandable\" id=\"cat_1\">";
                echo "<div class=\"title_admin\">";
                    echo "<a href=\"#\" onclick=\"showhideclass('#cat_1', 'closed');return false;\">Informace</a>";
                echo "</div>";
                if($data != null){
                    echo "<div>".$data["description"]."</div>";
                }else{
                    echo "<div>Unkown template code</div>";
                }
            echo "</div>";
        echo "</div>";
    echo "</div>";
}elseif($action == "new") {
    $data = array(
        "name" 		=> t("new template"),
        "created"	=> time(),
        "author"	=> $user["id"],
        "hash" 	    => sha1(time() + Strings::random(10))
    );
    $result = dibi::query('INSERT INTO :prefix:templates', $data);
    header("location:".$t->router->url_."adminv2/templates/edit/".dibi::InsertId());
}else{

    $plugin = $t->root->module_manager->hook_call("templates.types", null, array(), false, true, true);            
	$templates = $plugin["output"];

    echo "<div class=bottommenu><a href='".Router::url()."adminv2/templates/new/'>".t("new template")."</a></div>";
	echo "<table class='tablik'>";
	echo "<tr><th width=350>".t("Name")."</th><th width=250>".t("Code")."</th><th width=150>".t("Author")."</th><th width=120>".t("Action")."</th></tr>";
	$result = dibi::query('SELECT * FROM :prefix:templates');
	foreach ($result as $n => $row) {
		echo "<tr><td><b>".$row["name"]."</b> <i>".Strings::str_time($row["created"])."</i></td><td>".$row["code"]."</td><td>";
		$user = User::get($row["author"]);
		echo "".$user["nick"]."</td><td>";
			echo "<a href='".$t->router->url_."adminv2/templates/edit/".$row["id"]."'><i class=\"fas fa-pencil-alt\"></i> ".t("Edit")."</a> <a href='".$t->router->url_."adminv2/templates/delete/".$row["id"]."'><i class=\"fas fa-trash-alt\"></i> ".t("Delete")."</a>";
		echo "</td></tr>";
	}
	echo "</table>";
}