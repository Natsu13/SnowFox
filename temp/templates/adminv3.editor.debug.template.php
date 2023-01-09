<div  class="row"><div  class="col-md-12"><div  class="card no-padding"><div  class='browser'><div  class="left-menu"><div  class="search-holder"><input  type="text"  class="search-input"  placeholder='<?php echo t("Search files"); ?>...' /></div> <ul  class="file-list"> <?php foreach($model["dirs"] as $key => $dir) { ?> <?php $index = $dir['index']; ?><li  class="folder"><div  onclick="$(this).parent().toggleClass('open');return false;"><a  href="#"  class="add-btn"  onclick="addFile($('#file-list-<?php echo $index; ?>'), '<?php echo $key; ?>'); event.stopPropagation(); return false;"><i  class="fas fa-plus"></i> </a>  <?php echo $key; ?></div> <ul  class='file-list file-list-sub'  id='file-list-<?php echo $index; ?>'> <?php foreach($dir["files"] as $skey => $file) { ?><li  class="file"  onclick="openEditor('<?php echo $key; ?>/<?php echo $file; ?>');return false;"><div><?php echo $file; ?></div></li>  <?php } ?></ul> </li>  <?php } ?></ul> </div> <div  class="content"><ul  class="top-tabs"></ul> <div  class="edit-code"><div  class="file-prop"><div  class="file-prop-name">test.view</div> <div  class="file-prop-buttons"><button  class='btn btn-small btn-modern btn-default'  id="helpbtn">?</button> <button  class='btn btn-small btn-modern btn-danger'>Delete</button> <button  class='btn btn-small btn-modern btn-default'>Rename</button> <button  class='btn btn-small btn-modern btn-primary btn-disabled'  onclick="save();"  id="save-btn">Save</button> </div> </div> <div  class="file-content"><textarea  id="code"></textarea> </div> </div> </div> </div> </div> </div> </div> <link  rel="stylesheet"  href="<?php echo Router::url(); ?>include/codemirror/lib/codemirror.css" /><link  rel="stylesheet"  href="<?php echo Router::url(); ?>include/codemirror/addon/display/fullscreen.css" /><link  rel="stylesheet"  href="<?php echo Router::url(); ?>include/codemirror/addon/dialog/dialog.css" /><link  rel="stylesheet"  href="<?php echo Router::url(); ?>include/codemirror/addon/search/matchesonscrollbar.css" /><script  src="<?php echo Router::url(); ?>include/codemirror/lib/codemirror.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/mode/javascript/javascript.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/mode/xml/xml.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/mode/css/css.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/mode/htmlmixed/htmlmixed.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/selection/active-line.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/edit/matchbrackets.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/edit/closebrackets.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/edit/closetag.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/display/fullscreen.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/scroll/annotatescrollbar.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/matchesonscrollbar.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/searchcursor.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/match-highlighter.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/fold/xml-fold.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/edit/matchtags.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/mode/overlay.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/dialog/dialog.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/searchcursor.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/search.js"></script> <script  src="<?php echo Router::url(); ?>include/codemirror/addon/search/jump-to-line.js"></script> <style> .cm-view {
        color: #008ee1;
        font-weight: bold;
    }</style><script> var editorOpened = [];

    $("#helpbtn").click(function () {
        var text = "<h4>Shortcut</h4>";
        text += "<b>Ctrl-J</b> Jump to matching tag<br>";
        text += "<b>Ctrl-F</b> Start searching<br>";
        text += "<b>Ctrl-G</b> Find next<br>";
        text += "<b>Shift-Ctrl-G</b> Find previous<br>";
        text += "<b>Shift-Ctrl-F</b> Replace<br>";
        text += "<b>Alt-F</b> Persistent search (dialog doesn't autoclose, enter to find next, Shift-Enter to find previous)<br>";
        text += "<b>Alt-G</b> Jump to line<br>";
        messageBox("Help", text);
    });

    CodeMirror.defineMode("view", function (config, parserConfig) {
        var viewOverlay = {
            token: function (stream, state) {
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
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                resizeBrowser();
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                resizeBrowser();
            }
        },
        //highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true },
        matchTags: {bothTags: true },
        extraKeys: {
            "Ctrl-J": "toMatchingTag",
            "Alt-F": "findPersistent"
        }
    });
    editor.on('change', editor => {
        if (skipEdit) {
            skipEdit = false;
            return;
        }
        editorChange(editor.getValue());
    });

    function resizeBrowser() {
        if (editorOpened.length == 0) {
            $(".browser").css("height", $(window).outerHeight() - 130);
            $(".edit-code").hide();
        } else {
            if ($(".CodeMirror").hasClass("CodeMirror-fullscreen")) {
                $(".adminheader").css("z-index", 1);
            } else {
                $(".adminheader").css("z-index", 400);
                $(".edit-code").show();
                $(".top-tabs").css("width", 0);
                $(".CodeMirror").css("width", 0);
                $(".CodeMirror").css("height", 0);
                var fc = $(".file-content");
                $(".CodeMirror").css("width", fc.outerWidth());
                $(".CodeMirror").css("height", fc.outerHeight());
                $(".browser").css("height", $(window).outerHeight() - 130);
                $(".top-tabs").css("width", fc.outerWidth());
            }
        }
    }
    resizeBrowser();
    $(window).on("resize", function () {resizeBrowser(); });
    setTimeout(function () {resizeBrowser(); }, 10000);

    var openedEditor = -1;
    var lastOpen = -1;
    var editorId = 0;
    var skipEdit = true;
    var saveScroll = 0;
    function openEditor(file) {
        var file = file;

        if (editorOpened.some(function (x) {return x.file == file; })) {
            var editor = editorOpened.find(function (x) {return x != null && x.file == file; });
            openedEditor = editor.id;
            renderTabs();
            resizeBrowser();
            return;
        }

        manager.get("<?php echo Router::url(); ?>adminv3/templates/editor_open/", {file: file }, function (data) {
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
            $(".top-tabs").animate({scrollLeft: $(".top-tabs").outerWidth() }, 500);
            resizeBrowser();
        });
    }

    var lastEditor = null;
    function renderTabs() {
        saveScroll = $(".top-tabs").scrollLeft();

        $(".top-tabs").html("");
        var saveScrollTo = null;
        for (var key in editorOpened) {
            var _editor = editorOpened[key];
            var tab = $("<li class=top-tab></li>");
            if (_editor.id == openedEditor) {
                tab.addClass("active");
                $(".file-prop").show();

                if (lastOpen != openedEditor) {
                    $(".file-prop-name").text(_editor.name);
                    skipEdit = true;
                    //editor.setValue(_editor.content);
                    var buf = _editor.document;
                    if (buf.getEditor()) buf = buf.linkedDoc({sharedHist: true });
                    var old = editor.swapDoc(buf);
                    var linked = old.iterLinkedDocs(function (doc) {linked = doc; });
                    if (linked) {
                        lastEditor.document = linked;
                        old.unlinkDoc(linked);
                    }
                    editor.focus();

                    setTimeout(function () {
                        editor.refresh();
                        editor.focus();
                    }, 1);

                    lastOpen = openedEditor;

                    $(".top-tabs").scrollLeft(saveScroll);
                    if (_editor.tab != null) {
                        saveScrollTo = tab;
                    }

                    lastEditor = _editor;
                }
            }

            if (_editor.content != _editor.old) {
                tab.addClass("changed");
                $("#save-btn").removeClass("btn-disabled");
            } else {
                tab.removeClass("changed");
                $("#save-btn").addClass("btn-disabled");
            }

            var title = $("<div class=tab-title></div>");
            title.text(_editor.name);
            tab.append(title);
            var a = $("<a href=#></a>");
            a.html("<i class=\"fas fa-times\"></i>");
            a.data("id", _editor.id);
            a.click(function () {
                removeTab($(this).data("id"));
            });
            tab.append(a);
            tab.data("id", _editor.id);
            tab.on("click", function () {
                switchTab($(this).data("id"));
            });
            _editor.tab = tab;
            $(".top-tabs").append(tab);
        }

        if (saveScrollTo != null) {
            $(".top-tabs").scrollLeft(saveScroll);
            scroll = saveScrollTo.offset().left - $(".top-tabs").offset().left;
            $(".top-tabs").animate({scrollLeft: scroll }, 500);
        }
    }

    function switchTab(id) {
        openedEditor = id;
        renderTabs();
    }

    function editorChange(text) {
        editorOpened[openedEditor].content = text;
        renderTabs();
    }

    function save() {
        var file = editorOpened[openedEditor].file;
        var text = editorOpened[openedEditor].content;
        editorOpened[openedEditor].old = editorOpened[openedEditor].content;
        renderTabs();

        manager.get("<?php echo Router::url(); ?>adminv3/templates/editor_save/", {file: file, text: text }, function (data) {
            manager.notification("<?php echo t('The changes have been saved.'); ?>", "success");
        });
    }

    function addFile(elem, folder) {
        var elem = elem;
        var folder = folder;
        inputBox("New file", "Enter name for new file", "new-file", function (value) {
            manager.get("<?php echo Router::url(); ?>adminv3/templates/editor_new/", {file: folder + "/" + value }, function (data) {
                if (data.ok) {
                    manager.notification("<?php echo t('The file has been created.'); ?>", "success");

                    var f = $("<li class=file></li>");
                    var fd = $("<div></div>");
                    fd.html(data.file);
                    f.append(fd);
                    f.data("file", data.full);
                    f.click(function (e) {
                        openEditor($(this).data("file"));
                        e.preventDefault();
                    });
                    elem.append(f);
                } else {
                    manager.notification(data.error, "error");
                }
            });
        }, function () {});
    }
</script>