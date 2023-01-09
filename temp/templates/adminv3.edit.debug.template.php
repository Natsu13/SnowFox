<div  class="row"><div  class="col-xl-9 col-lg-12"><div  class="card"><form  id="templateedit"  method="post"  action="<?php echo Router::url(); ?>adminv3/templates/update/<?php echo $model['id']; ?>"><div  class="row"><div  class="col-md-3 static-text"><?php echo t("Name"); ?></div> <div  class="col-md-9"><input  type="text" <?php if((is_bool(  $model['name'] ) && (  $model['name'] )) || !is_bool(  $model['name'] )) { echo " value=\"" . ($model['name']) . "\""; } ?>  name="name" /></div>      </div>  <div  class="row"><div  class="col-md-3 static-text"><?php echo t("Code"); ?></div> <div  class="col-md-9"><input  type="text" <?php if((is_bool(  $model['code'] ) && (  $model['code'] )) || !is_bool(  $model['code'] )) { echo " value=\"" . ($model['code']) . "\""; } ?>  name="code"  data-list="<?php echo join(';', $model['templatesList']); ?>" /></div>      </div>  <div  class="row"><div  class="col-md-3 static-text"><?php echo t("Description"); ?></div> <div  class="col-md-9"><div  class="switch"><a  href="#"  class=""  id="switch-editor"  onclick="switchToEditor();return false;">Editor</a> <a  href="#"  class="selected"  id="switch-html"  onclick="switchToHtml(true);return false;">HTML</a> </div> <input  type="hidden"  name="html"  id="htmlonlyval"  value="1" /><textarea  rows="20"  name="content"  class='tinimcenocheck'  id="intereditor"><?php echo htmlentities($model['content']); ?></textarea>                         </div> </div> <div  class="row"><div  class="col-md-3"></div> <div  class="col-md-9"><button  type="submit"  class="submit button button-primary"  onclick='beforeSubmit();'  name="save"><?php echo t('Save'); ?></button>  <?php if($model["hasTemplate"] || 1==1) { ?><button  class="button button-primary"  onclick='templatePreview(event); return false;'  name="preview"><?php echo t('Preview'); ?></button>  <?php } ?><button  class="button button-secondary"  onclick='templateRebuild(event); return false;'  name="rebuild"><?php echo t('Rebuild'); ?></button> </div> </div> </form> </div> </div> <div  class="col-xl-3 col-lg-12"><div  class="card"><div  class="card-title"> <?php echo t("Description"); ?></div> <div  class="card-content"> <?php if($model["hasTemplate"]) { ?> <?php echo $model["templateDescription"]; ?> <?php } else { ?> <?php echo t("Unkown template code"); ?> <?php } ?></div> </div> </div> </div> <div  id="window_preview"  class="width-700"  style="display:none;"><div  style="padding: 8px 2px;font-size: 14px;border-top: 1px solid #e2e2e2;">Subject <b  id="subject"></b> </div> <iframe  style="width:100%;height:500px;border: 1px solid silver;background:white;"  id="preview-iframe"></iframe> </div> <script> var dialogPreview;
    var isHtml = true;
    var rand = "<?php echo $model['rand']; ?>";
    $(function(){
        if(isHtml) {
            setTimeout(function(){switchToHtml(true); }, 100); 
        }

        dialogPreview = new Dialog();
        dialogPreview.setTitle("Preview");
        dialogPreview.setButtons(Dialog.CLOSE);     
        var btn = dialogPreview.getButtons();
        $(btn[0]).click(function(){dialogPreview.Close(); });
        dialogPreview.html.dialogHtml.append($("#window_preview"));   
        $("#window_preview").show(); 

        loadeditor();
        bindEditor();
    });

    function templateRebuild(e){
        e.preventDefault();
        manager.get("<?php echo Router::url(); ?>adminv3/templates/rebuild/<?php echo $model['id']; ?>", {},  function (data, isSuccess) {});
    }

    function templatePreview(e){
        e.preventDefault();
        $("#subject").html($("[name=name]").val());            
        dialogPreview.ShowLoading();

        manager.get("<?php echo Router::url(); ?>adminv3/templates/draft/<?php echo $model['id']; ?>", {rand: rand, content: getTemplateContent() },  function (data, isSuccess) {           
            setTimeout(function(){
                $("#preview-iframe").attr("src", "<?php echo Router::url(); ?>adminv3-loadContent/?_pageLoad=templates/preview/<?php echo $model['id']; ?>&rand="+rand);
                dialogPreview.Show(); 
            }, 200);
        });
    }

    function bindEditor(){
        $("#intereditor").on("keyup", function(){
			if($("#htmlonlyval").val() == "0") {
				var text = $(this).val();
				tinymce.activeEditor.setContent(text);
				tinymce.activeEditor.getDoc().designMode = 'Off';				
			}
		});
    }

    function getTemplateContent(){
        if($("#htmlonlyval").val() == "0") {
            return tinymce.activeEditor.getContent();
        }
        return $("[name=content]").val();
    }
    
    function beforeSubmit(){
        if($("#htmlonlyval").val() == "0") {
            $("[name=content]").val(tinymce.activeEditor.getContent());
        }
    }

    var ace = null;
    function switchToHtml(html){
        $("#switch-html").addClass("selected");
        $("#switch-editor").removeClass("selected");
        ace = tinymce.activeEditor;
        console.log(tinymce.activeEditor);
        tinyMCE.remove();

        console.log(html);				
		if(html === true){
			$('#htmlonly').val($("#oldtextsaved").val());
		}else{
			$('#htmlonly').val(tinymce.activeEditor.getContent());
		}
        
		$('#htmlonlyval').val(1);
		try{           
			tinymce.activeEditor.hide();
			tinymce.activeEditor.getDoc().designMode = 'Off';									
		}catch{}
	}

	function switchToEditor(html){
        $("#switch-editor").addClass("selected");
        $("#switch-html").removeClass("selected");
        loadeditor();
		$('#htmlonlyval').val(0);
        bindEditor();
	}
</script>