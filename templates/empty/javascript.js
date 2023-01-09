$(function(){
	$(".toggler").each(function(){
		$(this).on("click", function(){
			var t = $($(this).data("target"));
			var b = $($(this).data("target")+"_dark");
			if(t.hasClass("collapse") && !t.hasClass("show")){
				t.removeClass("collapse");
				t.addClass("collapsing");						
				setTimeout(function(){ t.removeClass("collapsing"); t.addClass("collapse"); t.addClass("show"); },700);
				b.addClass("show");
			}else{
				t.removeClass("show"); 
				t.addClass("collapsed");
				setTimeout(function(){ t.addClass("collapse"); t.removeClass("show"); t.removeClass("collapsed"); },700);
				b.removeClass("show");
			}
		});
	});
});
var selectedDIV = "", lastDIVsel = "", selectedBUT = "", selectData = new Array();
var userName = "", key = "";
var dialogPass = null;

start();

function recoveryPass(k){
	if(k==1){
		var d = new Dialog();
		d.setTitle('Vyhledejte svůj účet');
		d.setButtons([Dialog.SEARCH, Dialog.CANCEL2]);
		d.Load(_router_url + 'ajax/dialog/1');
		butt = d.getButtons();
		$(butt[1]).click(function(){ d.Close(); }); //Close
		$(butt[0]).click(function(){ 
			if($("#frm_in_username").val() != ""){
				userName = $("#frm_in_username").val();
				d.Load(_router_url + 'ajax/dialog/1', { user: $("#frm_in_username").val() }, recoveryPassCheck);
			}else{ $("#frm_in_username").addClass("error"); }
		});
		return false;
	}
	else{
		var d = new Dialog();
		d.setTitle('Bezpečné heslo');
		d.setButtons([Dialog.CLOSE]);
		d.Load(_router_url + 'ajax/dialog/2');
		butt = d.getButtons();
		$(butt[0]).click(function(){ d.Close(); }); //Close
	}
}
function recoveryPassCheck(dialog, text, status){
	if(text == "[Select Recovery]"){
		var dialog = dialog;
		dialog.setTitle("Obnovit heslo");
		dialog.setButtons([Dialog.CONTINUE, Dialog.CANCEL2]);
		dialog.Load(_router_url + 'ajax/dialog/1', {user: userName, recovery: 1});
		butt = dialog.getButtons();
		$(butt[1]).click(function(){ dialog.Close(); }); //Close
		$(butt[0]).click(function(){ 
			dialog.Load(_router_url + 'ajax/dialog/1', { user: $("#frm_in_username").val(), recovery:2 }, recoveryPassCheck);
		});
	}
	if(text == "[Show key]"){
		var dialog = dialog;
		dialog.setTitle("Zkontrolujte doručené e-mailové zprávy");
		dialog.setButtons([Dialog.CONTINUE, Dialog.CANCEL2]);
		dialog.Load(_router_url + 'ajax/dialog/1', {user: userName, key: ""});
		butt = dialog.getButtons();
		$(butt[1]).click(function(){ dialog.Close(); }); //Close
		$(butt[0]).click(function(){ 
			key = $("#frm_in_key").val();
			dialog.Load(_router_url + 'ajax/dialog/1', { user: $("#frm_in_username").val(), key:$("#frm_in_key").val() }, recoveryPassCheck);
		});
	}
	if(text == "[Show Change Pass]"){
		window.location.href=_router_url + "recovery/password/?user="+userName+"&key="+key;
	}
}
function passGet(id, form){
	dialogPass = new Dialog();
	dialogPass.setTitle("Zadejte znovu své heslo");
	dialogPass.setButtons(Dialog.OK_CLOSE);
	dialogPass.dialogHtml.html("<div class=cnt><b>Pro ověření je vyžadováno vaše heslo</b><br><br><label for=pass>Heslo: </label> <input type=password name=pass id=passkontrolhes></div>");
	dialogPass.Show();
	butt = dialogPass.getButtons();
	$(butt[0]).click(function(){ dialogPass.Close(); });
	$(butt[1]).click(function(){
		if($("#passkontrolhes").val() == ""){
			$("#passkontrolhes").css("border-color", "red");
		}else{
			var d = new Dialog();
			d.anonymous = true;
			d.Load(_router_url + 'ajax/dialog/passkontrolhes', { pass: $("#passkontrolhes").val() }, checkPassowrd);
		}
	});
}

function checkPassowrd(dialog, text, status){
	dialog.Close();
	if(text == "[PASS FAILED]"){
		messageBox("Chyba", "Bylo zadáno špatné heslo!");
	}else{
		$(idOfPassVal).val(text);
		//passGet(idOfPassVal, FormtoPassCheck);
		dialogPass.Close();
		FormtoPassCheck.submit();
	}
}

var idOfPassVal, FormtoPassCheck;
function checkPass(id, form){
	idOfPassVal = id;
	FormtoPassCheck = form;
	if($(id).val() == ""){
		passGet(id, form);
		return false;
	}
	return true;
}
function loadeditor(){
	/*$('textarea.tinimce:visible').froalaEditor(
		{
			"heightMin": "170px",
			"heightMax": "300px",
			toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'fontFamily', 'fontSize', '|', 'color', 'emoticons', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', '-', 'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable', '|', 'quote', 'insertHR', 'undo', 'redo', 'clearFormatting', 'selectAll'],
			pluginsEnabled: null
		}
	)*/
	tinymce.init({
		content_css : _router_url + "templates/bootstrap/style.css",
		selector: 'textarea.tinimce',  // change this value according to your HTML
		theme: 'silver',
		relative_urls: false,
		relative_urls: false,
		convert_urls: false,
		plugins: [
			'advlist autolink lists link image charmap print preview hr anchor pagebreak',
			'searchreplace wordcount visualblocks visualchars code fullscreen',
			'insertdatetime media nonbreaking save table directionality',
			'emoticons template paste textpattern imagetools codesample toc'
		],
		toolbar1: 'undo redo | insert | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		toolbar2: 'print preview media | forecolor backcolor emoticons | codesample',
	});
	tinymce.init({
		content_css : _router_url + "templates/bootstrap/style.css",
		selector: 'textarea.tinimcenocheck',  // change this value according to your HTML
		theme: 'silver',
		relative_urls: false,
		relative_urls: false,
		convert_urls: false,
		plugins: [
			'advlist autolink lists link image charmap print preview hr anchor pagebreak',
			'searchreplace wordcount visualblocks visualchars code fullscreen',
			'insertdatetime media nonbreaking save table directionality',
			'emoticons template paste textpattern imagetools codesample toc'
		],
		toolbar1: 'undo redo | insert | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		toolbar2: 'print preview media | forecolor backcolor emoticons | codesample',
		cleanup : false,
    	verify_html : false
	});
	tinymce.init({
		content_css : "style.css",
		selector: 'textarea.tinimce_mini',  // change this value according to your HTML
		theme: 'silver',
		//skin: 'custom',
		menubar: false,
		relative_urls: false,
		relative_urls: false,
		convert_urls: false,
		remove_script_host : false,
		resize: false,
		statusbar: false,
		plugins: [
			'advlist autolink lists link image charmap print preview hr anchor pagebreak',
			'searchreplace wordcount visualblocks visualchars code fullscreen',
			'insertdatetime media nonbreaking save table directionality',
			'emoticons template paste textpattern imagetools codesample toc'
		],
		toolbar1: 'undo redo | insert | styleselect | bold italic underline | code | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
	});
}

$(function() { loadeditor(); });

function getEditorText(textareIdOrJqueryObject){
	if(typeof(textareIdOrJqueryObject) === "string")
		return tinymce.get(textareIdOrJqueryObject).save();
	return tinymce.get(textareIdOrJqueryObject.attr("id")).save();
}
function isTextEditor(textarea){
	if(textarea.hasClass("tinimce_mini") || textarea.hasClass("tinimce"))
		return true;
	return false;
}