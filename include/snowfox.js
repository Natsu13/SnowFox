
/*Inicialization template functions*/
loadeditor = () => { };
getEditorText = () => { return ""; };
isTextEditor = () => { return false; };

var WindowTitle = document.title;

function NotifView(op) {
	if (jak == 0) {
		selectedDIV = $("#notificatons");
		if (op != 5) { $("#notificatons").toggle(); }
		$("#nottifSpin").css('display', "inline")
		if ($("#notificatons").css('display') == "block") {
			if ($("#buttonNotificationOther").css('display') == "none") { $("#buttonNotificationOther").css('display', "inline"); posbut = $("#buttonNotificationOther").offset(); $("#buttonNotificationOther").css('display', "none"); }
			else { posbut = $("#buttonNotificationOther").offset(); }
			$("#notificatons").offset({ top: (posbut.top + 22), left: (posbut.left - 400 + 24) });
			if ($("#notificationsText").html() == "") { $("#notificationsText").html("<div style='text-align:center;padding:7px;'>Načítám....</div>"); }
			$.ajax({
				url: notificationaj,
				data: { "jak": jak },
				success: function (data) {
					$("#nottifSpin").css('display', "none")
					$("#notificationsText").html("");
					for (i = 0; i < data.length; i++) {
						if (data[i].Url != "") { divN = "a"; style = ""; } else { divN = "div"; style = "cursor:default;"; }
						$("<" + divN + " style='" + style + "' class='notifList " + data[i].Class + "' href='" + data[i].Url + "'><img src='" + data[i].Image + "' class='img'><div class=body><div class='text'>" + data[i].Text + "</div><div class='info'>" + data[i].Info + "</div></div><div style='clear:both;'></div><div style='clear:both;'></div></" + divN + ">").appendTo("#notificationsText");
					}
					if (data.length == 0) { $("<div style='text-align:center;padding:7px;'>Žádná notifikace k zobrazení!</div>").appendTo("#notificationsText"); }
				}
			});
		}
	} else {
		selectedDIV = $("#notificatons_other");
		if (op != 5) { $("#notificatons_other").toggle(); }
		$("#nottifSpin_other").css('display', "inline")
		if ($("#notificatons_other").css('display') == "block") {
			if ($("#buttonNotificationOther").css('display') == "none") { $("#buttonNotificationOther").css('display', "inline"); posbut = $("#buttonNotificationOther").offset(); $("#buttonNotificationOther").css('display', "none"); }
			else { posbut = $("#buttonNotificationOther").offset(); }
			$("#notificatons_other").offset({ top: (posbut.top + 22), left: (posbut.left - 400 + 24) });
			if ($("#notificationsText_other").html() == "") { $("#notificationsText_other").html("<div style='text-align:center;padding:7px;'>Načítám....</div>"); }
			$.ajax({
				url: notificationaj,
				data: { "jak": jak },
				success: function (data) {
					$("#nottifSpin_other").css('display', "none")
					$("#notificationsText_other").html("");
					for (i = 0; i < data.length; i++) {
						if (data[i].Url != "") { divN = "a"; style = ""; } else { divN = "div"; style = "cursor:default;"; }
						$("<" + divN + " style='" + style + "' class='notifList " + data[i].Class + "' href='" + data[i].Url + "'><img src='" + data[i].Image + "' class='img'><div class=body><div class='text'>" + data[i].Text + "</div><div class='info'>" + data[i].Info + "</div></div><div style='clear:both;'></div><div style='clear:both;'></div></" + divN + ">").appendTo("#notificationsText_other");
					}
					if (data.length == 0) { $("<div style='text-align:center;padding:7px;'>Žádná notifikace k zobrazení!</div>").appendTo("#notificationsText_other"); }
				}
			});
		}
	}
}

/*Notification create*/
function NotifiTick(id, div, bar, tick) {
	var _id = id, _div = div, _bar = bar, _tick = tick;
	var size = 123;
	_tick -= 20;
	if (_tick < 0) {
		div.fadeOut("slow", function () { });
	} else {
		var prc = _tick / 1000;
		var wid = (prc) * size;
		bar.css("width", wid + "%");
		setTimeout(function () { NotifiTick(_id, _div, _bar, _tick); }, 100);
	}
}
var ntf = Array();
notifID = 0;
function NotificationCreate(text, desc, href, clas) {
	var ntid = notifID;
	ntf[notifID] = $("<a></a>");
	ntf[notifID].addClass("notifAlert");
	ntf[notifID].addClass(clas);
	ntf[notifID].attr("id", "notification_" + notifID);
	ntf[notifID].attr("style", "display:none;");
	ntf[notifID].attr("data-timeout", 1000);
	if (typeof href != "undefined")
		ntf[notifID].attr("href", href);
	var clo = $("<span></span>");
	clo.addClass("close");
	clo.click(function () { ntf[ntid].fadeOut("slow", function () { }); });
	ntf[notifID].append(clo);
	var ditex = $("<div></div>");
	ditex.addClass("text");
	ditex.html(text);
	ntf[notifID].append(ditex);
	var descr = $("<div></div>");
	descr.addClass("descr");
	descr.html(desc);
	ntf[notifID].append(descr);
	var statu = $("<div></div>");
	statu.addClass("bar");
	statu.css("width", "123%");
	ntf[notifID].append(statu);
	$("#notificatonsAlert").append(ntf[notifID]);
	//$("<a class='notifAlert' id='notification_"+notifID+"' style='display:none;' href='"+href+"'><span href='#' class=close></span><div class=text>"+text+"</div><div class=descr>"+desc+"</div></a>").appendTo("#notificatonsAlert");

	$("#notification_" + notifID).fadeIn("slow", function () { });
	//setTimeout(function(bi) { $("#notification_"+bi).fadeOut( "slow", function() {} ); }, 30000, notifID);
	NotifiTick(ntid, ntf[notifID], statu, 1000);
	notifID++;
}

function NotificationControl() {
	$.ajax({
		url: notifcountajax,
		data: {},
		success: function (data) {
			$("#buttonNotification").html(data.Count);
			$("#buttonNotificationOther").html(data.Notif);
			$("#buttonNotification").css("display", "inline");
			if (data.Count > 0 || data.Notif > 0) {
				document.title = "(" + (data.Count + data.Notif) + ") " + WindowTitle;
			} else {
				document.title = WindowTitle;
			}
			$("#buttonNotification_count").css("display", "block");
			$("#buttonNotification_count").css("top", $("#buttonNotification").offset().top + 7);
			$("#buttonNotification_count").css("left", $("#buttonNotification").offset().left + $("#buttonNotification").width() - $("#buttonNotification_count").width() + 4);

			$("#buttonNotificationOther_count").css("display", "block");
			$("#buttonNotificationOther_count").css("top", $("#buttonNotificationOther").offset().top + 7);
			$("#buttonNotificationOther_count").css("left", $("#buttonNotificationOther").offset().left + $("#buttonNotificationOther").width() - $("#buttonNotificationOther_count").width() + 4);

			if ($("#buttonNotification_count").attr("jsed") != 1) {
				$("#buttonNotification_count").appendTo("body");
				$("#buttonNotification_count").attr("jsed", 1)
				$("#buttonNotificationOther_count").appendTo("body");
			}
			if (data.Count > 0) {
				$("#buttonNotification_count_text").html(data.Count);
			} else { $("#buttonNotification_count_text").html(data.Count); $("#buttonNotification_count").css("display", "none"); }
			if (data.Notif > 0) {
				$("#buttonNotificationOther_count_text").html(data.Notif);
			} else { $("#buttonNotificationOther_count_text").html(data.Notif); $("#buttonNotificationOther_count").css("display", "none"); }
		}
	});
	$.ajax({
		url: notificationco + "?time=" + (timeNot - 1000),
		data: {},
		success: function (data) {
			for (i = 1; i < data.length; i++) {
				NotificationCreate(data[i].Text, data[i].Info, data[i].Href, data[i].Image);
			}
			timeNot = data[0].time;
		}
	});
}

function loading_show() {
	$("#dialog-loading").show("scale", 400);
}
function loading_hide(div) {
	$('#dialog-loading').css("width", "300px");
	$('#dialog-loading').css("height", "70px");
	if (typeof div != "undefined") {
		var div = div;
		div.css("opacity", 0);
		$("#dialog-loading").hide();
		$('#dialog-loading').animate({ height: div.outerHeight(), width: div.outerWidth(), top: div.offset().top, left: div.offset().left }, function () {
			div.css("opacity", 1);
			$('#dialog-loading').css("width", "300px");
			$('#dialog-loading').css("height", "70px");
			$("#dialog-loading").center();
		});
		//parents('.ui-dialog:first').
	} else {
		$("#dialog-loading").hide("fade", 200);
	}
}

function ResetPassword(id) {
	html = "<div style='padding:8px;'><div class=redbox>Tomuto uživately již bylo heslo resetováno!</div>";
	html += "Níže naleznete kod pro resetování hesla pro daného uživatele:";
	html += "<input type=text value='45fs1v45' style='width:200px;font-size: 23px;display:block;margin: 12px 0px;'>";
	html += "<div style='border-top:1px solid silver;margin: 8px 0px;'></div>";
	html += "<button style='float:right;margin:3px;' onClick='$(\"#dialog-dialog\").dialog(\"close\");'>Zavřít</button>";
	html += "<div style='clear:both;'></div></div>";
	$("#dialog-dialog").dialog('option', 'title', "Resetování hesla");
	$("#dialog-dialog").html(html);
	$("#dialog-dialog").dialog("open");
}

function UserShowCategory(ids) {
	html = "";
	if (ids == -1) {
		for (i = 0; i < allUserCategory.sekcion.length; i++) {
			if (typeof allUserCategory.users[i] != "undefined") {
				for (a = 0; a < allUserCategory.users[i].length; a++) {
					html += "<div class='ratingBoxis'><img src='" + basepath + "/images/avatars/" + allUserCategory.users[i][a]["Avatar"] + "' class='ratingAvatar'><div style='float:left;'><b><a style='bacground:white;' href='" + intercomlinked + "/" + allUserCategory.users[i][a]["Username"] + "/'>" + allUserCategory.users[i][a]["Name"] + "</a></b><br>" + allUserCategory.sekcion[i] + "</div><div style='clear:both;'></div></div>";
				}
			}
		}
	} else {
		if (typeof allUserCategory.users[ids] == "undefined") {
			html = "<div style='font-weight:bold;text-align:center;padding:20px;'>Nebyly nalezeny žádné výsledky.</div>";
		} else {
			for (var i = 0; i < allUserCategory.users[ids].length; i++) {
				html += "<div class='ratingBoxis'><img src='" + basepath + "/images/avatars/" + allUserCategory.users[ids][i]["Avatar"] + "' class='ratingAvatar'><div style='float:left;'><b>" + allUserCategory.users[ids][i]["Name"] + "</b><br><a href='" + intercomlinked + "/" + allUserCategory.users[ids][i]["Username"] + "/'>Poslat zprávu</a></div><div style='clear:both;'></div></div>";
			}
		}
	}
	$("#UserListCategory").html(html);
}

var swpier_toggle_body = new Array();
var swpier_toggle_id = new Array();
var resizer_klt = new Array();
var progres_bar = new Array();

function initializeSpecialComponents() {
	$("input[type=\"progressbar\"]").each(function (i) {
		var state = $(this).attr("value");
		var width = $(this).outerWidth(true);
		var max = $(this).attr("data-max");
		var proc = Math.round((state / max) * 100);
		var wigra = (width / 100) * state;

		$(this).css("display", "none");
		this.setAttribute("parent", i);
		body = document.createElement('div');
		body.setAttribute("parent", i);
		$(body).css("width", width + "px");
		$(body).addClass("progress_bar");
		$(body).html("<span class=pro>" + proc + "%</span>");

		gra = document.createElement('div');
		$(gra).css("width", wigra + "px");
		$(gra).addClass("gra");
		$(body).append(gra);

		text = document.createElement('div');
		$(text).css("width", width + "px");
		$(text).addClass("text");
		$(text).html(proc + "%");
		$(gra).append(text);

		progres_bar[i] = new Array($(this), body);

		$(this).bind("change paste keyup", function (event) {
			var state = $(this).val();
			var max = $(this).attr("data-max");
			var proc = Math.round((state / max) * 100);
			var wigra = (state / width) * 100;
			var parent = $(this).attr("parent");
			var div = progres_bar[parent][1];

			$($(div).find(".pro")[0]).html(proc + "%");
			//$($(div).find(".gra")[0]).css("width",wigra+"px");
			$($(div).find(".gra")[0]).animate({ width: wigra + "px", }, 200);
			$($(div).find(".text")[0]).html(proc + "%");
		});

		$(this).after(body);
	}
	);
	$("img[type=\"image_resize\"]").load(function (a) {
		i = a.timeStamp;
		var image = $(this).attr("src");
		var name = $(this).attr("name");
		var resize = $(this).attr("data-resize").split("x");

		$(this).css("display", "none");

		imre_body = document.createElement('div');
		$(imre_body).css("width", resize[0]);
		$(imre_body).css("height", resize[1]);
		imre_body.setAttribute("parent", i);
		$(imre_body).css("background", "url(" + image + ")");
		$(imre_body).addClass("image_resize_b");

		$(this).after(imre_body);
	}
	);

	function stateChanged(s) {
		var parent = s.attr("parent");
		var width = $($(swpier_toggle_id[parent]).data("toggler")).outerWidth();
		var heigh = $($(swpier_toggle_id[parent]).data("toggler")).outerHeight();
		var state = swpier_toggle_id[parent].prop("checked");
		var disab = swpier_toggle_id[parent].attr("disabled");
		if (!disab) {
			var mover = $($(swpier_toggle_body[parent]).find(".mover")[0]);				
			var animLength = 200;
			var opt = {h: mover.outerHeight(), w: mover.outerWidth(), t: mover.css("top"), p: mover.css("padding"), ns: 20};
			opt.pn = opt.ns - opt.h - 1;
			var topChange = opt.pn;
			mover.stop();
			mover.addClass("moving");

			if (state) {
				$(swpier_toggle_body[parent]).find(".icon-on").hide();
				$(swpier_toggle_body[parent]).find(".icon-off").show();

				var h = $(swpier_toggle_body[parent]).outerHeight() / 2;				
				var left = heigh - h*2 + 1;

				$(swpier_toggle_id[parent]).attr("name", $(swpier_toggle_id[parent]).data("name") + "_unchecked");
				$(swpier_toggle_id[parent]).val("");
				//$($(swpier_toggle_body[parent]).find(".mover")[0]).css("left", left);		

				mover.stop();
				mover.animate({left: 0, height: opt.ns, width: opt.ns + 5, top: topChange, padding: opt.pn}, animLength/2, function(){
					mover.animate({left: 2, height: opt.h, width: opt.w, top: opt.t, padding: opt.p}, animLength/2, function(){
						mover.removeClass("moving");
					});	
				});	

				$($(swpier_toggle_body[parent]).find(".mover")[0]).removeClass("active");
				$(swpier_toggle_body[parent]).removeClass("active");
			} else {
				$(swpier_toggle_body[parent]).find(".icon-on").show();
				$(swpier_toggle_body[parent]).find(".icon-off").hide();

				$(swpier_toggle_id[parent]).attr("name", $(swpier_toggle_id[parent]).data("name"));
				$(swpier_toggle_id[parent]).val($(swpier_toggle_id[parent]).data("value"));
				//$($(swpier_toggle_body[parent]).find(".mover")[0]).css("left", (width / 2) - 2);	

				mover.animate({left: ((width / 2) - 2) - 1 - (opt.ns - opt.h) * 2, height: opt.ns, width: opt.ns + 5, top: topChange, padding: opt.pn, "padding-left": opt.pn + 5}, animLength/2, function(){
					mover.animate({left: (width / 2) - 2, height: opt.h, width: opt.w, top: opt.t, padding: opt.p}, animLength/2, function(){
						mover.removeClass("moving");
					});	
				});	

				$($(swpier_toggle_body[parent]).find(".mover")[0]).addClass("active");
				$(swpier_toggle_body[parent]).addClass("active");
			}
			swpier_toggle_id[parent].prop("checked", !state);
			$(swpier_toggle_id[parent]).trigger("change");
		}
	}

	/**
	 * If you want check and change the state you must do it like this
	 * $("#geo_state").prop( "checked", false).trigger('click');
	 * $("#geo_state").prop( "checked", true).trigger('click');
	 * $("#geo_state").prop( "disabled", true)
	 */
	$("input[type=\"toggle_swipe\"]").each(function (i) {
		if ($(this).data("created") != "yes")
			$(this).data("created", "yes");
		else {
			return;
		}

		swpier_toggle_id[i] = $(this);

		$(this).data("value", $(this).val());
		var state = $(this).prop("checked");
		var stav = $(this).attr("data-state"); 
		if (typeof $(this).attr("data-state") == "undefined" || $(this).attr("data-state") == "") { 
			stav = new Array("ON", "OFF"); 
		} else { 
			stav = stav.split("|");
		}
		var disab = $(this).attr("disabled");
		$(this).data("name", $(this).attr("name"));
		var swpier_toggle = document.createElement('a');

		$(swpier_toggle).attr("style", $(this).attr("style"));
		$(swpier_toggle).attr("class", $(this).attr("class"));
		$(this).css("display", "none");

		swpier_toggle_id[i].data("toggler", swpier_toggle);
		swpier_toggle.setAttribute("id", "toggle_swipe_" + i);
		swpier_toggle.setAttribute("parent", i);
		//swpier_toggle.setAttribute("href","#toggle_swipe_"+i);
		$(swpier_toggle).addClass("toggle_swipe");

		swpier_toggle_id[i].click(function () {
			$(this).prop("checked", !$(this).prop("checked"));
			stateChanged($(swpier_toggle));
		});

		$(swpier_toggle).click(function () {
			stateChanged($(this));
			return false;
		});

		var _parent = $(this).parents("label");
		if(_parent.length > 0) {
			_parent.data("togler", swpier_toggle_id[i]);
			_parent.data("swiper", $(swpier_toggle));
			_parent.dblclick(function(event){
				event.preventDefault();
			});
			_parent.on("mouseover", function(){
				$(this).find("a").addClass("hover");
			});
			_parent.on("mouseout", function(){
				$(this).find("a").removeClass("hover");
			});
			_parent.on("click", function(e){
				if(e.originalEvent !== undefined) {
					e.stopPropagation();
					e.preventDefault();										
					stateChanged($(this).data("swiper"));	
				}
			});
		}

		swpier_toggle_id[i].watch('disabled', function () {
			var parent = $(swpier_toggle).attr("parent");
			if ($(this).prop("disabled")) {
				$(swpier_toggle).addClass("disabled");
				$(swpier_toggle_id[parent]).val("");
			} else {
				$(swpier_toggle).removeClass("disabled");
				$(swpier_toggle_id[parent]).val($(swpier_toggle_id[parent]).data("value"));
			}
		});

		swpier_toggle_body[i] = document.createElement('span'); // tělo switche
		$(swpier_toggle_body[i]).addClass("body");

		var swpier_toggle_body_switch = document.createElement('div'); //přepínač
		$(swpier_toggle_body_switch).addClass("mover");
		if (disab == "disabled") {
			$(swpier_toggle).addClass("disabled");
		}
		//stateChanged($(swpier_toggle));

		//$(swpier_toggle_body_switch)
		var off_icon = $(this).data("icon-off");
		if(off_icon == undefined) off_icon = window.toggle_swipe_default_icon_off;
		if(off_icon != undefined) {
			var off = $("<span></span>");
			off.addClass("icon icon-off");
			off.html("<span class=\"material-symbols-outlined\">"+off_icon+"</span>");
			$(swpier_toggle_body_switch).append(off);
		}
		var on_icon = $(this).data("icon-on");
		if(on_icon == undefined) on_icon = window.toggle_swipe_default_icon_on;
		if(on_icon != undefined) {
			var on = $("<span></span>");
			on.addClass("icon icon-on");
			on.html("<span class=\"material-symbols-outlined\">"+on_icon+"</span>");
			$(swpier_toggle_body_switch).append(on);
		}

		$(swpier_toggle_body[i]).append(swpier_toggle_body_switch);
		$(swpier_toggle).append(swpier_toggle_body[i]);
		$(this).after(swpier_toggle);

		if (state) {
			$(swpier_toggle_body[i]).addClass("active");
			$(swpier_toggle_body_switch).addClass("active");
			$(swpier_toggle_body[i]).find(".icon-on").show();
			$(swpier_toggle_body[i]).find(".icon-off").hide();
			var o = i;
			$(function () {
				setTimeout(function () {
					var width = $($(swpier_toggle_id[o]).data("toggler")).getHiddenDimensions().width;
					$(swpier_toggle_body_switch).css("left", (width / 2) - 2);
				}, 100);
			});
		} else {
			$(swpier_toggle_body[i]).find(".icon-on").hide();
			$(swpier_toggle_body[i]).find(".icon-off").show();
			var heigh = $(swpier_toggle).outerHeight();
			var h = $(swpier_toggle_body[i]).outerHeight()/2;
			var left = heigh - h*2 + 1;
			$(this).attr("name", $(this).data("name") + "_unchecked");
			$(this).val("");
			$(swpier_toggle_body_switch).css("left", 3);
			//$(swpier_toggle_body_switch).css("top", 4);
		}
	}
	);
}


// Function to watch for attribute changes
//TODO: replace it with this https://www.javascripture.com/MutationObserver
//TODO: https://stackoverflow.com/questions/16781778/detecting-attribute-change-of-value-of-an-attribute-i-made
$.fn.watch = function (props, callback, timeout) {
	if (!timeout)
		timeout = 10;
	return this.each(function () {
		var el = $(this),
			func = function () { __check.call(this, el) },
			data = {
				props: props.split(","),
				func: callback,
				vals: []
			};
		$.each(data.props, function (i) { data.vals[i] = el.attr(data.props[i]); });
		el.data(data);
		if (typeof (this.onpropertychange) == "object") {
			el.bind("propertychange", callback);
		} else {
			setInterval(func, timeout);
		}
	});
	function __check(el) {
		var data = el.data(),
			changed = false,
			temp = "";

		if (data.props == undefined)
			return;

		for (var i = 0; i < data.props.length; i++) {
			temp = el.attr(data.props[i]);
			if (data.vals[i] != temp) {
				data.vals[i] = temp;
				changed = true;
				break;
			}
		}

		if (changed && data.func) {
			data.func.call(el, data);
		}
	}
}


var selected_div_for_hide = new Array();
var selected_div_for_timeout = 100;
function start() {
	$(".NoJS").hide();
	$(".JS").show();

	ReplaceSelect();
	initializeSpecialComponents();
	//ContextMenuClickable();

	$(document).mouseup(function (e) {
		lastDIVsel = "";
		if (selectedDIV != "") {
			var container = $(selectedDIV);

			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
			{
				//container.hide();
				var bx = selectedDIV;
				var he = $(bx).height();
				$(bx).animate({ height: $(selectedBUT).outerHeight() }, 100, function () { container.parent().hide(); $(bx).css("height", ""); });
				lastDIVsel = selectedDIV;
				selectedDIV = "";
				selectedBUT.removeClass("selected");
			}
		}
	});
	$(document).mouseup(function (e) {
		selected_div_for_hide.forEach(function (element) {
			if (element != "") {
				var container = element;

				if (container != null && !container.is(e.target) && container.has(e.target).length === 0) {
					HideSelectedDiv(element);
				}
			}
		});
	});
}
function AddSelectedDiv(container) {
	selected_div_for_hide.push(container);
}
function RemoveSelectedDiv(container) {
	selected_div_for_hide.splice(selected_div_for_hide.indexOf(container), 1);
}
function HideSelectedDiv(container) {
	if (container == null)
		return;
	if (!container.hasClass("showing")) {
		container.addClass("hiding");
		setTimeout(function () {
			//selected_div_for_hide = void 0;
			container.removeClass("hiding");
			container.hide();
			selected_div_for_timeout = 100;
			RemoveSelectedDiv(container);
		}, selected_div_for_timeout);
	}
}

serialize = function (obj) {
	var str = [];
	for (var p in obj)
		if (obj.hasOwnProperty(p)) {
			str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
		}
	return str.join("&");
}

function isMobile() {
	return $(window).outerWidth() < 985;
}

var DialogService = {
	callbacks: [],
	register: function(callback){
		DialogService.callbacks.push(callback);
	},
	notify: function(type, data) {
		for(var key in DialogService.callbacks) {
			DialogService.callbacks[key](type, data);
		}
	}
};

var Dialog = function (width, addclass) {
	this.title = "";
	this.content = "";
	this.buttons = [];
	this.fnOnCancel = function () { };
	this.ContextPadding = 14;
	this.MinHeight = 100;
	if (typeof width != "undefined" && width != null)
		this.width = width;
	else
		this.width = 700;
	this.html = {};
	this.anonymous = false;
	this.addclass = addclass;
	this.Create();

	this._ClickOutsideCl = true;
	this._OnWindowShow = null;
}

Dialog.OK = { name: "ok", className: "btn btn-primary", title: "OK", type: "button" };
Dialog.OK_CLOSE = [
	{ name: "close", className: "btn btn-secondary", title: "Zavřít", type: "button" }, 
	{ name: "ok", className: "btn btn-primary", title: "OK", type: "button" }
];
Dialog.CANCEL = { name: "cancel", className: "btn btn-primary", title: "Storno", type: "a" };
Dialog.CONFIRM = { name: "confirm", className: "btn btn-primary", title: "Potvrdit", type: "button" };
Dialog.CONTINUE = { name: "continue", className: "btn btn-secondary", title: "Pokračovat", type: "button" };
Dialog.DELETE = { name: "delete", className: "btn btn-primary", title: "Odstranit", type: "button" };
Dialog.NEXT = { name: "next", className: "btn btn-primary", title: "Další", type: "button" };
Dialog.SAVE = { name: "save", className: "btn btn-primary", title: "Uložit", type: "button" };
Dialog.SUBMIT = { name: "submit", className: "btn btn-primary", title: "Odeslat", type: "button" };
Dialog.CLOSE = { name: "close", className: "btn btn-secondary", title: "Zavřít", type: "button" };
Dialog.SEARCH = { name: "search", className: "btn btn-secondary", title: "Hledat", type: "button" };
Dialog.CREATE = { name: "create", className: "btn btn-secondary", title: "Vytvořit", type: "button" };
Dialog.CANCEL2 = { name: "cancel", className: "btn btn-primary", title: "Zrušit", type: "button" }

Dialog._WINDOWS = 0;

Dialog.prototype = {
	Remove: function(){
		this.html.background.remove();
		this.html.dialog.remove();
	},
	Create: function () {
		this.windowId = Dialog._WINDOWS + 1;
		var parent = this;		
		
		this.html.background = $("<div>");
		this.html.background.addClass("dialog-background");
		//this.html.background.click(function(){ parent.Close(); });
		this.html.background.hide();
		$("body").append(this.html.background);

		this.html.dialog = $("<div>");
		this.html.dialog.addClass("dialog");
		if(this.addclass != undefined && this.addclass != "") {
			this.html.dialog.addClass(this.addclass);
		}
		this.html.dialog.css("width", this.width);
		this.html.dialog.css("min-height", this.MinHeight);
		this.html.background.append(this.html.dialog);

		this.html.dialogContent = $("<div>");
		this.html.dialog.append(this.html.dialogContent);

		this.html.dialogHtml = $("<div>");
		this.dialogHtml = this.html.dialogHtml;
		this.html.dialogHtml.addClass("dialog-content");
		this.html.dialogContent.append(this.html.dialogHtml);

		this.html.dialogLoading = $("<div>");
		this.html.dialogLoading.addClass("dialog");
		this.html.dialogLoading.addClass("loading-dialog");
		this.html.dialogLoading.css("width", "400px");
		this.html.dialogLoading.css("height", "90px");
		this.html.background.append(this.html.dialogLoading);

		this.html.dialogLoadingContent = $("<div>");
		this.html.dialogLoadingContent.addClass("loading");
		this.html.dialogLoading.append(this.html.dialogLoadingContent);

		this.html.dialog.hide();
		this.html.dialogLoading.hide();

		$(window).resize(function () {
			parent.Center();
		});

		var checkSize = function (parent) {
			setTimeout(function () {
				if (!isMobile()) {
					if (!parent.html.dialogLoading.is(':visible')) {
						if (parent.html.dialog.is(':visible')) {
							if (parent.html.dialog.outerHeight() != parent.html.dialog.find("div").outerHeight()) {
								parent.html.dialog.css("height", parent.html.dialog.find("div").outerHeight());
								setTimeout(function () { parent.Center(); }, 200);
							}
						}
					}
					checkSize(parent);
				}
			}, 500);
		}
		checkSize(this);

		$(window).on("keyup", function (e) {
			if (parent.html.dialog.is(":visible")) {
				if (parent.windowId === Dialog._WINDOWS && e.keyCode == 27) {
					parent.Close();
				}
			}
		});
		this.html.background.on("scroll", function () {
			parent.Scroll();
		})

		DialogService.notify("created", this.html);
	},
	onShow: function (callback) {
		this._OnWindowShow = callback;
	},
	setTitle: function (title) {
		if (typeof parent == "undefined")
			var parent = this;
		parent.title = title;
		if (typeof parent.html.title != "undefined") {
			$(parent.html.title.find(".title")[0]).html(this.title);
		} else {
			parent.html.title = $("<div>");
			parent.html.title.addClass("dialog-title");
			parent.html.title.html("<div class=title>" + this.title + "</div>");
			parent.html.dialogContent.prepend(this.html.title);
			parent.html.closeButton = $("<div>");
			parent.html.closeButton.addClass("close");
			parent.html.closeButton.attr("title", "Zavřít");
			parent.html.closeButton.click(function () { parent.Close(); });
			parent.html.title.prepend(this.html.closeButton);
		}
		parent.Center();
	},
	getButtons: function () {
		return this.html.footer.find(".button");
	},
	setButtons: function (buttons) {
		if (typeof this.html.buttons != "undefined") {
			this.html.buttons = $("<div>");
			this.html.buttons.addClass("dialog-footer-button");
			if (typeof buttons.length == "undefined") { buttons = [buttons]; }
			for (i = 0; i < buttons.length; i++) {
				butt = $("<" + buttons[i].type + ">");
				butt.addClass(buttons[i].className);
				butt.addClass("button");
				butt.html(buttons[i].title);
				butt.attr("name", buttons[i].name);
				this.html.buttons.append(butt);
			}
			this.html.footer.html("");
			this.html.footer.append(this.html.buttons);
			this.html.footer.append($("<div>").css("clear", "both"));
		} else {
			this.html.footer = $("<div>");
			this.html.footer.addClass("dialog-footer");
			this.html.buttons = $("<div>");
			this.html.buttons.addClass("dialog-footer-button");
			if (typeof buttons.length == "undefined") { buttons = [buttons]; }
			for (i = 0; i < buttons.length; i++) {
				butt = $("<" + buttons[i].type + ">");
				butt.addClass(buttons[i].className);
				butt.addClass("button");
				butt.html(buttons[i].title);
				butt.attr("name", buttons[i].name);
				this.html.buttons.append(butt);
			}
			this.html.footer.append(this.html.buttons);
			this.html.footer.append($("<div>").css("clear", "both"));
			this.html.dialogContent.append(this.html.footer);
		}
		this.Center();
	},
	ShowLoading: function () {
		var parent = this;

		if (this.anonymous != true)
			this.html.background.show();

		if (parent.html.dialog.is(":visible")) {
			parent.html.dialog.hide();

			if (this.anonymous != true)
				parent.html.dialogLoading.show();

			width = parent.html.dialogLoading.outerWidth();
			height = parent.html.dialogLoading.outerHeight();

			parent.html.dialogLoading.css("width", this.html.dialog.outerWidth());
			parent.html.dialogLoading.css("height", this.html.dialog.outerHeight());
			parent.html.dialogLoading.css("margin-top", this.html.dialog.css("margin-top"));
			parent.html.dialogLoading.css("margin-bottom", this.html.dialog.css("margin-bottom"));
			parent.html.dialogLoadingContent.css("opacity", 0);
			parent.Center();

			parent.html.dialogLoading.animate({
				width: width + "px",
				height: height + "px"
			}, 400, function () { parent.html.dialogLoading.css("height", "auto"); parent.html.dialogLoadingContent.animate({ opacity: 1 }, 400); });
		} else {
			parent.html.dialogLoading.fadeIn("fast");
			parent.Center("fast");
		}
	},
	Load: function (url, param, callback) {
		var parent = this;
		//if(typeof param != "undefined"){ param = $.param(param); }

		this.ShowLoading();

		if (typeof param == "object")
			param = serialize(param);
		if (param != null && param != "")
			url = url + "?" + param;

		parent.html.dialogHtml.load(url, function (response, status, xhr) {
			if (status == "error") {
				var msg = "Omlouváme se ale při načítání nastala chyba<br>";
				parent.setTitle("Error");
				parent.setButtons(Dialog.CLOSE);
				parent.html.dialogHtml.html("<div class='red'>" + msg + xhr.status + " " + xhr.statusText + "</div>");
			}

			if (parent.anonymous != true)
				parent.Show();
			else {
				parent.html.background.hide();
			}

			parent.Center();

			if (typeof callback != "undefined" && callback != "")
				callback(parent, parent.html.dialogHtml.html(), status);

			afterPageLoad();
		});
	},
	Show: function () {
		Dialog._WINDOWS++;

		if (typeof parent == "undefined")
			var parent = this;

		$("body").css("overflow", "hidden");

		parent.html.dialog.show();
		parent.html.dialog.css("width", "auto");
		width = parent.html.dialog.outerWidth();
		height = parent.html.dialog.outerHeight();
		parent.html.dialog.hide();
		//parent.Center(true);

		//console.log(this.html.dialogLoading.width());
		this.html.dialogLoading.show();
		parent.html.dialog.css("width", this.html.dialogLoading.outerWidth());
		parent.html.dialog.css("height", this.html.dialogLoading.outerHeight());
		this.html.dialogLoading.hide();
		setTimeout(function () {
			parent.html.dialog.css("display", "table");
			parent.html.dialog.addClass("dialog-visible");
			parent.html.dialog.css("overflow", "initial");
			parent.html.dialog.css("width", parent.html.dialog.outerWidth());
			parent.html.dialog.css("height", parent.html.dialog.outerHeight());
			parent.html.dialog.css("display", "block");
			parent.html.dialog.css("overflow", "hidden");

			parent.html.dialogContent.animate({ opacity: 1 }, 400);
			setTimeout(function () {
				parent.html.dialog.css("display", "table");
				parent.html.dialog.css("overflow", "initial");
				parent.Center();

				if (parent._OnWindowShow != null)
					parent._OnWindowShow(this);
			}, 250);
			parent.Center();
		}, 250);
		if (!isMobile()) {
			parent.html.dialog.css("margin-top", this.html.dialogLoading.css("margin-top"));
			parent.html.dialog.css("margin-bottom", this.html.dialogLoading.css("margin-bottom"));
		} else {
			parent.html.dialog.css("margin-top", "999px");
		}
		parent.html.dialogContent.css("opacity", 0);

		parent.html.background.show();
		parent.html.dialog.css("display", "block");
		parent.html.dialog.css("overflow", "hidden");
		parent.html.dialogLoading.hide();
		/*
		parent.html.dialog.animate({
			width: width+"px",
			height: height+"px"
		}, 400, function() { parent.html.dialog.css("height", "auto");parent.html.dialogContent.animate({ opacity:1 }, 400);parent.Center() });
		*/
	},
	Close: function () {
		var parent = this;
		if (this._ClickOutsideCl) {
			this.html.dialog.fadeOut("fast", function () { 
				parent.html.dialog.removeClass("dialog-visible"); 
				parent.html.background.hide(); 
				parent.fnOnCancel(parent.html);
			});
		}else{
			parent.fnOnCancel(parent.html);
		}

		if (Dialog._WINDOWS == 1)
			$("body").css("overflow", "auto");
		Dialog._WINDOWS--;		
	},
	Scroll: function () {
		var parent = this;
		if (isMobile()) {
			if (parent.html.background != null) {
				//console.log(parent.html.background.scrollTop(), parseInt(parent.html.dialog.css("margin-top")), parent.html.background[0].scrollHeight - parent.html.background.outerHeight());

				var marginTop = parseInt(parent.html.dialog.css("margin-top"));
				var scrollTop = parent.html.background[0].scrollHeight - parent.html.background.outerHeight() - parent.html.background.scrollTop();

				if (parent.html.background.scrollTop() < marginTop) {
					parent.html.dialog.toggleClass("rounded-dialog", true);
					parent.html.dialog.toggleClass("scrolled-top", false);
				} else {
					parent.html.dialog.toggleClass("rounded-dialog", false);
					parent.html.dialog.toggleClass("scrolled-top", true);
				}
				if (scrollTop < 20) {
					parent.html.dialog.toggleClass("scrolled-bottom", true);
				} else {
					parent.html.dialog.toggleClass("scrolled-bottom", false);
				}
			}
		}
	},
	Center: function (overload) {
		var parent = this;
		margin = (window.innerHeight / 2) - (this.html.dialog.height() / 2);
		if (margin < 10) { margin = 10; }
		if (margin > 250) { margin = 250; }
		margin -= 1;

		this.Scroll();

		if (isMobile()) {
			var margintop = window.innerHeight - parent.html.dialogContent.outerHeight() - (parent.html.footer == null ? 0 : parent.html.footer.outerHeight());
			if (margintop < 200) { margintop = 200; }
			console.log(window.innerHeight, parent.html.dialogContent.outerHeight(), margintop);
			this.html.dialog.css({ 'cssText': 'margin-top: ' + margintop + 'px !important' });// "margin-top", "200px !important");			
		} else {
			if (overload == "fast" || 1 == 1) {
				this.html.dialogLoading.css("margin-top", margin + "px");
				this.html.dialogLoading.css("margin-bottom", margin + "px");
				this.html.dialog.css("margin-top", margin + "px");
				this.html.dialog.css("margin-bottom", margin + "px");
			} else {
				if (this.html.dialogLoading.is(':visible') && overload != true) {
					this.html.dialogLoading.animate({
						marginTop: margin + "px",
						marginBottom: margin + "px"
					}, 100, function () { });
				}

				if (this.html.dialog.is(':visible') || overload) {
					this.html.dialog.animate({
						marginTop: margin + "px",
						marginBottom: margin + "px"
					}, 100, function () { });
				}
			}
		}
	}
}

function messageBox(title, text) {
	if (typeof text == "undefined") { text = title, title = "Info"; }
	var dialog = new Dialog();
	dialog.setTitle(title);
	dialog.setButtons(Dialog.CLOSE);
	dialog.dialogHtml.html("<div class=cnt>" + text + "</div>");
	dialog.Show();
	butt = dialog.getButtons();
	$(butt[0]).click(function () { dialog.Close(); });
	return dialog;
}

function confirmBox(title, text, confirmFun, cancelFun) {
	var confirmFun = confirmFun, cancelFun = cancelFun;
	var dialog = new Dialog();
	dialog.setTitle(title);
	dialog.setButtons(Dialog.OK_CLOSE);
	if(typeof cancelFun != "undefined") {
		dialog.fnOnCancel = function(){ cancelFun(); }
	}
	dialog.dialogHtml.html("<div class=cnt>" + text + "</div>");
	dialog.Show();
	butt = dialog.getButtons();
	$(butt[0]).click(function () { dialog.Close(); });
	$(butt[1]).click(function () { if(typeof confirmFun != "undefined") { confirmFun(); } dialog.Close(); });	
}

function inputBox(title, text, value, confirmFun, cancelFun){
	var confirmFun = confirmFun, cancelFun = cancelFun;
	var dialog = new Dialog();
	dialog.setTitle(title);
	dialog.setButtons(Dialog.OK_CLOSE);
	if(typeof cancelFun != "undefined") {
		dialog.fnOnCancel = function(){ cancelFun(); }
	}
	dialog.dialogHtml.html("<div class=cnt>" + text + "<input type=text style='width: 100%;margin-top: 10px;' value=\""+value+"\"></div>");
	dialog.Show();
	butt = dialog.getButtons();
	$(butt[0]).click(function () { dialog.Close(); });
	$(butt[1]).click(function () { if(typeof confirmFun != "undefined") { confirmFun(dialog.dialogHtml.find("input").val()); } dialog.Close(); });
	dialog.dialogHtml.find("input").focus().select();
}

function InputError(input, error) {

}

function addScript(url) {
	var script = document.createElement('script');
	script.src = url;
	script.type = 'text/javascript';
	document.getElementsByTagName('head')[0].appendChild(script);
}

function showhide(id) {
	if ($(id).is(":visible")) {
		$(id).slideUp();
	} else {
		$(id).slideDown();
	}
}

function showhideclass(id, _class) {
	if (!$(id).hasClass(_class)) {
		$(id).addClass(_class);
	} else {
		$(id).removeClass(_class);
	}
}

function showhideclass_adv(id, _class, id2, _class) {
	if (!$(id).hasClass(_class)) {
		$(id).addClass(_class);
		$(id2).addClass(_class);
	} else {
		$(id).removeClass(_class);
		$(id2).removeClass(_class);
	}
}

function shohid(id, tid) {
	if ($(tid).is(":visible")) {
		$(tid).slideUp();
		$(id).removeClass("hide_box_row");
		$(id).addClass("show_box_row");
	} else {
		$(tid).slideDown();
		$(id).removeClass("show_box_row");
		$(id).addClass("hide_box_row");
	}
}

var lastlistajx = new Array();
var lastlistajw = new Array();

function ajaxcall(url) {
	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {

	});
}

function ajaxcall_draw(url, data, id, callback) {
	$.ajax({
		method: "GET",
		url: url,
		data: data
	}).done(function (msg) {
		$(id).html(msg);
		callback(msg);
	});
}

function ajaxcall_loadtext(url, id1, id2) {
	$(id1).slideUp("quick");
	$(id2).slideDown("quick");
	$(id2).html("<span class='loading small'></span> Načítám...");
	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {
		$(id2).html(msg);
	});
}

function hideSlide(id1, id2) {
	$(id2).slideUp("quick");
	$(id1).slideDown("quick");
}

function ajaxload(url, id, sh, il) {
	if (typeof lastlistajx[il] == "undefined") lastlistajx[il] = "";
	if (typeof lastlistajw[il] == "undefined") lastlistajw[il] = "";
	if (lastlistajx[il] != "") {
		$(lastlistajw[il]).removeClass("arrow_b");
		$(lastlistajw[il]).addClass("arrow_r");
		$(lastlistajx[il]).slideUp("quick");
	}
	var can = true;
	if (typeof sh != "undefined") {
		if ($(id).is(":visible")) {
			$(sh).removeClass("arrow_b");
			$(sh).addClass("arrow_r");
			$(id).slideUp("quick");
			can = false;
		} else {
			$(sh).addClass("arrow_b");
			$(sh).removeClass("arrow_r");
		}
	}
	if (can) {
		$.ajax({
			method: "GET",
			url: url
		}).done(function (msg) {
			$(id).html(msg);
			$(id).slideDown("quick");

			afterPageLoad();
		});
	}
	lastlistajx[il] = id;
	lastlistajw[il] = sh;
}

function ajaxloadurl(self, url, id) {
	var save = $(self).html();
	$(self).html("Loading...");
	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {
		$(self).html(save);
		$(id).html(msg);
		ReplaceSelect();
	});
}

function simpleajaxload(url, id) {
	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {
		$(id).html(msg);
		ReplaceSelect();
	});
}

function ajaxsend_nor(butt, url, id1, id2) {
	$(butt).html("<span class='loading small'></span> Sending...");

	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {
		$(id1).html(msg);
		hideSlide(id1, id2);
	});
}

function doAction(msg) {
	var ret = new Array();
	var data = JSON.parse(msg.trim());
	for (key in data) {
		if (data[key][0] == "html") {
			$(data[key][1]).html(data[key][2]);
		}
		else if (data[key][0] == "redirect") {
			window.location.replace(data[key][1]);
		}
		else { ret[data[key][0]] = data[key][1]; }
	}
	return ret;
}

function ajaxcallhref(butt, url) {
	var saveText = $(butt).html();
	$(butt).toggleClass("disabled");
	$(butt).html("Loading...");

	$.ajax({
		method: "GET",
		url: url
	}).done(function (msg) {
		if (msg.trim().substr(0, 1) == "{" || msg.trim().substr(0, 1) == "[") {
			var dat = doAction(msg);
			if (dat["message"] !== undefined) {
				$(butt).html(dat["message"]);
				$(butt).addClass("error");
			} else $(butt).html(saveText);
		}
		else if (msg.trim() != "ok") {
			$(butt).html(msg);
			$(butt).addClass("error");
		} else {
			$(butt).html(saveText);
		}
		$(butt).toggleClass("disabled");
	});
}

function ajaxsend(frm, butt, error, url) {
	if (url.charAt("?") == -1) url += "?";
	else url += "&";
	var saveText = $(butt).html();
	$(butt).attr("disabled", true);
	$(butt).html("<span class='loading small'></span> Sending...");

	//var dt = $(frm).serialize();

	var dt = "";
	var fields = $(frm).serializeArray();
	jQuery.each(fields, function (i, field) {
		dt += field.name + "=" + field.value.replace(new RegExp("\r\n", 'g'), "_R_N_").replace(new RegExp("#", 'g'), "_HASH_").replace(new RegExp("=", 'g'), "_QES_").replace(new RegExp("\\&", 'g'), "_ADN_D_") + "&";
	});
	dt = dt.substr(0, dt.length - 1);

	$.ajax({
		method: "GET",
		url: url + dt
	}).done(function (msg) {
		$(butt).attr("disabled", false);
		if (msg.trim().substr(0, 1) == "{" || msg.trim().substr(0, 1) == "[") {
			var dat = doAction(msg);
			if (dat["message"] !== undefined) {
				$(error).show();
				$(error).html(dat["message"]);
			}
		}
		else if (msg.trim() != "ok") {
			$(error).show();
			$(error).html(msg);
		}
		$(butt).html(saveText);
	});
}

function ajaxsend_ext(frm, butt, error, url, url2, id2) {
	if (url.charAt("?") == -1) url += "?";
	else url += "&";
	var saveText = $(butt).html();
	$(butt).attr("disabled", true);
	$(butt).html("<span class='loading small'></span> Sending...");

	var url2 = url2;
	var id2 = id2;

	//var dt = $(frm).serialize();

	var dt = "";
	$(frm).find("textarea").each(function () {
		if (isTextEditor($(this))) {
			$(this).val(getEditorText($(this)));
			console.log($(this).val());
		}
	});
	var fields = $(frm).serializeArray();
	jQuery.each(fields, function (i, field) {
		var d = field.value.replace(new RegExp("\r\n", 'g'), "_R_N_");
		d = d.replace(new RegExp("=", 'g'), "%3D");
		d = d.replace(new RegExp("#", 'g'), "_HASH_");
		d = d.replace(new RegExp("\\+", 'g'), "%2B");
		dt += field.name + "=" + d + "&";
	});
	dt = dt.substr(0, dt.length - 1);

	$.ajax({
		method: "GET",
		url: url + dt
	}).done(function (msg) {
		$(butt).attr("disabled", false);
		$.ajax({
			method: "GET",
			url: url2
		}).done(function (msg) {
			$(id2).html(msg);

			afterPageLoad();
		});
	});
}

function ajaxsend_del(frm, butt, error, url, id2) {
	var dt = "";
	var fields = $(frm).serializeArray();
	jQuery.each(fields, function (i, field) {
		var d = field.value.replace(new RegExp("\r\n", 'g'), "_R_N_");
		d = d.replace(new RegExp("=", 'g'), "%3D");
		d = d.replace(new RegExp("\\+", 'g'), "%2B");
		dt += field.name + "=" + d + "&";
	});
	dt = dt.substr(0, dt.length - 1);

	var id2 = id2;
	$(butt).html("<span class='loading small'></span> Wait...");
	$.ajax({
		method: "GET",
		url: url + "&" + dt
	}).done(function (msg) {
		//$(id2).html(msg);
		$(id2).remove();
	});
}

var lastSel = new Array();
function showInfo(_self, c, id, text) {
	$(id).show();
	$(id).html(text);
	if (typeof lastSel[c] == "undefined") { lastSel[c] = _self; }
	else { $(lastSel[c]).removeClass("sel"); lastSel[c] = _self; }
	$(_self).addClass("sel");
}

function ruleAdd(id) {
	try {
		var data = jQuery.parseJSON($(id).val());
	} catch (e) {
		$(id).val("[]");
		var data = jQuery.parseJSON($(id).val());
	}
	data[data.length] = { "type": "set value", "data": "" };
	$(id).val(JSON.stringify(data));
	ruleRedraw(id);
}

function ruleDelete(id, pos) {
	var data = jQuery.parseJSON($(id).val());
	var nata = [];
	var i = 0;
	for (_key in data) {
		if (i != pos) {
			nata[nata.length] = data[_key];
		}
		i += 1;
	}
	$(id).val(JSON.stringify(nata));
	ruleRedraw(id);
}

function ruleRedraw(id) {
	var data = jQuery.parseJSON($(id).val());
	$(id + "_cont").html("");
	var i = 0;
	var rulid = id;
	var setvalake = false;
	for (_key in data) {
		var showerror = "";
		var cont = $("<div style='padding:0px;'></div><br>");
		cont.addClass("bluecol");

		var types = $("<select id='set_value_" + i + "' style='width:100px;'></select>");
		types.attr("data-pos", i);
		var options = ["set value", "when"];
		$.each(options, function (index, key) { if (data[_key]["type"] == key) { types.append($('<option selected></option>').val(key).html(key)); } else { types.append($('<option></option>').val(key).html(key)); } });
		types.on('change', function () {
			var posi = $(this).attr("data-pos");
			var da = jQuery.parseJSON($(rulid).val());
			da[posi]["type"] = this.value;
			$(rulid).val(JSON.stringify(da));
			ruleRedraw(rulid);
		});
		cont.append(types);

		var plsnobutt = false;

		if (data[_key]["type"] == "set value") {
			var value = $("<input>");
			value.attr("type", "text");
			value.css("width", "235px");
			value.css("padding-right", "27px");
			value.attr("id", "input_data_selector_" + i + "_0");
			value.attr("data-pos", i);
			value.val(data[_key]["data"]);
			value.on('change keydown paste input', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				da[posi]["data"] = this.value;
				$(rulid).val(JSON.stringify(da));
			});
			cont.append(value);
			var inpil = $("<span>");
			inpil.attr("style", "width:0px;display: inline-block;");
			var chekb = $("<input>");
			chekb.attr("type", "checkbox");
			chekb.attr("data-id", "input_data_selector_" + i + "_0");
			chekb.attr("data-pos", i);
			chekb.attr("style", "position: relative;left: -25px;");
			chekb.attr("title", "Enable eval?");
			if (data[_key]["eval"] == "1") { chekb.attr("checked", "checked"); showerror = "Use mathematical operations only, when eval is enabled!"; }
			chekb.on('click', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				if (this.checked)
					da[posi]["eval"] = "1";
				else
					da[posi]["eval"] = "0";
				$(rulid).val(JSON.stringify(da));
			});
			inpil.append(chekb);
			cont.append(inpil);
			var buttad = $("<button>");
			buttad.addClass("blue");
			buttad.html("#");
			buttad.attr("data-id", "input_data_selector_" + i + "_0");
			buttad.on('click', function () {
				showDataDialog($(this).attr("data-id"));
			});
			cont.append(buttad);
		}
		if (data[_key]["type"] == "when") {
			var value = $("<input>");
			value.attr("type", "text");
			value.css("width", "73px");
			value.attr("id", "input_data_selector_" + i + "_1");
			value.attr("data-pos", i);
			value.val(data[_key]["val1"]);
			value.on('change keydown paste input', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				da[posi]["val1"] = this.value;
				$(rulid).val(JSON.stringify(da));
			});
			cont.append(value);
			var buttad = $("<button>");
			buttad.addClass("blue");
			buttad.html("#");
			buttad.attr("data-id", "input_data_selector_" + i + "_1");
			buttad.on('click', function () {
				showDataDialog($(this).attr("data-id"));
			});
			cont.append(buttad);

			var types = $("<select id='set_value_" + i + "_how' style='padding-left: 5px;padding-right: 2px;width:58px;' selectType=1 class='noarrow'></select>");
			types.attr("data-pos", i);
			var options = [">", "<", "==", "!="];
			$.each(options, function (index, key) { if (data[_key]["matchhow"] == key) { types.append($('<option selected></option>').val(key).html(key)); } else { types.append($('<option></option>').val(key).html(key)); } });
			types.on('change', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				da[posi]["matchhow"] = this.value;
				$(rulid).val(JSON.stringify(da));
			});
			cont.append(types);

			var value = $("<input>");
			value.attr("type", "text");
			value.css("width", "74px");
			value.attr("id", "input_data_selector_" + i + "_2");
			value.attr("data-pos", i);
			value.val(data[_key]["val2"]);
			value.on('change keydown paste input', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				da[posi]["val2"] = this.value;
				$(rulid).val(JSON.stringify(da));
			});
			cont.append(value);
			var buttad = $("<button>");
			buttad.addClass("blue");
			buttad.html("#");
			buttad.attr("data-id", "input_data_selector_" + i + "_2");
			buttad.on('click', function () {
				showDataDialog($(this).attr("data-id"));
			});
			cont.append(buttad);

			var buttdel = $("<button>");
			buttdel.attr("onClick", "ruleDelete('" + id + "', " + i + ");return false;");
			buttdel.addClass("red");
			buttdel.html("X");
			cont.append(buttdel);

			cont.append($("<br>"));
			cont.append($("<span style='display: inline-block;padding: 5px;'>Then :</span>"));

			var types = $("<select id='set_value_" + i + "_seti' style='padding-left: 5px;padding-right: 2px;width:120px;'></select>");
			types.attr("data-pos", i);
			var options = ["set value", "increase", "decrease", "disable", "require", "hide", "show"];
			$.each(options, function (index, key) { if (data[_key]["matchakce"] == key) { types.append($('<option selected></option>').val(key).html(key)); } else { types.append($('<option></option>').val(key).html(key)); } });
			types.on('change', function () {
				var posi = $(this).attr("data-pos");
				var da = jQuery.parseJSON($(rulid).val());
				da[posi]["matchakce"] = this.value;
				$(rulid).val(JSON.stringify(da));
				ruleRedraw(rulid);
			});
			cont.append(types);
			if (typeof data[_key]["matchakce"] == "undefined" || data[_key]["matchakce"] == "set value" || data[_key]["matchakce"] == "" || data[_key]["matchakce"] == "increase" || data[_key]["matchakce"] == "decrease") {
				var value = $("<input>");
				value.attr("type", "text");
				value.css("width", "195px");
				value.attr("id", "input_data_selector_" + i + "_3");
				value.attr("data-pos", i);
				value.val(data[_key]["valuexx"]);
				value.on('change keydown paste input', function () {
					var posi = $(this).attr("data-pos");
					var da = jQuery.parseJSON($(rulid).val());
					da[posi]["valuexx"] = this.value;
					$(rulid).val(JSON.stringify(da));
				});
				cont.append(value);
				var buttad = $("<button>");
				buttad.addClass("blue");
				buttad.html("#");
				buttad.attr("data-id", "input_data_selector_" + i + "_3");
				buttad.on('click', function () {
					showDataDialog($(this).attr("data-id"));
				});
				cont.append(buttad);
			} else {
				var value = $("<input>");
				value.attr("type", "text");
				value.css("width", "195px");
				value.css("background", "#e4e4e4");
				value.attr("id", "input_data_selector_" + i + "_5");
				value.attr("data-pos", i);
				value.attr("readonly", i);
				value.val(data[_key]["targetid"]);
				value.on('change keydown paste input', function () {
					var posi = $(this).attr("data-pos");
					var da = jQuery.parseJSON($(rulid).val());
					da[posi]["targetid"] = this.value;
					$(rulid).val(JSON.stringify(da));
				});
				cont.append(value);
				var buttad = $("<button>");
				buttad.addClass("blue");
				buttad.html("#");
				buttad.attr("data-id", "input_data_selector_" + i + "_5");
				buttad.on('click', function () {
					showDataDialog($(this).attr("data-id"), false);
				});
				cont.append(buttad);
			}

			plsnobutt = true;
		}

		if (!plsnobutt) {
			var buttdel = $("<button>");
			buttdel.attr("onClick", "ruleDelete('" + id + "', " + i + ");return false;");
			buttdel.addClass("red");
			buttdel.html("X");
			cont.append(buttdel);
		}

		$(id + "_cont").append(cont);
		if (setvalake && data[_key]["type"] == "set value") {
			$(id + "_cont").append($("<div class=inpoerror style='margin-bottom:3px;'>Additional value settings are not required</div>"));
		}
		if (showerror != "") {
			$(id + "_cont").append($("<div class=inpoerror style='margin-bottom:3px;'>" + showerror + "</div>"));
		}
		if (data[_key]["type"] == "set value") { setvalake = true; }
		i += 1;
	}

	afterPageLoad();
}

var current_showed_data_dialog = null;
function showDataDialog(id, em) {
	if (typeof em == "undefined") em = true;
	var dialog = new Dialog(700);
	dialog.setTitle("Data dialog picker");
	dialog.Load(_router_url + "ajax/dialog/datapicker", { inputid: id, formid: _form_id_actual, jem: em });
	current_showed_data_dialog = dialog;
}


var mouseTitleTimer, mouseTitleTimerMs = 500, mouseTitleTimerEnded = false;
function replaceTitles() {
	$("a, i, svg, li, span").each(function (i) {
		if ($(this).attr("data-title") != undefined && $(this).attr("data-title") != "" && $(this).attr("data-title")!="Slide") {
			$(this).on("mouseenter", function () {
				if ($(this).data("idtitle") == undefined) {
					$(this).removeAttr("title");
					var div = $("<div></div>");
					div.addClass("mini-popup");
					div.addClass($(this).data("title-class"))
					div.attr("id", "popup-div-" + i);
					$(this).attr("data-popup-context", "popup-div-" + i);
					var content = $("<div></div>");
					content.addClass("content");
					content.html($(this).data("title"));
					var footer = $("<div></div>");
					footer.addClass("popufooter");
					footer.html("");
					div.append("<div style='height:0px;'><div class='arrow'></div></div>");

					div.append(content);
					div.css("position", "absolute");
					div.css("top", $(this).offset().top + $(this).outerHeight());
					div.css("left", $(this).offset().left);
					$("body").append(div);
					$(this).data("idtitle", "popup-div-" + i);
					div.hide();
				}
				else
					var div = $("#" + $(this).data("idtitle"));

				var _this = $(this);
				mouseTitleTimer = setTimeout(function () {
					mouseTitleTimerEnded = true;
					div.hide();
					div.fadeIn(400);
					div.css("top", _this.offset().top + _this.outerHeight());
					div.css("left", _this.offset().left);
				}, mouseTitleTimerMs);
			});
			$(this).on("mouseleave", function () {
				clearTimeout(mouseTitleTimer);

				if (mouseTitleTimerEnded) {
					mouseTitleTimerEnded = false;

					var _this = $(this);
					setTimeout(function () {
						var div = $("#" + _this.data("idtitle"));
						div.fadeOut(400, function () { div.hide(); });
					}, 200);
				}
			});
		}
		if ($(this).attr("data-popup") != undefined && $(this).attr("data-popup") != "") {
			$(this).on("click", function () {
				if ($(this).attr("data-popup-context") != undefined) {
					var div = $("#" + $(this).attr("data-popup-context"));
					if (!div.is(':visible')) {
						div.fadeIn(400);
						div.css("position", "absolute");
						div.css("top", $(this).offset().top + $(this).outerHeight());
						div.css("left", $(this).offset().left - div.outerWidth() + $(this).outerWidth());
					} else {
						div.fadeOut(400);
					}
				} else {
					var data = JSON.parse($(this).attr("data-popup").replace(new RegExp("'", 'g'), '"'));
					var div = $("<div></div>");
					div.addClass("mini-popup");
					div.attr("id", "popup-div-" + i);

					$(this).attr("data-popup-context", "popup-div-" + i);

					var content = $("<div></div>");
					content.addClass("content");
					content.html(data["content"]);

					var title = $("<div></div>");
					title.addClass("title");
					title.html(data["title"]);

					var footer = $("<div></div>");
					footer.addClass("popufooter");
					footer.html("");

					if (data["button-ok"] != undefined) {
						var butt = $("<a></a>");
						butt.addClass("button");
						butt.attr("href", data["button-ok"][1]);
						butt.html(data["button-ok"][0]);
						footer.append(butt);
					}

					if (data["button-cancel"] != undefined) {
						var butt = $("<a></a>");
						butt.addClass("button");
						if (data["button-cancel"][1] == "#cancel") {
							butt.on("click", function () { div.fadeOut(400); return false; })
						} else {
							butt.attr("href", data["button-cancel"][1]);
						}
						butt.html(data["button-cancel"][0]);
						footer.append(butt);
					}

					if (data["button-hide"] == undefined)
						data["button-hide"] = 0;

					div.append("<div style='height:0px;'><div class='arrow " + (data["title"] != undefined && data["title"] != "" ? "intitle" : "") + "'></div></div>");
					if (data["title"] != undefined && data["title"] != "")
						div.append(title);
					div.append(content);

					if (data["button-hide"] == 0)
						div.append(footer);
					div.css("position", "absolute");
					div.css("top", $(this).offset().top + $(this).outerHeight());
					div.css("left", $(this).offset().left);
					$("body").append(div);
					div.hide();
					div.fadeIn(400);
					div.css("left", $(this).offset().left - div.outerWidth() + $(this).outerWidth());
				}
				AddSelectedDiv(div);
				return false;
			});
		}
	});
};

function hideTopBar(time) {
	Cookies.set("topBarHide", true);
	if (typeof time == "undefined")
		time = 300;

	$("body").removeClass("debug-show");
	$("#admintools").animate({
		opacity: 0,
		bottom: "-60",
	}, time, function () {
		$("#admintoolshow").animate({
			opacity: 1,
			bottom: "0",
		}, time, function () {
			// Animation complete.
		});
	});
}
function showTopBar() {
	Cookies.remove('topBarHide');
	$("body").addClass("debug-show");
	$("#admintools").css("display", "block");
	$("#admintoolshow").animate({
		opacity: 0,
		bottom: "-60",
	}, 300, function () {
		$("#admintools").animate({
			opacity: 1,
			bottom: "0"
		}, 300, function () { });
	});
}

function readyTopBar() {
	var hide = Cookies.get("topBarHide");
	if (typeof hide == "undefined" || !hide) {
		showTopBar();
	} else {
		hideTopBar(0);
	}
}

function showdebug() {
	if ($("#admintoolsdebug").css("bottom") == "30px") {
		//$(".fixme").css("position", "relative");
		$("#admintoolsdebug").animate({
			opacity: 1,
			bottom: "-500",
		}, 300, function () { });
		$("#admintools").animate({
			opacity: 1,
			bottom: "0",
		}, 300, function () { });
	} else {
		//$(".fixme").css("position", "fixed");
		$("#admintoolsdebug").animate({
			opacity: 1,
			bottom: "30",
		}, 300, function () { });
		$("#admintools").animate({
			opacity: 1,
			bottom: "0",
		}, 300, function () { });
	}
}

var last = '#log';
function togle_debug_box() {
	if ($("#debug_button").html() == ' &gt; ') {
		$("#debug_button").html(' < ');
		$('#debug_box').css('bottom', '-390px');
		$('#debug_box').css('width', '60px');
	} else {
		$("#debug_button").html(' > ');
		$('#debug_box').css('bottom', '0px');
		$('#debug_box').css('width', '100%');
	}
}
togle_debug_box();

function removeDia(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
}
function toUrl(str) {
	return removeDia(str.toLocaleLowerCase()).replace(/\s/g, '-').replace(/[&\/\\#,+()$~%.'":*?<>{}]/g,'');
}
function isNotAccesableField(id) {
	var div = $(id);
	var type = div.prop('nodeName');
	if (type == "INPUT" || type == "TEXTAREA") {
		if (div.is(":disabled") || div.is("[readonly]"))
			return true;
	}
	else if (type == "DIV") {
		return true;
	}
	return false;
}


var Slider = function(element){
	this.element = $(element);
	if(this.element.data("slider") != undefined) return;

	this.element.data("slider", this);
	this.data = {
		min: this.element.data("min"),
		max: this.element.data("max"),
		step: this.element.data("step"),
		formater: this.element.data("formater"),
		lastValue: this.element.val()
	};
	if(this.data.step < 1) this.data.step = 1;

	this._titleFormater = null;
	this.build();
};
Slider.prototype.titleFormater = function(callback) { return this._titleFormater = callback; };
Slider.prototype.isDisabled = function(){
	return this.element.is(":disabled");
};
Slider.prototype.build = function(){
	var self = this;

	if(parseInt(this.element.val()) < this.data.min) {
		this.element.val(this.data.min);
	}else if(parseInt(this.element.val()) > this.data.max) {
		this.element.val(this.data.max);
	}

	this.slider = $("<div></div>");
	this.slider.addClass("slider");
	this.slider.addClass(this.element.attr("class"));

	this.sliderPoint = $("<div></div>");
	this.sliderPoint.addClass("slider-point");
	this.slider.append(this.sliderPoint);

	if(this.data.formater != null && this.data.formater != undefined && this.data.formater != "") {
		this._titleFormater = function(value) {
			return this.data.formater.replace("$value", value);
		};
	}

	this.sliderPopup = $("<div></div>");
	this.sliderPopup.addClass("slider-popup");
	this.sliderPopup.html("<span>"+this.formatTitle(this.value())+"</span>");
	this.sliderPoint.append(this.sliderPopup);

	this.sliderBar = $("<div></div>");
	this.sliderBar.addClass("bar-holder");

	this.sliderBarPercentage = $("<div></div>");
	this.sliderBarPercentage.addClass("bar-color");
	this.sliderBarPercentage.css("width", this.percent()+"%");

	if(this.data.step > 1) {
		this.sliderStep = $("<div></div>");
		this.sliderStep.addClass("bar-stepper");

		var oneStepSize = (this.data.max - this.data.min) / this.data.step;
		var stepSize = (100/oneStepSize);
		//console.log(oneStepSize, stepSize);
		//var stepSize = this.one() * this.data.step;		
		var stepAmount = 100 / stepSize;
		for(var i = 0; i <= stepAmount; i++) {
			var oneStep = $("<div></div>");
			//oneStep.css("width", stepSize+"%");
			oneStep.css("left", (stepSize * i)+"%");
			this.sliderStep.append(oneStep);			
		}
		this.sliderBar.append(this.sliderStep);

		this.sliderStep2 = $("<div></div>");
		this.sliderStep2.addClass("bar-stepper");
		for(var i = 0; i <= stepAmount; i++) {
			var oneStep = $("<div></div>");
			//oneStep.css("width", stepSize+"%");
			oneStep.css("left", (stepSize * i)+"%");
			this.sliderStep2.append(oneStep);			
		}
		this.sliderBarPercentage.append(this.sliderStep2);
	}

	this.sliderBar.append(this.sliderBarPercentage);
	this.slider.append(this.sliderBar);
		
	this.slider.insertAfter(this.element);
	this.element.hide();

	this.onResize();
	this.onChange(null, this.value());

	this.element.watch('disabled', function () {
		$(this).data("slider").onResize();
	});
	this.element.on("change", function(event){
		$(this).data("slider").value($(this).val(), true);
	});
	this.sliderPoint.on("mousedown touchstart", function(event){
		self.beginMove(event);
	});
	$(window).on("mouseup touchend", function(event){
		self.stopMove(event);
	});
	$(window).on("mousemove touchmove", function(event){
		self.onMove(event);
	});
	$(window).on("resize", function(){ 
		self.onResize(); 
	});
	//console.log(">>slider", this.value(), this.percent(), this.one());
};
Slider.prototype.formatTitle = function(value){
	var title = this._titleFormater == null? value: this._titleFormater(value);
	return title;
}
Slider.prototype.beginMove = function(event){
	if(this.isDisabled()) return;

	var pos = getPositionFromEvent(event);
	this.slider.addClass("moving");
	this.data.startX = pos.x;
	this.data.moveX = parseInt(pos.x);
	this.data.moveLast = false;
	this.data.actualSize = this.sliderBarPercentage.outerWidth();
	this.data.lastValue = this.element.val();
	this.data.state = "MOVING";
};
Slider.prototype.onMove = function(event){
	if(this.data.state == "MOVING") {
		var pos = getPositionFromEvent(event);
		var move = pos.x - this.data.startX;
		this.data.moveX = move;

		var shouldMove = false;
		var moveTo = move;
		var value = 0;
		var oneStepSize = (this.data.max - this.data.min) / this.data.step;
		var stepSize = (100/oneStepSize);
	
		var stepAmount = 100 / stepSize;		
		var totalSize = this.slider.outerWidth();
		var oneSize = totalSize / 100;
		var actualSize = this.data.actualSize;
		var moveRight = this.data.moveX == this.data.lastX? this.data.moveLast: this.data.moveX > this.data.lastX;
		this.data.lastX = parseInt(this.data.moveX);			
		this.data.moveLast = moveRight;

		//console.log("============");
		//console.log("x => ", moveRight?">>":"<<", totalSize, actualSize, oneSize);
		var pass = 0;
		for(var i = 0; i <= stepAmount; i++) {
			var currentStep = oneSize * i;
			var markPosition = (totalSize/100) * stepSize * i;
			var markPositionOneBack = (totalSize/100) * stepSize * (i-1);
			var currentCursorPosition = actualSize + move;

			//console.log(">> ", moveRight, "actualSize", actualSize, "currentCursorPosition", currentCursorPosition, "markPosition", markPosition);
			/*console.log("y => ", 
				i, 
				"markPosition", markPosition,
				"stepAmount", stepAmount,
				"i * this.data.step", i * this.data.step, 
				"currentStep", currentStep,
				"oneSize", oneSize,
				"actualSize + move", actualSize + move,
				"oneSize * (i * this.data.step)", oneSize * (i * this.data.step)
			);*/
			//if(moveRight && actualSize + move > oneSize * (i/* * this.data.step*/)) {
			if((moveRight && currentCursorPosition > markPosition) || (!moveRight && currentCursorPosition > markPositionOneBack)) {
				moveTo = oneSize * (i * this.data.step);
				//value = ((this.data.max - this.data.min)/100) * (i/* * this.data.step*/);
				value = this.data.min + (i * this.data.step);
				pass++;
				shouldMove = true;
			}
			//else if(!moveRight && actualSize + move > oneSize * ((i - 1)/* * this.data.step*/)){
			/*else if(!moveRight && currentCursorPosition > markPositionOneBack){
				moveTo = oneSize * (i * this.data.step);
				//value = ((this.data.max - this.data.min)/100) * (i/* * this.data.step* /);
				value = this.data.min + (i * this.data.step);
				pass++;
				shouldMove = true;
			}*/
		
			if(!moveRight && value > this.data.lastValue){
				value = this.data.lastValue;
			}
			if(moveRight && value < this.data.lastValue){
				value = this.data.lastValue;
			}				
		}
		//console.log(value);

		if(shouldMove) {	
			this.data.lastValue = value;			
			if(value > this.data.max) value = this.data.max;
			var p = ((value - this.data.min) / (this.data.max - this.data.min)) * 100;
			this.sliderBarPercentage.css("width", p+"%");
			this.sliderPoint.css("left", p+"%");
			this.sliderPopup.find(">span").html(this.formatTitle(value));
			this.renderUi();
		}
	}
}
Slider.prototype.stopMove = function(event){
	if(this.data.state == "MOVING") {
		this.slider.removeClass("moving");
		this.data.state = "STOP";
		//var p = ((value - this.data.min) / (this.data.max - this.data.min)) * 100;
		var val = this.element.val();
		this.element.val(this.data.lastValue).attr("value", this.element.val()).trigger("change");
		this.data.lastValue = this.element.val();
		this.onResize();
		this.onChange(val, this.element.val());
	}
};
Slider.prototype.renderUi = function(){
	this.sliderPopup.css("left", ((this.sliderPopup.outerWidth() - this.sliderPoint.outerWidth())/2) * -1);
	this.sliderPopup.get(0).style.setProperty("--bottom-left", ((this.sliderPopup.outerWidth()  / 2) - (19 / 2))+"px");
	if(this.isDisabled())
		this.slider.addClass("disabled");
	else
		this.slider.removeClass("disabled");
};
Slider.prototype.onResize = function(){
	if(this.data.step > 1) {
		var amount = ((this.data.max - this.data.min) / this.data.step);
		var width = this.sliderBar.outerWidth();
		var stepperSize = this.sliderStep.find(">div").outerWidth();
		this.sliderStep2.find(">div").css("width", stepperSize);
		this.sliderStep2.css("width", width);
	}
	this.sliderBarPercentage.css("width", this.percent()+"%");
	this.sliderPoint.css("left", this.percent()+"%");	
	this.renderUi();
};
Slider.prototype.value = function(value, force){	
	if(value == undefined)
		return parseInt(this.element.val());	

	if(force == undefined) force = false;
	if(!force && value == this.element.val()) return;
	if(this.isDisabled()) value = this.data.lastValue;

	value = parseFloat(value);
	var isOk = value % this.data.step == 0;
	if(!isOk){
		value = Math.round(value/this.data.step) * this.data.step;
	}
	if(value < this.data.min) value = this.data.min;
	if(value > this.data.max) value = this.data.max;

	this.element.val(value).attr("value", value);
	this.onResize();
	this.onChange(null, value);
};
Slider.prototype.percent = function(){
	return ((this.element.val() - this.data.min) / (this.data.max - this.data.min)) * 100;	
};
Slider.prototype.one = function(){
	return (this.data.max - this.data.min) / 100;
};
Slider.prototype.onChange = function(oldValue, newValue){
	if(oldValue != null) {
		this.slider.removeClass("slider-value-" + oldValue);
	}else{
		this.slider.attr("class", "slider");
		if(this.isDisabled())
			this.slider.addClass("disabled");
		else
			this.slider.removeClass("disabled");
	}
	this.slider.addClass("slider-value-" + newValue);
	this.sliderPopup.find(">span").html(this.formatTitle(newValue));
};

function replaceInputes() {
	$("input[type='slider']").each(function(){
		var slider = new Slider($(this));
	});
	$("input").each(function (index, element) {
		var self = $(element);
		if (self.data("postfix") !== undefined && self.data("postfix") !== null) {
			var pre = $("<div></div>");
			pre.addClass("input-fix");
			pre.addClass(self.attr("class"));
			pre.insertBefore(self);
			pre.append(self);
			pre.on("click", function () {
				self.focus();
			});
			self.on("focus", function () {
				pre.addClass("focused");
			});
			self.on("blur", function () {
				pre.removeClass("focused");
			});
			self.on("change paste keyup", function () {
				if ($(this).val() !== "") {
					pre.removeClass("placeholder");
				} else {
					if (
						$(this).attr("placeholder") !== undefined &&
						$(this).attr("placeholder") !== ""
					) {
						pre.addClass("placeholder");
					}
				}
			});
			var postfix = $("<div></div>");
			postfix.addClass("post-fix");
			postfix.text(self.data("postfix"));
			self.data("postfix", null);
			pre.append(postfix);
			if (self.val() === "") {
				if (
					self.attr("placeholder") !== undefined &&
					self.attr("placeholder") !== ""
				)
					pre.addClass("placeholder");
			}
			if (self.hasClass("error")) {
				pre.addClass("error");
				self.removeClass("error");
			}
			self.removeClass("input");
		} else if (self.data("title") !== undefined && self.data("title") !== null) {
			var title = $("<div></div>");
			title.text(self.data("title"));
			title.addClass("title");
			self.data("title", null);

			var pre = $("<div></div>");
			pre.insertBefore(self);
			if (self.val() === "") pre.addClass("empty");
			pre.addClass("input-fix input-line");
			pre.append(self);
			pre.prepend(title);
			self.removeClass("input");
			self.on("change paste keyup keydown", function () {
				if ($(self).val() === "") {
					pre.addClass("empty");
				} else {
					pre.removeClass("empty");
				}
			});
			pre.on("click", function () {
				self.focus();
			});
			self.on("focus", function () {
				pre.addClass("focused");
			});
			self.on("blur", function () {
				pre.removeClass("focused");
			});
			if (self.hasClass("error")) {
				pre.addClass("error");
				self.removeClass("error");
			}
		}
	});
}

var afterPageLoad = () => {
	if(typeof tinymce != "undefined")
		tinymce.remove();

	setTimeout(() => { loadeditor(); }, 100);
	ReplaceSelect();
	//ContextMenuClickable();
	replaceTitles();
	initializeSpecialComponents();
	replaceInputes();
}
$(function () {
	initializeSpecialComponents();
	replaceTitles();
	replaceInputes();
});

/** Extension JQuery */
jQuery.expr.filters.offscreen = function (el) {
	var rect = el.getBoundingClientRect();
	return (
		(rect.x + rect.width) < 0
		|| (rect.y + rect.height) < 0
		|| (rect.x + rect.width > window.innerWidth || rect.y + rect.height > window.innerHeight)
	);
};

/**
 * Check if {} or [] has any key 
 * 
 * {}, [] return false
 * 
 * {key: 1}, [1] return true
 * 
 * @param {*} object - Array or Object
 */
function isAnyKey(object) {
	for (var prop in object) {
		if (object.hasOwnProperty(prop)) {
			return true;
		}
	}
	return false;
}

/**
 * Duplicate missing props from defaultObject to object
 * @param {*} object 
 * @param {*} defaultObject 
 * @return {*} object
 */
function duplicateMissing(object, defaultObject) {
	try {
		return { ...defaultObject, ...object };
	} catch{
		for (var k in defaultObject) {
			if (!object.hasOwnProperty(k))
				object[k] = defaultObject[k];
		}
		return object;
	}
}

function copyArray(object) {
	try {
		return [...object];
	} catch{
		return object.slice();
	}
}

function copyObject(src) {
	return Object.assign({}, src);
}

$.fn.isfixed = function () {
	var el = this[0];
	while (typeof el === 'object' && el.nodeName.toLowerCase() !== 'body') {
		if (window.getComputedStyle(el).getPropertyValue('position').toLowerCase() === 'fixed') return true;
		el = el.parentElement;
	}
	return false;
};

var defaultOptions = { side: "right", closeButton: true, classes: "" };
var Menu = function (self, items, options) {
	this.id = void 0;
	this.ul = void 0;
	this.url = null;
	this.self = self;
	this.items = items;
	this.open = false;
	this.callback = void 0;
	this.loading = void 0;
	this.haveIcon = false;
	this.isTop = false;
	this.isFixed = false;
	this.options = options == undefined ? defaultOptions : duplicateMissing(options, defaultOptions);
	this._build();
};
/** Private method to build menu 
 * @private
*/
Menu.prototype._build = function () {
	if (this.id != void 0) {
		this.id.remove();
	}
	var builder = $("<div></div>");
	builder.addClass("content-menu");
	builder.addClass(this.options.classes);

	var _self = this;
	var ul = $("<ul></ul>");
	builder.append(ul);

	for (var prop in this.items) {
		var cr = this.items[prop];
		if (!this.items.hasOwnProperty(prop)) continue;
		if (cr.constructor.name == "String") continue;
		if (cr.icon != undefined || cr.type == "check") {
			this.haveIcon = true;
			break;
		}
	}

	var i = 0;
	var obj = this.items;
	for (var prop in obj) {
		if (!obj.hasOwnProperty(prop)) continue;
		var cr = obj[prop];
		var li = $("<li></li>");
		var icon;
		if (cr.constructor.name != "String" && cr.class != undefined)
			li.addClass(cr.class);
		if (cr.constructor.name != "String" && cr.type == "line") {
			li.append("<div class=line></div>");
		} else {
			var a = $("<a></a>");
			li.append(a);
			if (cr.constructor.name == "String") {
				a.html(cr);
				a.data("key", cr);
			} else {
				if (cr.color != undefined) {
					a.css("color", cr.color);
				}
				if (cr.icon != undefined || this.haveIcon || cr.type == "check") {
					if (cr.type == "check") {
						iconcheck = $("<i class='checkbox'></i>");
						if (cr.ischecked)
							iconcheck.addClass("checked");
						a.append(iconcheck);
					}
					if (cr.type == "image" || cr.type == "image-big") {
						icon = $("<i class='" + cr.type + "'></i>");
						icon.css("backgroundImage", "url(" + cr.icon + ")");
					}
					else if (cr.icon == undefined)
						icon = $("<i class='empty'></i>");
					else
						icon = $("<i class='" + cr.icon + "'></i>");

					var text = $("<span class=title></span>");
					text.html(cr.text);
					if (cr.subtext != undefined) {
						var stext = $("<span class=subtitle></span>");
						stext.html(cr.subtext);
						li.addClass("width-subtitle");
					}
					a.append(icon);
					a.append(text);
					if (cr.subtext != undefined)
						a.append(stext);
				} else {
					var text = $("<span></span>");
					text.html(cr.text);
					a.append(text);
				}
				a.data("key", cr.key == undefined ? cr.text : cr.key);
				a.addClass(cr.classes);
				a.attr("href", cr.href);
			}
			a.data("pos", i);
			a.on("click", function () {
				if (cr.type == "check") {
					cr.ischecked = !cr.ischecked;
					iconcheck.toggleClass("checked");
				}
				if (_self.callback != undefined)
					_self.callback(_self, $(this).data("pos"), $(this).data("key"));
				else
					_self.close();
				if (cr.click != undefined)
					cr.click(self);
			});
		}
		ul.append(li);
		i++;
	}

	if (this.options.closeButton && this.items != null) {
		var li = $("<li></li>");
		li.addClass("nopadd");
		var line = $("<div class=line></div>");
		li.append(line);
		var a = $("<a></a>");
		li.append(a);
		var close = $("<div class=lclose>Zavřít</div>");
		close.on("click", function () {
			_self.close();
		});
		a.append(close);
		ul.append(li);
	}

	var li = $("<li></li>");
	li.addClass("nopadd");
	var a = $("<a></a>");
	li.append(a);
	var close = $("<div class=lclose style='text-align:center;'><i class='fas fa-circle-notch fa-spin'></i></div>");
	close.on("click", function () {
		_self.close();
	});
	a.append(close);
	ul.append(li);
	this.loading = li;
	this.loading.hide();

	$("body").append(builder);
	this.sizeSelf = { width: builder.width(), height: builder.height() };
	this.id = builder;
	this.ul = ul;
	builder.hide();

	$(window).on("resize scroll", function () { _self.position(); });
	$(document).mouseup(function (e) {
		if (_self.ul != null && !_self.ul.is(e.target) && _self.ul.has(e.target).length === 0 && _self.open) {
			_self.close();
		}
	});
}
Menu.prototype.get = function () {
	return this.ul;
}
Menu.prototype.click = function (callback) {
	this.callback = callback;
}
Menu.prototype.close = function () {
	//if(this.isTop)
	//	this.ul.css("top", this.ul.offset().top + this.ul.outerHeight());	
	this.ul.css("overflow", "hidden");
	this.ul.css("max-height", 0);
	var _self = this;
	setTimeout(function () {
		_self.id.hide();
		_self.open = false;
	}, 300);
}
Menu.prototype.position = function () {
	if (!this.id.is(":visible"))
		return;

	if (this.ul.outerWidth() != $(window).width()) {
		this.sizeSelf = { width: this.ul.outerWidth(), height: this.ul.outerHeight() };
	}
	var pos = $(this.self).offset();
	//console.log(pos);
	var size = { width: $(this.self).outerWidth(), height: $(this.self).outerHeight() };

	var hei;
	if ($(window).outerWidth() <= 450) {
		hei = $(window).outerHeight();
	} else if (this.isFixed) {
		hei = $(window).outerHeight() - this.ul.position().top;
	} else {
		hei = $(window).outerHeight() - this.ul.offset().top;
	}
	this.ul.css("max-height", hei);

	var _self = this;
	setTimeout(function () {
		var hei = $(window).outerHeight() - _self.ul.offset().top;
		if (hei < 150) hei = 150;
		if ($(window).outerWidth() <= 450) {
			hei = $(window).outerHeight();
		}
		if (hei > _self.ul.outerHeight() || _self.isFixed)
			_self.ul.css("max-height", _self.ul.outerHeight());

		if (_self.id.is(':offscreen') && hei == 150 && $(window).outerWidth() > 450) {
			_self.isTop = true;
			_self.ul.css("max-height", "none");
			setTimeout(function () {
				_self.ul.css("max-height", _self.ul.outerHeight());
				_self.id.css("top", pos.top - _self.sizeSelf.height + size.height);
			}, 100);
		} else {
			_self.isTop = false;
		}
	}, 100);

	if (this.self.isfixed()) {
		//this.id.css("position", "fixed");
		this.id.css("position", "absolute");
		this.isFixed = true;
	}
	else {
		this.id.css("position", "absolute");
		this.isFixed = false;
	}

	if (this.options.side == "left") {
		this.id.css("top", pos.top);
		this.id.css("left", pos.left - this.sizeSelf.width + size.width);
	} else {
		this.id.css("top", pos.top);
		this.id.css("left", pos.left);
	}
}
/** Display menu */
Menu.prototype.show = function (newself) {
	if (newself != void 0)
		this.self = newself;

	var _self = this;
	this._load(function () { _self._show(); });
}
Menu.prototype._show = function () {
	this.id.show();

	//this.position();	
	//START POSITION
	var size = { width: $(this.self).outerWidth(), height: $(this.self).outerHeight() };
	this.sizeSelf = { width: this.ul.outerWidth(), height: this.ul.outerHeight() };
	var pos = $(this.self).offset();
	if (this.self.isfixed()) {
		//this.id.css("position", "fixed");
		this.id.css("position", "absolute");
		this.isFixed = true;
	}
	else {
		this.id.css("position", "absolute");
		this.isFixed = false;
	}
	if (this.options.side == "left") {
		this.id.css("top", pos.top);
		this.id.css("left", pos.left - this.sizeSelf.width + size.width);
	} else {
		this.id.css("top", pos.top);
		this.id.css("left", pos.left);
	}
	//END POSITION

	//this.ul.css("max-height", 0);
	this.ul.css("max-height", $(window).outerHeight());
	var _self = this;
	setTimeout(function () {
		_self.ul.css("overflow", "auto");
		_self.open = true;
		_self.position();
	}, 500);
	/*
	setTimeout(function(){
		_self.ul.css("max-height", $(window).outerHeight());		
		_self.open = true;
		setTimeout(function(){
			_self.ul.css("overflow", "auto");	
			if($(window).outerHeight() > _self.ul.outerHeight())
				_self.ul.css("max-height", _self.ul.outerHeight());
			_self.position();
		}, 200);
	}, 100);*/

}
/** Load menu from JSON url
 * @param {string} url - Url for load the JSON config for the menu
 * @param {function} callback - After load you can call another function
 */
Menu.prototype.load = function (url) {
	this.url = url;
}
Menu.prototype._load = function (callback) {
	var callback = callback;
	if (this.url == null) {
		callback();
		return;
	}
	this.position();
	this._show();
	this.loading.show();
	var _self = this;
	$.getJSON(this.url, function (data) {
		_self.url = null;
		_self.items = data;
		_self._build();
		_self.loading.hide();
		callback();
	});
}

var Toast = function (text) {
	this.text = text;
}
Toast.prototype.show = function () {

}

$("input[type=rating]").each(function (e) {
	$(this).hide();
	var value = $(this).val();
	var max = $(this).data("max");

	var div = $("<div></div>");
	div.addClass("rating");
	div.data("input", $(this));
	div.data("max", max);

	var stars = [];

	for (var i = 1; i <= max; i++) {
		var star = $("<div></div>");
		stars.push(star);
		star.addClass("star");
		star.data("i", i);
		star.data("input", $(this));
		star.attr("title", i);
		if (value >= i) {
			star.addClass("on");
		}
		star.mouseover(function () {
			if ($(this).data("input").is(':disabled'))
				return;
			for (var a = 1; a <= $(this).data("i"); a++) {
				stars[a - 1].addClass("on");
			}
		});
		star.click(function () {
			if ($(this).data("input").is(':disabled'))
				return;
			div.data("input").val($(this).data("i"));
			for (var a = 1; a <= $(this).data("i"); a++) {
				stars[a - 1].addClass("on");
			}
		});
		star.mouseout(function () {
			if ($(this).data("input").is(':disabled'))
				return;
			for (var a = 1; a <= div.data("max"); a++) {
				stars[a - 1].removeClass("on");
			}
			for (var a = 1; a <= div.data("input").val(); a++) {
				stars[a - 1].addClass("on");
			}
		});
		div.append(star);
	}

	if (!$(this).is(":disabled")) {
		var mob = $("<span></span>");
		mob.addClass("mobile");
		var rem = $("<button>-</button>");
		rem.data("input", $(this));
		rem.addClass("btn btn-danger btn-sm");
		rem.click(function (e) {
			if ($(this).data("input").is(':disabled'))
				return;
			var q = div.data("input").val() - 1; if (q < 1) q = 1;
			div.data("input").val(q);
			for (var a = 1; a <= div.data("max"); a++) {
				stars[a - 1].removeClass("on");
			}
			for (var a = 1; a <= div.data("input").val(); a++) {
				stars[a - 1].addClass("on");
			}
			e.preventDefault();
		});
		mob.append(rem);

		var add = $("<button>+</button>");
		add.data("input", $(this));
		add.addClass("btn btn-primary btn-sm");
		add.click(function (e) {
			if ($(this).data("input").is(':disabled'))
				return;
			var q = parseInt(div.data("input").val()) + 1; if (q > div.data("max")) q = div.data("max");
			div.data("input").val(q);
			for (var a = 1; a <= div.data("max"); a++) {
				stars[a - 1].removeClass("on");
			}
			for (var a = 1; a <= div.data("input").val(); a++) {
				stars[a - 1].addClass("on");
			}
			e.preventDefault();
		});
		mob.append(add);
		div.append(mob);
	}
	$(this).after(div);
});

var ActionButton = function () {
	this.button = $("<div class='action-button'></div>");
	this.button.css("transform", "scale(0)");
	this.ispan = $("<i class=\"\"></i>");
	this.tspan = $("<span class=text></span>");
	this.tspan.hide();
	this.button.append(this.ispan);
	this.button.append(this.tspan);
	this.callback = void 0;
	var _self = this;
	$("body").append(this.button);
	this.button.on("click", function () {
		if (_self.callback != void 0)
			_self.callback($(this), _self);
	});
	$(window).on("resize scroll touch click mouseup", function () { _self.onscroll(); });
	this.onscroll();
	//this.show();
}
ActionButton.prototype.onscroll = function () {
	if ((window.scrollY + window.innerHeight) >= $(document).height() - 20 || $(document).height() == $(window).height()) {
		this.showText();
	} else {
		this.hideText();
	}
}
ActionButton.prototype.setText = function (text) {
	this.tspan.html(text);
}
ActionButton.prototype.show = function () {
	this.button.css("transform", "scale(1)");
	var _self = this;
	setTimeout(function () { _self.onscroll(); }, 100);
}
ActionButton.prototype.hide = function () {
	this.button.css("transform", "scale(0)");
}
ActionButton.prototype.showText = function () {
	var _self = this;
	_self.tspan.css("display", "inline-block");
	this.tspan.fadeIn(500, function () { _self.tspan.css("display", "inline-block"); });
	var width = this.tspan.outerWidth();
	if (this.saveWidth == undefined)
		this.saveWidth = this.button.outerWidth();
	this.button.css("width", width + 50);
}
ActionButton.prototype.hideText = function () {
	this.tspan.fadeOut(500);
	this.button.css("width", this.saveWidth);
}
ActionButton.prototype.onclick = function (callback) {
	this.callback = callback;
}
ActionButton.prototype.changeIcon = function (icon) {
	if (this.ispan.attr("class") == "") {
		this.ispan.attr("class", icon);
		return;
	}
	this.hideText();
	var _self = this;
	var _icon = icon;
	setTimeout(function () {
		$({ deg: 0 }).animate({ deg: 360 }, {
			duration: 300,
			step: function (now) {
				if (now > 360 / 2)
					_self.ispan.attr("class", _icon);
				_self.ispan.css({
					transform: 'rotate(' + now + 'deg)'
				});
			},
			complete: function () {
				_self.onscroll();
			}
		});
	}, 100);
}

$(function () {
	$(".card").each(function () {
		var _self = $(this);
		if ($(this).data("expandable") == true) {
			var clicked = $(this).find(".title");
			console.log(clicked);
			clicked.on("click", function () {
				_self.toggleClass("expanded");
				var content = _self.find(".content");
				if (_self.hasClass("expanded")) {
					content.css("max-height", "fit-content");
					var height = content.outerHeight();
					content.css("max-height", "0px");
					setTimeout(function () {
						content.css("max-height", height);
					}, 100);
				} else {
					content.css("max-height", "0px");
				}
			});
		}
	});
	$(".swipable").each(function () {
		var _self = $(this);
		if ($(this).data("swipable") == true) {
			$(this).on("touchstart", function (e) {
				$(this).data("start-x", e.originalEvent.touches[0].pageX);
				$(this).data("page-scroll-y", window.scrollY);
			});
			$(this).on("touchmove", function (e) {
				if ($(this).data("start-x") == NaN)
					return;
				if (window.scrollY != $(this).data("page-scroll-y")) {
					$(this).data("start-x", NaN);
					$(this).trigger("touchend");
				} else {
					var movex = e.originalEvent.touches[0].pageX;
					var newx = movex - $(this).data("start-x");
					if (Math.abs(newx) > 10) {
						$(this).find(".left").hide();
						$(this).find(".right").hide();
						if (newx > 0) {
							$(this).find(".right").show();
							if ($(this).find(".right").length == 0 && newx > 30) {
								newx = 30;
							}
						} else {
							$(this).find(".left").show();
							if ($(this).find(".left").length == 0 && newx < -30) {
								newx = -30;
							}
						}
						$(this).find(".main").css("left", newx);
					}
				}
			});
			$(this).on("touchend", function (e) {
				if (Math.abs($(this).find(".main").position().left) > $(this).find(".main").outerWidth() / 2) {
					if ($(this).find(".main").position().left > 0)
						$(this).find(".main").animate({ left: $(this).find(".main").outerWidth() }, 200);
					else
						$(this).find(".main").animate({ left: $(this).find(".main").outerWidth() * -1 }, 200);
					$(this).trigger("swiped", [$(this).find(".main").position().left < 0 ? "left" : "right"]);
				} else {
					$(this).find(".main").animate({ left: 0 }, 200);
				}
			});
		}
	});
});

/* REMOVE, DEPRECATED use class Uploader | */
function dropZoneFinishUpload(event, result, name) {
	console.error("DEPRECATED USE Uploader");
	var data = JSON.parse(result);
	if (data.error != "") {
		NotificationCreate("Error when uploading", data.error, "#", "error");
		Dropzone.forElement("#" + name + "-upload").removeAllFiles(true);
	} else {
		$("#" + name + "-input").val(data.url);
		$("#" + name + "-img-view-url").css("background-image", "url(" + $("#" + name + "-img-view-url").data("url") + data.url + ")");
		$("#" + name + "-img-view").find(".text span").html(data.url);
		dropZoneShowUpload(name);
	}
}
function dropZoneRemoveUpload(name) {
	console.error("DEPRECATED USE Uploader");
	$("#" + name + "-img-view").hide();
	$("#" + name + "-input").val("");
	$("#" + name + "-upload").show();
	Dropzone.forElement("#" + name + "-upload").removeAllFiles(true);
}
function dropZoneShowUpload(name) {
	console.error("DEPRECATED USE Uploader");
	$("#" + name + "-img-view").show();
	$("#" + name + "-upload").hide();
}
/* REMOVE, DEPRECATED use class Uploader ^ */

SelectTotal = 1;
function ReplaceSelect() {
	$("select, input[data-list], a[data-list]").each(function () {
		if($(this).data("selectable") == true) {			
			return;
		}

		var s = new Select(this);
		s.Render();
	});
}

var Select = function (element) {
	this.element = $(element);
	this.element.data("selectable", true);

	this.type = element.tagName; /* select-one, INPUT, A */
	this.disabled = this.element.is(":disabled") || this.element.hasClass("disabled");
	this.selectable = true;
	this.dataCallback = null;

	this.list = $("<div></div>");
	this.open = false;
	this._onChangeEvent = function () { };
	
	if(this.type == "INPUT") {
		this.lastSelected = this.element.val();
		this.values = this.element.data("list").split(';');
		this.type = "text";
		this.dataCallback = this.element.data("callback");
	}else if(this.type == "A"){
		this.lastSelected = this.element.data("value");
		this.values = this.element.data("list").split(';');
		this.type = "button";
		this.selectable = this.element.data("selectable");
		this.dataCallback = this.element.data("callback");
	}else{
		this.lastSelected = this.element.find("option:selected");
		if(this.lastSelected.length == 0) {
			this.lastSelected = $(this.element.find("option")[0]);
		}
		this.values = this.element.find("option");
	}

	this.isSearchable = this.element.data("search");
	this.iconWidth = this.element.data("iconwidth");
};

Select.prototype.hasDataCallback = function(){
	return this.dataCallback != null;
}
Select.prototype.getValues = function(type){
	if(type == undefined) type = "default";

	if(this.hasDataCallback()){
		return window[this.dataCallback](this, type, this._getSelected());
	}
	return this.values;
}

Select.prototype.Render = function () {
	var self = this;
	var select = $("<div></div>");
	this.select = select;
	select.addClass(this.element.attr("class"));
	if(this.disabled) {
		select.addClass("disabled");
	}
	select.attr("style", this.element.attr("style"));	

	var slct = this.lastSelected;/*this.element.find("option:selected");
	if (slct.length == 0) {
		slct = $(this.element.find("option")[0]);
	}*/

	if(this.type == "button") {
		this.select = this.element;
		this.element.data("select", this);
		this.element.on("click", function(event){
			event.preventDefault();
			var select = $(this).data("select");
			select.Click(event);			
		});
	}
	else if(this.type == "text") {
		this.select.insertAfter(this.element);
		this.select.addClass("input-list");

		this.text_helper = $("<div></div>");
		this.text_helper.addClass("text-helper");
		this.select.append(this.text_helper);

		this.select.append(this.element);

		this.opener = $("<div></div>");
		this.opener.addClass("opener");

		this.opener.data("select", this);
		this.opener.on("click", function(event){
			var select = $(this).data("select");
			select.Click(event);
		});

		this.select.append(this.opener);

		this.element.data("select", this);
		this.element.on("focus", function(){
			var select = $(this).data("select");
			select.select.addClass("focused");
		});
		this.element.on("blur", function(){
			var select = $(this).data("select");
			select.select.removeClass("focused");
		});
		this.element.on("keyup change keydown", function(e){			
			var select = $(this).data("select");
			select.HandleHelper(e);
		});
	}else{
		select.addClass("select-dropdown");

		this.element.hide();

		var icon = $("<div></div>");
		icon.addClass("icon");
		select.append(icon);
		if(slct.data("icon") == undefined){
			icon.hide();
		}else{
			icon.css("background-image", "url("+slct.data("icon")+")");
			if(this.iconWidth != undefined){
				icon.css("width", this.iconWidth);
			}
		}

		var text = $("<div></div>");
		text.addClass("text");
		
		text.text(slct.text());
		this.select.data("value", slct.val());
		select.append(text);

		select.insertAfter(this.element);

		select.on("click", function (event) {
			self.Click(event);
		});
	}

	this.list.addClass("select-list");
	//this.list.insertAfter(select);
	$("body").append(this.list);
	this.list.hide();
	
	this.element.on("change", function (event) {
		self.Change(event, false);
	});
	$(document).mouseup(function (e) {
		var container = self.list;
		if (self.open) {
			if (!container.is(e.target) && container.has(e.target).length === 0) {
				self.CloseList();
			}
		}
	});
	$("body").on("keyup", function (event) {
		self.KeyUp(event);
	});
	$("body").on("keydown", function (event) {
		self.KeyDown(event);
	});
	$(window).resize(function() {
		self.Reposite();
	});
	$(window).scroll(function() {
		self.Reposite();
	});

	return select;
};

Select.prototype.HandleHelper = function (event){
	if(event.keyCode == 39 && event.type == "keyup") {
		if(this.text_helper.text() != "") {
			this.element.val(this.text_helper.text()).trigger("change");
			this.text_helper.text("");
		}
		return;
	}
	var text = this.element.val().toLowerCase();
	this.text_helper.text("");
	if(text == "") { return; }
	var found = "";
	$(this.getValues("search")).each(function(i, e){
		if(found == "" && e.toLowerCase().substr(0, text.length) == text) {
			found = e;
		}
	});
	if(found == text) {
		found = "";	
	}
	this.text_helper.text(found);
}

Select.prototype.KeyDown = function (event) {
	if (!this.open) return;
	if (event.keyCode === 40 || event.keyCode === 38) {
		event.preventDefault();
	}
}
//down 40, up 38
Select.prototype.KeyUp = function (event) {
	if (!this.open) return;

	if (event.keyCode === 40 || event.keyCode === 38) {
		if (!this.list.hasClass("active-cursor")) {
			this.list.addClass("active-cursor");
		} else {
			var sel = this.list.find(".cursor");
			sel.removeClass("cursor");
			if (event.keyCode === 40) {
				var next = sel.next();

				while ((next.hasClass("disabled") && next[0] !== next.next()[0]) || next.length === 0) {
					if (next.length === 0) {
						var lis = this.list.find("li");
						next = $(lis[0]);
					} else {
						next = next.next();
					}
				}

				next.addClass("cursor");
			} else if (event.keyCode === 38) {
				var prev = sel.prev();

				while ((prev.hasClass("disabled") && prev[0] !== prev.prev()[0]) || prev.length === 0) {
					if (prev.length === 0) {
						var lis = this.list.find("li");
						prev = $(lis[lis.length - 1]);
					} else {
						prev = prev.prev();
					}
				}

				prev.addClass("cursor");
			}
		}

		var obj = this.list.find(".cursor");
		this.list.scrollTop(0);

		if(obj.length != 0) {
			var childOffset = this.getScroll(obj);
			if (childOffset.top !== 0) {
				//this.list.animate({ scrollTop: childOffset.top }, 100, function() {});
				this.list.scrollTop(childOffset.top);
			}
		}		
	} else if (event.keyCode === 13) {
		var sel = this.list.find(".cursor");
		sel.trigger("click");
	}
};
Select.prototype._getSelected = function(){
	if(this.type == "button" || this.type == "text") {
		return this.element.data("value");
	}
	return this.element.find("option:selected");
}
Select.prototype.Change = function (event, fromme) {
	var text = this.select.find(">.text");
	var icon = this.select.find(">.icon");
	var selected = this._getSelected();

	if(this.type == "button" || this.type == "text") {
		text.text(selected);
		this.select.data("value", selected);
	}else{
		text.text(selected.text());
		var dicon = selected.data("icon");
		if(dicon == undefined) {
			icon.hide();
		}else{
			icon.show();
			icon.css("background-image", "url("+dicon+")");
			if(this.iconWidth != undefined){
				icon.css("width", this.iconWidth);
			}
		}
		this.select.data("value", selected.val());
	}	

	if(this.type == "button" || this.type == "text") {
		this._onChangeEvent(this, this.element, this.select);
		/*if (fromme !== false) {
			this.element.trigger("change");
		}*/
	}else{
		if (this.lastSelected[0] !== this.element.find("option:selected")[0]) {
			this.lastSelected = this.element.find("option:selected");
			this._onChangeEvent(this, this.element, this.select);
			if (fromme !== false) {
				this.element.trigger("change");
			}
		}
	}
};

Select.prototype.OnChange = function (callback) {
	this._onChangeEvent = callback;
};

Select.prototype.Click = function (event) {
	if (this.list.is(":visible")) return;
	this.ShowList();
};

Select.prototype.Resize = function(){
	var self = this;

	this.list.show();

	var sizeOld = {
		width: this.list.outerWidth(true),
		height: this.list.outerHeight(true)
	};

	//this.list.css("width", "initial");
	this.list.css("height", "initial");
	this.list.css("overflow", "hidden");
	
	var size = {
		width: this.list.outerWidth(true),
		height: this.list.outerHeight(true)
	};

	if (size.width + this.list.offset().left > $(window).outerWidth(true)) {
		size.width = this.select.outerWidth();//$(window).outerWidth(true) - this.list.offset().left * 2 + 3;
	}
	this.list.css("overflow", "");
	//this.list.css("width", this.select.outerWidth());
	this.list.css("height", sizeOld.height);

	var obj = this.list.find(".selected");
	if (obj.length == 0) {
		obj = $(this.list.find("li")[0]);
	}
	var childOffset = this.getScroll(obj);

	this.list.animate({
			height: size.height,
			scrollTop: 0,
			scrollTop: childOffset.top
		},
		100
	);
};

Select.prototype.getPosition = function(){
	var offsetTop = 5;
	this.list.css("width", "auto");
	var size = {
		width: this.list.outerWidth(true),
		height: this.list.outerHeight(true),
		top: this.select.offset().top + this.select.outerHeight() + offsetTop,
		left: this.select.offset().left,
		offsetTop: offsetTop
	};
	if (size.width + this.list.offset().left > $(window).outerWidth(true)) {
		size.width = this.select.outerWidth(true);//$(window).outerWidth(true) - this.list.offset().left * 2 + 3;
	}
	if(size.width < this.select.outerWidth(true)) {
		size.width = this.select.outerWidth(true);
	}
	var isOverHeight = $(window).outerHeight(true) + $(window).scrollTop() < this.select.offset().top + this.select.outerHeight() + size.height;
	if(isOverHeight) {
		size.top = this.select.offset().top - size.height - offsetTop;
	}
	var obj = this.list.find(".selected");
	if (obj.length == 0) {
		obj = $(this.list.find("li")[0]);
	}
	size.childOffset = this.getScroll(obj);
	return size;
};

Select.prototype.Reposite = function(){
	if (!this.open) return;
	var pos = this.getPosition();
	this.list.css({
			height: pos.height,
			width: pos.width,
			top: pos.top,
			left: pos.left,
			scrollTop: pos.childOffset.top
		});
};

Select.prototype.ShowList = function () {
	if(this.disabled) return;
	
	var self = this;
	this.RenderList();
	this.list.removeClass("active-cursor");
	this.list.show();
	this.select.addClass("opened");

	this.list.css("width", "initial");
	this.list.css("height", "initial");
	this.list.css("overflow", "hidden");
	this.list.css("opacity", 0);

	var size = this.getPosition();/*{
		width: this.list.outerWidth(true),
		height: this.list.outerHeight(true),
		top: this.select.offset().top + this.select.outerHeight()
	};*/

	this.list.css("left", this.select.offset().left);
	this.list.css("top", size.top);
	if (this.list.outerWidth() < this.select.outerWidth(true)) {
		this.list.css("width", this.select.outerWidth(true));
	}

	if (size.width + this.list.offset().left > $(window).outerWidth(true)) {
		size.width = this.select.outerWidth(true);//$(window).outerWidth(true) - this.list.offset().left * 2 + 3;
	}
	if(size.width < this.list.outerWidth(true)) {
		size.width = this.list.outerWidth(true);
	}
	var isOverHeight = $(window).outerHeight(true) + $(window).scrollTop() < this.select.offset().top + this.select.outerHeight() + size.height;
	if(isOverHeight) {
		this.list.css("top", this.select.offset().top);
		size.top = this.select.offset().top - size.height - size.offsetTop;
	}

	this.list.css("overflow", "");
	this.list.css("width", size.width);
	this.list.css("height", 0);	
	this.list.css("left", this.select.offset().left);

	this.list.scrollTop(0);
	var obj = this.list.find(".selected");
	if (obj.length == 0) {
		obj = $(this.list.find("li")[0]);
	}
	var childOffset = this.getScroll(obj);
	//this.list.scrollTop(childOffset.top - 1);
	//this.list.animate({ scrollTop: childOffset.top }, 1000, function() {});

	this.list.animate(
		{
			opacity: 1,
			//width: size.width + 1,
			height: size.height,
			top: size.top,
			scrollTop: childOffset.top
		},
		100,
		function () {
			self.open = true;
		}
	);
};

Select.prototype.getScroll = function (element) {
	var obj = element;
	var childPos = obj.offset();
	var parentPos = obj.parent().offset();
	return {
		top: childPos.top - parentPos.top,
		left: childPos.left - parentPos.left
	};
};

Select.prototype.CloseList = function () {
	var self = this;
	this.open = false;
	this.list.css("overflow", "hidden");
	this.select.removeClass("opened");
	var size = {
		width: this.select.outerWidth(),
		height: this.select.outerHeight()
	};
	this.list.animate(
		{
			//width: size.width,
			height: 0/* size.height */,
			opacity: 0
		},
		100,
		function () {
			self.list.animate({ opacity: 0 }, 100, function () {
				self.list.hide();
			});
		}
	);
};

Select.prototype._select = function (element) {
	var self = this;
	var i = 0;

	if(this.type == "button") {
		this.element.data("value", element.text()).trigger("change", [element.text()]);
	}
	else if(this.type == "text") {
		this.element.val(element.text()).trigger("change");
		this.text_helper.text("");
	}else {
		this.element.find("option").each(function () {
			if (
				$(this).text() === element.text() &&
				$(this)[0] !== self.element.find("option:selected")[0]
			) {
				self.element.find("option:eq(" + i + ")").prop("selected", true);
			}
			i++;
		});
	}
	this.Change();
	this.CloseList();
};

Select.prototype.Search = function(){
	var self = this;
	var search = self.list.find("input").val().toLocaleLowerCase();

	if(search != ""){
		self.list.find("li").each(function(e) {
			if($(this).text().toLocaleLowerCase().indexOf(search) >= 0 || ($(this).data("search") != undefined && $(this).data("search").toLocaleLowerCase().indexOf(search) >= 0)) {
				$(this).show();
			}else{
				$(this).hide();
			}
		});		
	}else{
		self.list.find("li").show();
	}

	if(!self.selectWithoutAnim) {
		self.Resize();
	}else{
		self.selectWithoutAnim = false;
	}
};

Select.prototype.SearchClear = function(){
	var self = this;
	if(self.searchInput != null && self.searchInput != undefined) {
		self.selectWithoutAnim = true;
		self.searchInput.val("");
		self.searchInput.trigger("keyup");
	}
};

Select.prototype.RenderList = function () {
	var self = this;
	var items = this.getValues("render");

	self.list.html("");
	self.list.removeClass("has-icons");

	if(self.isSearchable && this.type != "text" && this.type != "button") {
		var li = $("<div></div>");
		li.addClass("search");
		var clear = $("<a href=# class=clear-search></a>");
		var input  = $("<input type=text class=search-list />");
		self.searchInput = input;
		input.data("api", self);
		input.on("keyup", function(){
			$(this).data("api").Search();
		});
		clear.data("input", input);
		clear.on("click", function(e){
			e.preventDefault();
			$(this).data("input").val("");
			$(this).data("input").trigger("keyup");
		});
		li.append(clear);
		li.append(input);
		self.list.append(li);
	}

	var ul = $("<ul></ul>");
	if(!this.selectable) { ul.addClass("not-selectable"); }
	$(items).each(function (i, e) {
		//console.log(i, e);
		var li = $("<li></li>");
		var data_icon = "";
		var data_text = "";

		if(self.type == "button") {
			data_text = e;
			if(self.element.data("value") == e && self.selectable) {
				li.addClass("selected");
				li.addClass("cursor");
			}
		}
		else if(self.type == "text") {
			data_text = e;
			if(self.element.val() == e) {
				li.addClass("selected");
				li.addClass("cursor");
			}
		}else{
			if ($(this).is(":selected")) {
				li.addClass("selected");
				li.addClass("cursor");
			}
			if ($(this).is(":disabled")) {
				li.addClass("disabled");
			}
			li.data("icon", $(this).data("icon"));
			data_icon = $(this).data("icon");
			data_text = $(this).text();
		}

		var icon = $("<div></div>");
		icon.addClass("icon");
		icon.css("background-image", "url("+data_icon+")");
		if(self.iconWidth != undefined){
			icon.css("width", self.iconWidth);
		}
		li.append(icon);
		if(data_icon != undefined) {
			self.list.addClass("has-icons");			
		}

		var text = $("<div></div>");
		text.addClass("text");
		text.text(data_text);
		li.append(text);

		//li.text($(this).text());
		li.data("option", $(this));
		li.on("click", function () {
			if (
				!$(this)
					.data("option")
					.is(":disabled")
			) {
				self._select($(this));
				self.SearchClear();
			}
		});
		ul.append(li);
	});
	self.list.append(ul);
};

function format_price(price) {
	var p = (price.toFixed(2) + "").split(".");
	if (p[1] == "00")
		return (p[0]).replace(/(\d)(?=(\d{3})+$)/g, '$1 ');
	return (p[0]).replace(/(\d)(?=(\d{3})+$)/g, '$1 ') + "." + p[1];
}

String.prototype.replaceAll = function (search, replacement) {
	var target = this;
	return target.replace(new RegExp(search, 'g'), replacement);
};

function number_eshop(number) {
	var price = number_format(number, 2, ".", " ");
	price = "" + price;
	if (price.substr(price.length - 3, 3) == ".00")
		price = price.substr(0, price.length - 3);
	return price;
}

function getRndInteger(min, max) {
	return Math.floor(Math.random() * (max - min) ) + min;
}

function number_format(number, decimals, dec_point, thousands_sep) {
	// Strip all characters but numerical ones.
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	var n = !isFinite(+number) ? 0 : +number,
		prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
		sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
		dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
		s = '',
		toFixedFix = function (n, prec) {
			var k = Math.pow(10, prec);
			return '' + Math.round(n * k) / k;
		};
	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	if (s[0].length > 3) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if ((s[1] || '').length < prec) {
		s[1] = s[1] || '';
		s[1] += new Array(prec - s[1].length + 1).join('0');
	}
	return s.join(dec);
}

String.prototype.removeSplit = function (text, spliter) {
	var data = this.split(spliter);
	for (var key in data) {
		var el = data[key];
		if (el == text) {
			data.splice(key, 1);
			return data.join(spliter);
		}
	}
	return this;
}

String.prototype.addSplit = function (text, spliter) {
	var data = this == "" ? [] : this.split(spliter);
	for (var key in data) {
		var el = data[key];
		if (el == text) {
			return text;
		}
	}
	data.push(text);
	return data.join(",");
}

String.prototype.containSplit = function (text, spliter) {
	var data = this == "" ? [] : this.split(spliter);
	for (var key in data) {
		var el = data[key];
		if (el == text) {
			return true;
		}
	}
	return false;
}

var ObjectBuildUrl = function (object) {
	var urla = "?";
	for (key in object) {
		var el = object[key];
		if (el == null) continue;
		if (el == "") continue;
		urla += key + "=" + el + "&";
	}
	return urla.substr(0, urla.length - 1);
}

function getMinMax(object, selector) {
	var min = Number.MAX_SAFE_INTEGER;
	var max = Number.MIN_SAFE_INTEGER;
	for (var key in object) {
		var v = object[key];
		var o = selector(v);
		if (min > o) { min = o; }
		if (max < o) { max = o; }
	}
	return { min: min, max: max };
}

function GetPaginatorArray(page, limit, total) {
	page = parseInt(page);
	var max = Math.ceil(total / limit);
	var total = Math.ceil(total / limit);
	var data = { "pages": [] };

	if (total > 0 && page > 1) {
		var prev = page - 1; if (prev < 1) prev = 1;
		data["pages"].push({ "text": "<", "page": prev });
	}

	var offset = 2;

	if (total > offset * 2 && page > offset * 2 + 2) {
		data["pages"].push({ "text": "1", "page": 1 });
		data["pages"].push({ "text": "...", "static": true });
	}

	if (total > offset * 2) {
		start = page - offset;
		size = page + offset;

		if (start <= offset - 2) {
			start = 1;
			size = offset * 2 + 1;
		}
		if (start + offset * 2 > total) {
			start = total - (offset * 2) - 1;
			size = start + (offset * 2) + 2;
		}
		if(start == 2) start = 1;
		if(size > total) size = total;

		for (var i = start; i <= size; i++) {
			if (page == i) {
				data["pages"].push({ "text": i, "page": i, "current": true });
			} else {
				data["pages"].push({ "text": i, "page": i });
			}
		}
	} else {
		for (var i = 1; i <= total; i++) {
			if (page == i) {
				data["pages"].push({ "text": i, "page": i, "current": true });
			} else {
				data["pages"].push({ "text": i, "page": i });
			}
		}
	}

	if (total > offset * 2 + 1 && page < (total - offset)) {
		if(total > offset * 2 + 2) {
			data["pages"].push({ "text": "...", "static": true });
		}
		data["pages"].push({ "text": total, "page": total });
	}

	if (total > 0 && page + 1 <= max) {
		next = page + 1; if (next > max) next = max;
		data["pages"].push({ "text": ">", "page": next });
	}

	return data;
}

function expand(self, element) {
	var e = $(element);
	var s = $(self);
	s.toggleClass("toggled");
	e.toggleClass("expanded");

	if (e.hasClass("expanded")) {
		e.css("height", "initial");
		var height = e.outerHeight();
		e.css("height", "0px");
		e.addClass("--animated");
		setTimeout(function () {
			e.css("height", height);
			setTimeout(function () {
				e.removeClass("--animated");
				e.css("height", null);
			}, 500);
		}, 100);
	} else {
		setTimeout(function () {
			e.removeClass("--animated");
			e.css("height", "0");
		}, 500);
	}
}

function btnLoading(id, load) {
	//Support multiple buttons call with class name and not id
	if (id != null && id.length > 1) {
		id.each(function () {
			btnLoading($(this), load);
		});
		return;
	}

	if (!id.is("button") && !id.is("input") && (!id.is("a") && !id.hasClass("btn")) && !id.hasClass("disabled") && !id.is(":disabled")) {
		return;
	}
	if (load == true) {
		var rect = id[0].getBoundingClientRect();
		id.css("width", Math.ceil(rect.width)/*id.outerWidth()*/);
		id.css("height", Math.ceil(rect.height)/*id.outerHeight()*/);

		if (id.is("input")) {
			id.data("last-text", id.val());
			id.val("...");
		} else {
			id.data("last-text", id.html());
			id.html("<i class=\"fas fa-circle-notch fa-spin\"></i>");
		}
		id.toggleClass("disabled", true);
	} else if (id.data("last-text") != null) {
		if (id.is("input")) {
			id.val(id.data("last-text"));
		} else {
			id.html(id.data("last-text"));
		}
		id.data("last-text", null);
		id.toggleClass("disabled", false);
		id.css("width", null);
		id.css("height", null);
	}
}

function RoundTo2Decimal(num) {
	return Math.round((num + Number.EPSILON) * 100) / 100;
}
function RoundTo7Decimal(num) {
	return Math.round((num + Number.EPSILON) * 100000000) / 100000000;
}
function RoundTo5Decimal(num) {
	return Math.round((num + Number.EPSILON) * 100000) / 100000;
}
function CheckInputForNull(id, def) {
	if (typeof def == "undefined") { def = 0; }
	if ($(id).val() == "") {
		$(id).val(def);
		if ($(id).is(":focus")) {
			$(id).select();
			$(id).trigger("keyup");
		}
	}
}

$(function () {
	$(".search-table").each(function () {
		if (typeof $(this).data("url") != "undefined") {
			var searchTable = new SearchTable($(this));
			searchTable.build();
		}
	});
});

var SearchTable = function (element) {
	this.table = element;
}
SearchTable.prototype.build = function () {
	var self = this;
	this.table.html("");

	this.header = $("<div class='search-table-header'></div>");

	this.input = $("<input type=text />");
	this.input.attr("placeholder", "Type here for search...");
	this.input.on("keyup", function () {
		self.search($(this).val());
	});
	this.header.append(this.input);

	var text = this.table.data("add-text");
	if (typeof text == "undefined") { text = "Add"; }
	this.addbutton = $("<a class=button></a>");
	this.addbutton.html(text);
	this.addbutton.on("click", function (e) {
		window[self.table.data("add-callback")]();
		e.preventDefault();
	})
	this.header.append(this.addbutton);

	this.content = $("<div class='search-table-content'></div>");

	this.table.append(this.header);
	this.table.append(this.content);

	this.table.bind('reload', function () {
		self.reload();
	});

	this.reload();
}
SearchTable.prototype.search = function (search, fast) {
	if (typeof fast == "undefined") fast = false;

	$(this.table.find(".item")).show();
	$(this.table.find(".item")).each(function () {
		if (!$(this).data("content").toLowerCase().includes(search.toLowerCase())) {
			if (fast) {
				$(this).hide();
			} else {
				$(this).fadeOut();
			}
		}
	});
}
SearchTable.prototype.reload = function () {
	var self = this;

	this.setContentText("Loading...");
	$.getJSON(this.table.data("url"),
		function (response) {
			var data = response.data;
			if (data == null || data.length == 0) {
				self.setContentText("Nothing found");
			} else {
				self.content.html("");
				for (var key in data) {
					var it = data[key];
					var item = $("<div class=item></div>");
					item.data("content", $("<span/>").text(it.html).text());
					var text = $("<span></span>");
					text.html(it.html);
					item.append(text);
					if (typeof it.button != "undefined") {
						var btn = $("<a href=# class=button></a>");
						btn.text(it.button);
						btn.data("click", it.buttonCallback);
						btn.on("click", function (e) {
							e.stopPropagation();
							e.preventDefault();
							eval($(this).data("click"));
						});
						item.append(btn);
					}
					if (typeof it.clickCallback != "undefined") {
						item.data("click", it.clickCallback);
						item.on("click", function (e) {
							e.preventDefault();
							$(this).toggleClass("selected");
							eval($(this).data("click"));
						});
					}
					self.content.append(item);
				}

				self.search(self.input.val(), true);
			}
		}
	);
}
SearchTable.prototype.setContentText = function (text) {
	this.content.html("<div class=content-text>" + text + "</div>");
}

function toggleOpen(id) {
	$(id).toggleClass("open");
}

function closeToolbar(id) {
	var _toolbarid = id;
	$.getJSON(_PAGE_URL + "toolbar/?__type=ajax&closeToolbar&toolbarid=" + _toolbarid, function (data) {
		if (data.ok) {
			$("#toolbar_" + _toolbarid).fadeOut(300);
		}
	});
}

var Calendar = function (day, year, month, self) {
	this.year = year;
	this.month = month;
	this.day = day;
	this.selectedDay = new Date(year, month, day);
	this.calendar = $(self);

	var _self = this;
	this.calendar.on("click", function (e) {
		if (!_self.isMobile()) e.stopPropagation();
		_self.Show();
		if (_self.isMobile()) document.activeElement.blur();
	});
	/*this.calendar.on("focus", function() {
	  _self.Show();
	});*/

	this.mode = "day";
};

Calendar.nameMonth = [
	"Leden",
	"Unor",
	"Březen",
	"Duben",
	"Květen",
	"Červen",
	"Červenec",
	"Srpen",
	"Září",
	"Říjen",
	"Listopad",
	"Prosinec"
];

Calendar.nameDay = [
	"Pondělí",
	"Úterý",
	"Středa",
	"Čtvrtek",
	"Pátek",
	"Sobota",
	"Neděle"
];

Calendar.cssBreak = 767;

Calendar.shortDay = ["P", "U", "S", "Č", "P", "S", "N"];
Calendar.shortDayMore = ["po", "út", "st", "čt", "pa", "so", "ne"];

Calendar.daysInMonth = function (y, m) {
	return 32 - new Date(y, m, 32).getDate();
};

Calendar.prototype.Draw = function () { };

Calendar.prototype.firstDay = function () {
	return new Date(this.year, this.month).getDay();
};

Calendar.prototype.Create = function () {
	var shell = $("<div class='dt-shell'></div>");
	var _self = this;

	var mobileOnly = $("<div class='dt-mobile'></div>");
	shell.append(mobileOnly);

	var topYear = $("<div class='dt-title-year'></div>");
	var clickYear = $("<span class='dt-hover'></span>");
	clickYear.html(this.year);
	clickYear.on("click", function () {
		_self.mode = "year";
		_self.drawDays();
	});
	this.clickYear = clickYear;
	topYear.append(clickYear);
	mobileOnly.append(topYear);

	var editDate = $("<div class='dt-edit dt-hover'></div>");
	editDate.html("<i class=edit></i>");
	editDate.on("click", function () {
		_self.mode = _self.mode === "custom" ? "day" : "custom";
		_self.drawDays();
	});
	//mobileOnly.append(editDate);

	var firstDay = this.firstDay();

	var topDate = $("<div class='dt-actual'></div>");
	var selectedDate = $("<span class='dt-hover'></span>");
	selectedDate.on("click", function () {
		_self.mode = "day";
		_self.drawDays();
	});
	this.selectedDate = selectedDate;
	selectedDate.html(
		"<b>" +
		Calendar.shortDayMore[((firstDay + this.day) % 7) - 2] +
		"</b> " +
		this.day +
		". " +
		(this.month + 1) +
		". "
	);
	topDate.append(selectedDate);
	mobileOnly.append(topDate);

	var shellBody = $("<div class='dt-shell-body'></div>");
	shell.append(shellBody);

	var dayShell = $("<div></div>");
	this.dayShell = dayShell;
	shellBody.append(dayShell);

	var prev = $(
		"<div class='dt-prev dt-button-inline dt-small dt-hover'></div>"
	);
	dayShell.append(prev);
	prev.on("click", function () {
		_self.setDate(_self.day, _self.year, _self.month - 1, false);
	});

	var next = $(
		"<div class='dt-next dt-button-inline dt-small dt-hover'></div>"
	);
	dayShell.append(next);
	next.on("click", function () {
		_self.setDate(_self.day, _self.year, _self.month + 1, false);
	});

	var monthPicker = $("<div class='dt-small'></div>");
	monthPicker.html(Calendar.nameMonth[this.month] + " " + this.year);
	this.monthPicker = monthPicker;
	dayShell.append(monthPicker);

	var table = $("<div class='dt-table' style='margin-top: 8px;'></div>");
	shellBody.append(table);

	var row = $("<div class='dt-row'></div>");
	this.dayrow = row;
	table.append(row);

	for (var i = 1; i <= 7; i++) {
		var cell = $("<div class='dt-cell dt-header'></div>");
		cell.html("<div class='dt-so'>" + Calendar.shortDay[i - 1] + "</div>");
		row.append(cell);
	}

	this.table = table;
	var ds = $("<div></div>");
	ds.attr("style", "max-height: 234px;overflow: auto;overflow-x: hidden;");
	this.dayDisplay = ds;
	table.append(this.dayDisplay);

	this.drawDays();

	var mobileOnlyBottom = $("<div class='dt-mobile'></div>");
	var buttons = $("<div class=dt-button-bar></div>");

	var buttonCancel = $("<button class=dt-button></button>");
	buttonCancel.html("Zrušit");
	buttons.append(buttonCancel);
	buttonCancel.on("click", function () {
		_self.Hide();
	});

	var buttonOk = $("<button class=dt-button></button>");
	buttonOk.html("Ok");
	buttons.append(buttonOk);
	buttonOk.on("click", function () {
		_self.setDate(_self.day, _self.year, _self.month, true, true);
	});

	mobileOnlyBottom.append(buttons);
	shell.append(mobileOnlyBottom);

	$(window).click(function () {
		if (!_self.isMobile()) _self.Hide();
	});
	shell.click(function (e) {
		if (!_self.isMobile()) e.stopPropagation();
	});

	$("body").append(shell);
	this.shell = shell;
	shell.hide();

	this.shadow = $("<div class=shadow></div>");
	this.shadow.hide();
	this.shadow.on("click", function () {
		_self.Hide();
	});
	$("body").append(this.shadow);

	$(window).on("resize", function () {
		_self.Resize();
	});
};

Calendar.prototype.isMobile = function () {
	return $(window).outerWidth() < Calendar.cssBreak;
};

Calendar.prototype.Resize = function () {
	if (this.shell === undefined || !this.shell.is(":visible")) {
		if (this.shadow !== undefined) this.shadow.hide();
		return;
	}

	var pos = this.calendar.offset();
	var siz = {
		width: this.calendar.outerWidth(),
		height: this.calendar.outerHeight()
	};

	if (this.isMobile()) {
		this.shell.css("height", "initial");

		var wsize = {
			width: $(window).outerWidth(),
			height: $(window).outerHeight()
		};
		var ssize = {
			width: this.shell.outerWidth(),
			height: this.shell.outerHeight()
		};

		this.shadow.show();
		this.shell.css("left", wsize.width / 2 - ssize.width / 2);
		this.shell.css("top", wsize.height / 2 - ssize.height / 2);
	} else {
		this.shadow.hide();
		this.shell.css("left", pos.left);
		this.shell.css("top", pos.top + siz.height);
	}
};

Calendar.prototype.drawDays = function () {
	this.dayDisplay.html("");

	if (this.mode === "day") {
		this.dayShell.show();
		this.dayrow.show();
		// fill in the days
		var day = 1;
		var today = new Date();
		var monthLength = Calendar.daysInMonth(this.year, this.month);
		var startingDay = this.firstDay();
		if (startingDay === 0) startingDay = 7;

		// this loop is for is weeks (rows)
		var row = $("<div class='dt-row'></div>");
		var rows = 1;
		this.dayDisplay.append(row);
		for (var i = 0; i < 9; i++) {
			var cell;
			// this loop is for weekdays (cells)
			for (var j = 1; j <= 7; j++) {
				cell = $("<div class='dt-cell'></div>");
				if (day <= monthLength && (i > 0 || j >= startingDay)) {
					var incell = $("<div class=''></div>");
					incell.addClass("dt-day dt-hover dt-radius dt-so");
					if (
						new Date(this.year, this.month, day).getTime() ===
						this.selectedDay.getTime()
					) {
						incell.addClass("dt-selected");
					}
					incell.html(day);
					cell.append(incell);
					row.append(cell);
					if (
						today.getDate() === day &&
						today.getFullYear() === this.year &&
						today.getMonth() === this.month
					) {
						incell.addClass("dt-today");
					}
					var _self = this;
					cell.data("day", day);
					cell.on("click", function () {
						_self.selectedDay = new Date(
							_self.year,
							_self.month,
							$(this).data("day")
						);
						_self.setDate(
							$(this).data("day"),
							_self.year,
							_self.month,
							_self.isMobile() ? false : true,
							_self.isMobile() ? false : true
						);
					});
					day++;
				}
				row.append(cell);
				//html += '</td>';
			}
			// stop making rows if we've run out of days
			if (day > monthLength) {
				break;
			} else {
				row = $("<div class='dt-row'></div>");
				this.dayDisplay.append(row);
				rows++;
			}
		}

		if (rows === 5) {
			row = $("<div class='dt-row'></div>");
			this.dayDisplay.append(row);
			cell = $("<div class='dt-cell'></div>");
			row.append(cell);
		}
	} else if (this.mode == "year") {
		this.dayShell.hide();
		this.dayrow.hide();
		var actual = null;
		var d = new Date();
		var _self = this;
		var row = "";
		for (var i = 1900; i <= 2100; i++) {
			row = $("<div class='dt-row'></div>");
			var hov = $("<div class='dt-hover dt-year-pick'></div>");
			hov.html(i);
			if (i == this.year) {
				actual = row;
				hov.addClass("dt-current");
			}
			if (i == d.getFullYear()) {
				hov.addClass("dt-ye");
			}
			hov.data("year", i);
			hov.on("click", function () {
				_self.setDate(
					_self.day,
					$(this).data("year"),
					_self.month,
					false,
					false
				);
				_self.mode = "day";
				_self.drawDays();
			});
			row.append(hov);
			this.dayDisplay.append(row);
		}

		this.dayDisplay.scrollTop(actual.position(true).top - 97);
	} else if (this.mode === "custom") {
		this.dayShell.hide();
		this.dayrow.hide();
	}

	this.Resize();
};

Calendar.prototype.Show = function () {
	this.shell.show();
	if (!this.isMobile()) {
		var h = this.shell.height();
		this.saveHeight = h;
		this.shell.css("height", h - (h / 100) * 20);
		this.shell.animate(
			{
				height: h
			},
			100,
			function () { }
		);
	} else {
		this.shell.css("opacity", 0.3);
		this.shell.animate(
			{
				opacity: 1
			},
			100,
			function () { }
		);
	}
	this.Resize();
};

Calendar.prototype.Hide = function () {
	this.shadow.hide();
	this.shell.hide();
};

Calendar.prototype.setDate = function (day, year, month, hide, seti) {
	if (month >= 12) {
		month = 0;
		year += 1;
	}
	if (month < 0) {
		month = 11;
		year -= 1;
	}

	if (hide === undefined) hide = true;
	var move = "right";
	if (new Date(year, month, day) > new Date(this.year, this.month, this.day)) {
		move = "left";
	}
	if (month === this.month) move = "none";

	this.day = day;
	this.year = year;
	this.month = month;

	if (day !== this.day || this.month !== month) this.reloadSelected();
	if (day !== this.month) this.reloadMonth();

	var d = (this.day < 10 ? "0" : "") + this.day;
	var m = (this.month + 1 < 10 ? "0" : "") + (this.month + 1);
	var y = (this.year < 10 ? "0" : "") + this.year;

	if (seti === true) this.calendar.val(d + "." + m + "." + y);

	this.clickYear.html(this.year);

	if (move !== "none") {
		var _self = this;
		this.monthPicker.css("position", "relative");
		this.monthPicker.animate(
			{
				opacity: 0,
				left: move === "left" ? -40 : 40
			},
			100,
			function () {
				_self.monthPicker.html(
					Calendar.nameMonth[_self.month] + " " + _self.year
				);
				_self.monthPicker.css("left", move === "left" ? 40 : -40);
				_self.monthPicker.animate(
					{
						opacity: 1,
						left: 0
					},
					100,
					function () {
						_self.monthPicker.css("position", "initial");
					}
				);
			}
		);
	} else {
		this.monthPicker.html(Calendar.nameMonth[this.month] + " " + this.year);
	}
	//this.monthPicker.html(Calendar.nameMonth[this.month] + " " + this.year);

	var d = ((this.firstDay() + this.day) % 7) - 2;
	if (d === -1) d = 6;
	if (d === -2) d = 5;
	this.selectedDate.html(
		"<b>" +
		Calendar.shortDayMore[d] +
		"</b> " +
		this.day +
		". " +
		(this.month + 1) +
		". "
	);

	this.Resize();

	if (hide) this.Hide();
};

Calendar.prototype.reloadMonth = function () {
	this.drawDays();
};

Calendar.prototype.reloadSelected = function () {
	var _self = this;
	this.table.find(".dt-today").removeClass("dt-today");
	this.table.find(".dt-day").each(function (e) {
		if (
			$(this)
				.parent()
				.data("day") === _self.day
		) {
			$(this).addClass("dt-today");
		}
	});
};

Calendar.start = function () {
	$("input").each(function (e) {
		if (!$(this).hasClass("date-time-picker")) return;

		var dsplit = $(this)
			.val()
			.split(".");

		var calendar;
		if (dsplit.length == 1) {
			dsplit = new Date().toLocaleDateString().split(".");
		}

		var year = parseInt(dsplit[2].trim());
		var month = parseInt(dsplit[1].trim());
		var day = parseInt(dsplit[0].trim());
		calendar = new Calendar(day, year, month - 1, this);
		calendar.Create();
	});
};

$(function () {
	Calendar.start();
});

function CalculateDisatnce(x1,y1,x2,y2) {
	return Math.sqrt( Math.pow((x1-x2), 2) + Math.pow((y1-y2), 2) );
}

var Stepper = function(element){       
	var element = $(element);
	this.element = element;
	this.pages = element.find(".box-page").length;
	this.width = element.outerWidth();
	this.page = element.data("page");
	this.speed = element.data("speed");
	if(typeof this.speed == "undefined") { this.speed = 1000; }
	element.find(".box-page").width(this.width);
	element.find(".box-page").css("top", 0);	
	element.find(".box-page").data("controll", this);
	var height = element.find(".box-page").outerHeight();
	element.height(height);
	//element.find(".box-page").css("height", "100%");
	this.repage(false);

	var self = this;
	$(window).on("resize", function(){
		self.resize();
	});
};
Stepper.prototype.resize = function(){
	this.width = this.element.outerWidth();
	this.element.find(".box-page").width(this.width);
	this.repage(false);
}
Stepper.prototype.repage = function(animated){
	if(typeof animated == "undefined") animated = true;
	var animated = animated;

	var selectedPage = $(this.element.find(".box-page")[this.page-1]);
	this.element.animate({height: selectedPage.outerHeight()}, this.speed, function(){});

	this.element.find(".box-page").each(function(i){
		var controll = $(this).data("controll");
		if(animated){
			$(this).animate(
				{ left: (controll.width * (i + 1 - controll.page))}, 
				controll.speed, 
				function(){

				}
			);
		}else{
			$(this).css("left", (controll.width * (i + 1 - controll.page)));   
		}
	});
}
Stepper.prototype.next = function(){
	this.page++;
	if(this.page == this.pages + 1) { this.page--;return; }
	this.repage();
}
Stepper.prototype.prev = function(){
	this.page--;
	if(this.page < 1) { this.page++;return; }
	this.repage();
}

var globalNotification = null;
var NotificationCenter = function(){
	if(globalNotification != null) return;
	globalNotification = this;
	this.isopen = false;
	this.openelement = null;
	this.build();
	this.hide();
};

NotificationCenter.prototype.hide = function(){
	this.isopen = false;	
	this.notif.hide();
}

NotificationCenter.prototype.close = function(){
	var self = this;
	this.notif.animate({"height": this.openelement.outerHeight(), "opacity": 0.5, "width": this.openelement.outerWidth(), "left": this.openelement.offset().left}, 200, function(){ 
		self.hide();
	});	
}

NotificationCenter.prototype.build = function(self){
	var notificationDiv = $("<div></div>");
	notificationDiv.addClass("notification-top");
	//notificationDiv.css("height", 250);
	$("body").append(notificationDiv);
	this.notif = notificationDiv;	

	var notifTitle = $("<div></div>");
	notifTitle.html("Notifications");
	notifTitle.addClass("notif-title");
	notificationDiv.append(notifTitle);
	var notifContent = $("<div></div>");
	notifContent.addClass("notif-content");
	notificationDiv.append(notifContent);

	notifTitle.data("self", this);
	notifTitle.click(function(){
		$(this).data("self").close();
	});

	this.notifContent = notifContent;

	var self = this;
	$(document).mouseup(function (e) {
		var container = self.notif;
		if (self.isopen) {
			if (!container.is(e.target) && container.has(e.target).length === 0) {
				self.close();
			}
		}
	});
}

NotificationCenter.prototype.open = function(element){
	var self = this;
	var element = $(element);
	this.openelement = element;

	this.notif.css("height", '');
	this.notif.css("width", '');
	this.notif.css("opacity", '');

	this.notif.show();
	self.checkExpander();

	var left = element.offset().left - this.notif.outerWidth() + element.outerWidth();	
	var top = element.offset().top;
	var height = this.notif.outerHeight();
	console.log(height);
	var width = this.notif.outerWidth();

	this.notif.css("top", top - 5);	
	
	//this.notif.css("width", self.outerWidth());
	this.notif.css("opacity", 0.5);
	this.notif.css("width", width);
	this.notif.css("height", element.outerHeight());
	this.notif.css("left", element.offset().left - this.notif.outerWidth() + element.outerWidth());

	this.notif.animate({"height": height, "top": top, "opacity": 1 /*"width": width, "left": left*/}, 200, function(){ self.isopen = true; $(this).css("height", ''); });	
}

NotificationCenter.open = function(self){
	globalNotification.open(self);
}

NotificationCenter.prototype.checkExpander = function(){
	this.notifContent.find(".notification").each(function(){
		var t = $(this);
		if(typeof t.data("expandable") == "undefined"){
			var content = t.find(".content");
			var size = content.outerHeight();
			content.css("max-height", "initial");
			if(size == content.outerHeight()){
				t.find(".expander").hide();
				t.data("expandable", false);
			}else{
				t.find(".expander").show();
				t.data("expandable", true);
			}		
			content.css("max-height", '');
		}
	});
}

NotificationCenter.prototype.add = function(icon, from, title, message, link, time, cls, type, data){
	var div = $("<a></a>");
	div.attr("href", link);
	if(cls != "")
		div.addClass(cls);
	div.addClass("notification");
	var titleBar = $("<div></div>");
	titleBar.addClass("titlebar");
	div.append(titleBar);
	if(typeof icon != "undefined"){
		var dicon = $("<i></i>");
		dicon.addClass(icon);
		titleBar.append(dicon);
	}
	var dtitle = $("<div></div>");
	dtitle.addClass("title");
		var dtitlei = $("<div></div>");
		dtitlei.html(from);
		dtitle.append(dtitlei);
		//time	
		if(typeof time != "undefined" && time != null){
			var dtitlei = $("<div></div>");
			dtitlei.addClass("title-time");
			dtitlei.html(time);
			dtitle.append(dtitlei);
		}
	titleBar.append(dtitle);
	var expand = $("<div></div>");
	expand.addClass("expander");
	expand.data("div", div);
	expand.click(function(){
		expand.data("div").toggleClass("expanded");
	});
	titleBar.append(expand);

	var dmessage = $("<div></div>");
	dmessage.addClass("content");
		var dmesstitle = $("<div></div>");
		dmesstitle.addClass("title-content");
		dmesstitle.html(title);
		dmessage.append(dmesstitle);
		var dcotent = $("<div></div>");
		dcotent.addClass("message-content");
		dcotent.html(message);
		dmessage.append(dcotent);
	div.append(dmessage);

	this.notifContent.prepend(div);
}

var Tabler = function(id, data){
	this.id = "#"+id;
	this.data = data;
	this.skip = 0;
	var self = this;

	if(this.data.isasc == undefined) { this.data.isasc = null; }
	if(this.data.take == undefined) { this.data.take = 30; }
	if(this.data.paginate == undefined) { this.data.paginate = true; }

	this.tableData = $($(this.id).find(".table-body")[0]);

	$(this.id).find(".tabs li").data("tabler", this);
	$(this.id).find(".tabs li").on("click", function(){
		$(this).closest("ul").find("li").removeClass("selected");
		$(this).addClass("selected");
		$(this).data("tabler").skip = 0;
		$(this).data("tabler").Reload();
	});

	$(this.id).find(".head .col").data("tabler", this);
	$(this.id).find(".head .col").each(function(){
		var sort = $(this).find(".order i");
		sort.removeClass("fa-sort-desc");
		sort.removeClass("fa-sort-asc");
		sort.addClass("fa-sort");

		if(self.data.order == $(this).data("order")){
			sort.removeClass("fa-sort");
			if(self.data.isasc){
				sort.addClass("fa-sort-asc");
			}else{
				sort.addClass("fa-sort-desc");
			}
		}

		$(this).on("click", function(){
			$($(this).data("tabler").id).find(".head .col .order i").removeClass("fa-sort-asc fa-sort-desc");
			$($(this).data("tabler").id).find(".head .col .order i").addClass("fa-sort");

			if($(this).data("tabler").data.order != $(this).data("order")){
				$(this).data("tabler").data.isasc = true;

				$(this).find(".order i").addClass("fa-sort-asc");
				$(this).find(".order i").removeClass("fa-sort");
			}else if($(this).data("tabler").data.isasc == false){
				$(this).data("tabler").data.isasc = true;

				$(this).find(".order i").addClass("fa-sort-asc");
				$(this).find(".order i").removeClass("fa-sort-desc");
				$(this).find(".order i").removeClass("fa-sort");
			}else if($(this).data("tabler").data.isasc == null){
				$(this).data("tabler").data.isasc = true;

				$(this).find(".order i").addClass("fa-sort-asc");
				$(this).find(".order i").removeClass("fa-sort-desc");
				$(this).find(".order i").removeClass("fa-sort");
			}else{
				$(this).data("tabler").data.isasc = false;

				$(this).find(".order i").addClass("fa-sort-desc");
				$(this).find(".order i").removeClass("fa-sort");
			}
			$(this).data("tabler").data.order = $(this).data("order");
			
			$(this).data("tabler").skip = 0;
			$(this).data("tabler").Reload();
		});
	});

	$(this.id).find("input.search").data("tabler", this);
	$(this.id).find("input.search").on("keyup", function(e){
		if(e.keyCode == 13 || $(this).val() == ""){
			$(this).data("tabler").Reload();
			e.preventDefault();
		}
	});

	this.loadmore = $(this.id).find(".table-load-next");
	this.loadmore.hide();
	this.loadmore.data("api", this);
	this.loadmore.on("click", function(e){ e.preventDefault(); $(this).data("api").LoadMore(); });

	this.Reload();

	return this;
};

Tabler.prototype.GetFilter = function(){
	return $($(this.id).find(".tabs li.selected")).data("filter");
};

Tabler.prototype.GetSearch = function(){
	return $(this.id).find("input.search").val();
};

Tabler.prototype.LoadMore = function(){
	this.skip += this.data.take;
	this.loadmore.hide();
	this.Reload();
};

Tabler.prototype.SetTotal = function(total) {
	if(total > this.skip + this.data.take) {
		this.loadmore.show();
	}else{
		this.loadmore.hide();
	}
};

Tabler.prototype.Reload = function(){
	var $this = this;	
	if($this.skip == 0){
		$this.tableData.addClass("loading");
		$this.tableData.html("Loading...");
	}

	$.post($this.data.url, {filter: this.GetFilter(), order: this.data.order, isasc: this.data.isasc, search: this.GetSearch(), skip: this.skip, take: this.data.take}, function(data){ 		
		$this.tableData.removeClass("loading");
		if($this.skip == 0){
			$this.tableData.html(data);
		}else{
			$this.tableData.append(data);
		}		
		$this.tableData.find(".table-row").each(function(){
			if($(this).data("tabler") != undefined) return;
			$(this).data("tabler", $this);
			$(this).on("click", function(){
				if($(this).data("link") != "#" && $(this).data("link") != ""){
					window.location.href = $(this).data("link");
				}
			});
		});
	});
};

Tabler.prototype.Filter = function(){

};

function copy(c){
	var $temp = $("<input>");
	$("body").append($temp);
	$temp.val($(c).text()).select();
	document.execCommand("copy");
	$temp.remove();
}


Date.prototype.addSeconds = function(seconds) {
	this.setSeconds(this.getSeconds() + seconds);
	return this;
  };
  
  Date.prototype.addMinutes = function(minutes) {
	this.setMinutes(this.getMinutes() + minutes);
	return this;
  };
  
  Date.prototype.addHours = function(hours) {
	this.setHours(this.getHours() + hours);
	return this;
  };
  
  Date.prototype.addDays = function(days) {
	this.setDate(this.getDate() + days);
	return this;
  };
  
  Date.prototype.addWeeks = function(weeks) {
	this.addDays(weeks*7);
	return this;
  };
  
  Date.prototype.addMonths = function (months) {
	var dt = this.getDate();
	this.setMonth(this.getMonth() + months);
	var currDt = this.getDate();
	if (dt !== currDt) {  
	  this.addDays(-currDt);
	}
	return this;
  };
  
  Date.prototype.addYears = function(years) {
	var dt = this.getDate();
	this.setFullYear(this.getFullYear() + years);
	var currDt = this.getDate();
	if (dt !== currDt) {  
	  this.addDays(-currDt);
	}
	return this;
  };

var UploaderDefaultOptions = {autosubmit: false, maxfiles: 5, response: "html"};
var UploaderLanguage = {
	"choose": "Choose file",
	"drag": "or drag here",
	"selected": "$1 files selected",
	"uploaded": "Uploaded",
	"max": "You can upload max $1 files."
};
var Uploader = function(url, form, elements, options){
	this.url = url;
	this.form = form;
	this.elements = elements;
	this.afterUpload = null;
	this.droppedFiles = null;
	this.options = options == undefined ? UploaderDefaultOptions : duplicateMissing(options, UploaderDefaultOptions);
	this.lang = UploaderLanguage;
	this.state = "WAITING";

	if(!Array.isArray(this.elements)){
		this.elements = [{element: this.elements}];
	}else{
		var out = [];
		for(var key in this.elements) {
			var element = this.elements[key];
			out.push({element: element});
		}
		this.elements = out;
	}

	this.Build();
};

Uploader.prototype.Build = function(){
	for(var key in this.elements) {
		var element = this.elements[key];

		var outerDiv = $("<div></div>");
		outerDiv.addClass("dropdown-upload");

		var div = $("<div></div>");	
		var progress = $("<div class=progress></div>");
		div.append(progress);
		progress.hide();
		element.progress = progress;
		//this.progress = progress;

		var img = $("<span class=img-upload></span>");
		div.append(img);

		if(element.element.attr("value") != null && element.element.attr("value") != "") {
			img.addClass("img-upload-show");
			var icons = element.element.attr("value").split(",");			
			for(var key in icons) {
				if(key > 4) break;
				var image = $("<img/>");
				image.attr("src", icons[key]);
				img.append(image);
			}
		}

		var text = $("<div></div>");
		this.textPicker = text;
		element.textPicker = text;
		text.addClass("text");

		var href = $("<a></a>");
		href.attr("href", "#");
		href.text(this.lang.choose);
		href.data("api", this);
		href.data("element", element);
		href.on("click", function(e){
			e.preventDefault();
			$(this).data("element").element.trigger("click");
		});
		text.append(href);

		var otext = $("<span></span>");
		otext.html(" "+this.lang.drag);
		text.append(otext);
		div.append(text);
		
		var textSelected = $("<div></div>");
		//this.textSelected = textSelected;
		element.textSelected = textSelected;
		textSelected.addClass("text");
		div.append(textSelected);
		textSelected.hide();

		outerDiv.data("api", this);
		outerDiv.data("element", element);
		outerDiv.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
		})
		.on('dragover dragenter', function() {
			var api = $(this).data("api");
			var element = $(this).data("element");

			if(element.outerDiv.hasClass('is-uploading')) return;

			$(this).addClass('is-dragover');
		})
		.on('dragleave dragend drop', function() {
			$(this).removeClass('is-dragover');
		})
		.on('drop', function(e) {
			var api = $(this).data("api");
			var element = $(this).data("element");

			if(element.outerDiv.hasClass('is-uploading')) return;

			if(e.originalEvent.dataTransfer.files.length > api.options.maxfiles){
				element.outerDiv.removeClass('is-ok').addClass("is-error");
				element.textPicker.hide();
				element.textSelected.text(api.lang.max.replace("$1", api.options.maxfiles)+" ").show();
				element.textSelected.append(api.CreateChooseLink(element));
			}else{
				element.droppedFiles = e.originalEvent.dataTransfer.files;
				api.SelectedFiles(element);
				if(api.options.autosubmit === true) {
					api.Upload();
				}
			}
		});

		outerDiv.append(div);
		//this.outerDiv = outerDiv;
		element.outerDiv = outerDiv;
		element.droppedFiles = [];

		element.element.after(outerDiv);
		element.element.hide();
		element.element.data("api", this);
		element.element.data("element", element);
		element.element.on("change", function(e){	
			var api = $(this).data("api");
			var element = $(this).data("element");

			if(element.outerDiv.hasClass('is-uploading')) return;

			if($(this)[0].files.length > api.options.maxfiles){
				element.outerDiv.removeClass('is-ok').addClass("is-error");
				element.textPicker.hide();
				element.textSelected.text(api.lang.max.replace("$1", api.options.maxfiles)+" ").show();
				element.textSelected.append(api.CreateChooseLink(element));
			}else{
				element.droppedFiles = $(this)[0].files;
				api.SelectedFiles(element);
				if(api.options.autosubmit === true) {
					api.Upload();
				}
			}
		});
	}
};

Uploader.prototype.SelectedFiles = function(element){
	element.outerDiv.removeClass("is-error").removeClass("is-ok");
	if(element.droppedFiles.length > 1) {
		element.textPicker.hide();
		element.textSelected.text(this.lang.selected.replace("$1", element.droppedFiles.length));		
		element.textSelected.show();
	} else if(element.droppedFiles.length == 1) {
		element.textPicker.hide();
		element.textSelected.text(element.droppedFiles[0].name);
		element.textSelected.attr("title", element.droppedFiles[0].name);
		element.textSelected.show();
	} else {
		element.textSelected.hide();
		element.textPicker.show();
	}
};

Uploader.prototype.Upload = function(){
	if(this.state != "WAITING") return this.state;
	this.state = "UPLOADING";

	this.totalUpload = 0;
	var anyDroppedFiles = false;
	for(var key in this.elements) {
		var element = this.elements[key];
		element.progress.css("width", 0);		
		
		if (element.droppedFiles != null && element.droppedFiles.length > 0) {
			element.outerDiv.addClass("is-uploading").removeClass("is-error").removeClass("is-ok");
			element.progress.show();
			anyDroppedFiles = true;
			this.totalUpload++;			
		}
	}

	var self = this;
	var ajaxData = new FormData(this.form[0]);

	if (anyDroppedFiles) {
		for(var key in this.elements) {
			var element = this.elements[key];

			ajaxData.delete(element.element.attr('name'));
			$.each(element.droppedFiles, function(i, file) {
				ajaxData.append(element.element.attr('name')+"[]", file);
			});
		}
	}

	$.ajax({
		url: this.url, 
		type: 'POST',
		data: ajaxData,
		cache: false,
		contentType: false,
		processData: false,
		xhr: function () {
			var myXhr = $.ajaxSettings.xhr();
			if (myXhr.upload) {
				myXhr.upload.addEventListener('progress', function (e) {
					if (e.lengthComputable) {
						var totalPercent = e.loaded/(e.total / 100);
						//self.totalUpload
						var i = 1;
						var finished = true;
						var alerady = 0;
						for(var key in self.elements) {
							var element = self.elements[key];							
							if(element.droppedFiles == null || element.droppedFiles.length == 0) continue;

							if(totalPercent >= (100 / self.totalUpload) * i) {
								alerady = (100 / self.totalUpload) * i;
								element.progress.css("width", "100%");
							}else if(totalPercent >= (100 / self.totalUpload) * (i-1)){
								element.progress.css("width", ((e.loaded/(e.total / 100) - alerady)*self.totalUpload)+"%");
							}else{
								element.progress.css("width", 0+"%");
							}

							i++;
						}						
					}
				}, false);
			}
			return myXhr;
		}
	}).done(function(data) {	
		var errors = 0;

		for(var key in self.elements) {	
			var element = self.elements[key];
			var name = element.element.attr("name");			

			if(element.droppedFiles != null && element.droppedFiles.length > 0) {
				if(data[name] != undefined && data[name].error && data[name].error != "") {				
					element.outerDiv.removeClass('is-uploading').addClass("is-error");
					element.textSelected.text(data[name].error+" ");
					element.textSelected.append(self.CreateChooseLink(element));
					errors++;
				}else{				
					element.outerDiv.removeClass('is-uploading').addClass("is-ok");
					element.textSelected.text(self.lang.uploaded);
				}	
			}

			element.droppedFiles = [];		
		}

		if(self.afterUpload != null && errors == 0) {
			if(self.options.response == "json")
				self.afterUpload(JSON.parse(data));
			else
				self.afterUpload(data);
		}
	}).complete(function() {
		for(var key in self.elements) {
			var element = self.elements[key];
			element.progress.hide();
			element.outerDiv.removeClass('is-uploading');
		}
		self.state = "WAITING";
	}).error(function(error) {
		for(var key in self.elements) {
			var element = self.elements[key];

			element.outerDiv.removeClass('is-uploading').addClass("is-error");
			element.textSelected.text(error+" ");
			element.textSelected.append(self.CreateChooseLink(element));
		}
		self.state = "WAITING";
	});

	return true;
};

Uploader.prototype.CreateChooseLink = function(element){
	var href = $("<a></a>");
	href.attr("href", "#");
	href.text(this.lang.choose);
	href.data("api", this);
	href.data("element", element);
	href.on("click", function(e){
		e.preventDefault();
		$(this).data("element").element.trigger("click");
	});
	return href;
}

Uploader.prototype.AfterUpload = function(callback) {
	this.afterUpload = callback;
}

var ProgressBar = function(element){
	this.element = $(element);
	this.bar = null;
	this.div = null;
	this.element.data("api", this);
	this.percent = this.element.val();
};
ProgressBar.prototype.Build = function(){
	if(this.element.data("progress") != true){
		this.element.hide();
		this.element.data("progress", true);

		this.div = $("<div class='progress small'></div>");
		this.bar = $("<div class=bar style='width:50%;'></div>");
		this.div.append(this.bar);
		this.element.after(this.div);

		var self = this;
		this.element.on("change", function(){ self.OnChange(); });
	}
};
ProgressBar.prototype.OnChange = function(){
	this.bar.css("width", this.element.val()+"%");
};
ProgressBar.prototype.Show = function(){
	this.div.css("display", "block");
};
ProgressBar.prototype.Hide = function(){
	this.div.css("display", "none");
};
ProgressBar.prototype.AppendToButton = function(button){
	button.append(this.div);
	this.div.addClass("fixed-to-button");
	button.addClass("button-fixer");
}

var replaceProgress = function(){
	$("input[type=progress]").each(function(){
		var pb = new ProgressBar(this);
		pb.Build();
	});
};
replaceProgress();

function cmpVersions(a, b) {
    var i, diff;
    var regExStrip0 = /(\.0+)+$/;
    var segmentsA = a.replace(regExStrip0, '').split('.');
    var segmentsB = b.replace(regExStrip0, '').split('.');
    var l = Math.min(segmentsA.length, segmentsB.length);

    for (i = 0; i < l; i++) {
        diff = parseInt(segmentsA[i], 10) - parseInt(segmentsB[i], 10);
        if (diff) {
            return diff;
        }
    }
    return segmentsA.length - segmentsB.length;
}

Date.prototype.ddmmyyyy_hhmm = function() {
	var mm = this.getMonth() + 1; // getMonth() is zero-based
	var dd = this.getDate();
	var hh = this.getHours();
	var mi = this.getMinutes();
  
	var data = [this.getFullYear(),
			(mm>9 ? '' : '0') + mm,
			(dd>9 ? '' : '0') + dd,
			(hh>9 ? '' : '0') + hh,
			(mi>9 ? '' : '0') + mi,
		   ];
	return data[2]+"."+data[1]+"."+data[0]+" "+data[3]+":"+data[4]
};

function ArrayRemove(array, element) {
    for(var key in array){
		if(array[key] == element){
			array.splice(key, 1);
		}
	}
    return array;
};

var GridContainer = function(inside, width = 100, style = "vertical"){ 
	this.inside = inside == undefined? null: inside; 
	this.style = style; 
	this.width = width / 100;
};
GridContainer.prototype.build = function(){ 
	var div = $("<div class='gridContainer gridStyle-"+this.style+"'></div>");
	if(this.width != 1) {
		div.css("flex-grow", this.width);
	}
	if(this.inside == null) {
		div.addClass("gridLast")
	}
	return div;
};

var GridWindow = function(title, showTrans = false, addClass = ""){ 
	this.title = title;
	this.showTrans = showTrans;
	this.addClass = addClass;
};
GridWindow.prototype.build = function(){
	var div = $("<div class='gridWindow'></div>");
	if(this.addClass != undefined) {
		div.addClass(this.addClass);
	}
	var title = $("<div class='gridWindowTitle'></div>");
		
	var titleContent = $("<div class='gridWindowTitleContent'></div>");
	titleContent.text(this.title);
	var titleControls = this.titleControls = $("<div class='gridWindowTitleControls'></div>");

	title.append(titleContent);
	title.append(titleControls);

	this.titleDiv = title;

	var content = $("<div class='gridWindowContent'></div>");
	if(this.showTrans) {
		content.addClass("showTrans");
	}
	this.contentDiv = content;

	div.append(title);
	div.append(content);
	return div;
}
GridWindow.prototype.append = function(element) {
	this.contentDiv.append(element);
}
GridWindow.prototype.get = function(){
	return this.contentDiv;
}
GridWindow.prototype.getControlls = function(){
	return this.titleControls;
}

var GridManager = function(id, layout){
	var self = this;
	this.container = $(id);
	this.layout = layout;

	this.container.html("");
	this.build();
	this.refresh();	

	$(window).on("resize", function(){ self.refresh(); });
};
GridManager.prototype.buildGrid = function(grid, container){
	var element = grid;
	var builded = element.build();
	element.div = builded;
	container.append(builded);

	if(element.inside != undefined) {
		if(element.inside.length != undefined) {
			for(var key in element.inside) {
				this.buildGrid(element.inside[key], builded);
			}
		}else{
			this.buildGrid(element.inside, builded);
		}
	}
}
GridManager.prototype.build = function(){
	for(var key in this.layout) {
		this.buildGrid(this.layout[key], this.container);
	}
};
GridManager.prototype.refresh = function(){
	var height = 0; $(".admin > ul").each(function(){ height += $(this).outerHeight(); });
	var pageFreeHeight = $(window).height() - (height + $("#adminheader").outerHeight() + 12);
	this.container.css("height", pageFreeHeight);
};

var DesignerOptions = function(options) {
	var def = {
		"background": "transaprent",
		"x": 0,
		"y": 0,
		"width": 100,
		"height": 100,
		"border": "0px"
	};

	this.options = duplicateMissing(options, def);
}
DesignerOptions.prototype.get = function(name) {
	if(this.options[name] == null || this.options[name] == undefined || this.options[name] == "")
		return null;

	return this.options[name];
}
DesignerOptions.prototype.set = function(name, value) {
	if(this.options[name] == value) return false;

	this.options[name] = value;
	return true;
}
DesignerOptions.prototype.generateData = function(){
	var defCopy = {};
	for(var key in this.options) {
		var d = this.options[key];
		if(key != 'id' && key != 'name') {
			defCopy[key] = d;
		}
	}

	return JSON.stringify(defCopy);
}

/* START OF OBJECT TYPES */
var DesignerObjectDefault = function(id, name, options, parents){
	this.id = id;
	this.name = name;
	this.supportChildrens = true;
	this.options = new DesignerOptions(options);
	
	this.options.set("id", id);
	this.options.set("name", name);

	this.parents = parents;

	if(this.notBuildStyleProps == undefined) this.notBuildStyleProps = false;

	if(this.typeProps == undefined) this.typeProps = [];
	this.props = DesignerManager.buildDefaultPropertyes().concat(this.typeProps);
	if(!this.notBuildStyleProps) { this.props = this.props.concat(DesignerManager.buildDefaultStylePropertyes())}
	//this.props = this.props.concat(DesignerManager.buildDefaultPropertyes().concat(DesignerManager.buildDefaultStylePropertyes()));
}
DesignerObjectDefault.prototype.create = function(){
	var o = DesignerManager.buildDefault(this.options);
	
	if(this.buildType != undefined)
		this.buildType(o, this.options);

	return o;
}
DesignerObjectDefault.prototype.setOptions = function(name, value, parent){
	var isRoot = false;
	if(parent == undefined) { parent = this.props; isRoot = true; }
	if(parent == undefined) return;

	for(var key in parent) {
		var prop = parent[key];
		if(prop.id == name) {
			if(prop.setter != null) {
				prop.setter(this.object, value);
				if(parent != this.props){
					return true;
				}
			}
		}
		if(prop.parents != null) {
			if(this.setOptions(name, value, prop.parents)){
				this.manager.updatePropText(prop, this);
			}
		}
	}

	if(isRoot)
		return this.options.set(name, value);

	return null;
}

var DesignerObjectRoot = function(id, name, options, parents){
	this.type = "root";

	DesignerObjectDefault.call(this, id, name, options, parents);	
}
DesignerObjectRoot.prototype = Object.create(DesignerObjectDefault.prototype);

var DesignerObjectContainer = function(id, name, options, parents){
	this.type = "container";

	DesignerObjectDefault.call(this, id, name, options, parents);	
}
DesignerObjectContainer.prototype = Object.create(DesignerObjectDefault.prototype);

var DesignerObjectText = function(id, name, options, parents){
	this.type = "text";
	this.typeProps = [
		new DesignerProperty("", "Text", DesignerProperty.TYPE_CATEGORY),
		new DesignerProperty("text", "Text", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.text(value); })
	];
	this.supportChildrens = false;

	DesignerObjectDefault.call(this, id, name, options, parents);	
}
DesignerObjectText.prototype = Object.create(DesignerObjectDefault.prototype);
DesignerObjectText.prototype.buildType = function(object, options) {
	object.text(options.get("text"));
}

var DesignerObjectCanvas = function(id, name, options, parents){
	this.type = "canvas";
	this.typeProps = [
		new DesignerProperty("", "Canvas", DesignerProperty.TYPE_CATEGORY),
		new DesignerProperty("test", "Test", DesignerProperty.TYPE_TEXT)
	];

	DesignerObjectDefault.call(this, id, name, options, parents);	
}
DesignerObjectCanvas.prototype = Object.create(DesignerObjectDefault.prototype);

var DesignerObjectLine = function(id, name, options, parents){
	this.type = "line";
	this.notBuildStyleProps = true;

	DesignerObjectDefault.call(this, id, name, options, parents);	
}
DesignerObjectLine.prototype = Object.create(DesignerObjectDefault.prototype);
/* END OF OBJECT TYPES */

var DesignerManager = function(id, data, designerWindow, objectWindow, propertyWindow, timelineWindow){
	this.designerWindow = designerWindow;
	this.objectWindow = objectWindow;
	this.propertyWindow = propertyWindow;
	this.timelineWindow = timelineWindow;
	this.id_counter = 0;
	this.selected = null;
	this.latestPropsEdited = [];
	this.openedTree = [];
	this.objects = [];
	this.action = "";
	this.data = null;
	this.id = id;
	this.ticks = 0;

	var data = JSON.parse(data);
	
	if(data.items == null) {
		var root = this.addObject("root", "root", "Root", {width: 300, height: 300, border: "1px dashed red"});
	}else{
		var root = this.addObject("root", "root", data.items[0].name, data.items[0].options);
		this.addObjects(data.items[0].parents, root);

		if(data.openedTree != undefined) this.openedTree = data.openedTree;
		if(data.id_counter != undefined) this.id_counter = data.id_counter;
		if(data.selected != undefined) this.selected = data.selected;
	}

	this.mouse = {state: MouseState.NONE, x: 0, y: 0, save: {}};
	this.avalibleObjects = {};

	this.loadObjects();
	this.refreshTree();
	this.buildDesigner();
	this.renderObjectProps();
	this.renderSelector();
	this.windowResize();
	this.startTimers();

	$("body").data("manager", this);
	$("body").on("mousemove", function(event){
		$(this).data("manager").mouseEvent("move", event);
	});
	$("body").on("mouseup", function(event){
		$(this).data("manager").mouseEvent("up", event);
	});
	$("body").on("mousedown", function(event){
		$(this).data("manager").mouseEvent("down", event);
	});
	$(window).on("resize", function(){
		$("body").data("manager").windowResize();
	});	
}

DesignerManager.prototype.startTimers = function(){
	this.timer = setInterval(function(){
		$("body").data("manager").tick();
	}, 100);
}

DesignerManager.prototype.tick = function(){
	this.tick++;
	this.timeLineTick();
}

DesignerManager.prototype.timeLineTick = function(){
	
}

DesignerManager.prototype.loadObjects = function(){
	this.avalibleObjects["root"] = {
		name: "Root",
		canBeAdded: false,
		type: "root"
	};
	this.avalibleObjects["canvas"] = {
		name: "Canvas",
		canBeAdded: true,
		type: "canvas"
	};
	this.avalibleObjects["container"] = {
		name: "Container",
		canBeAdded: true,
		type: "container"
	}
	this.avalibleObjects["text"] = {
		name: "Text",
		canBeAdded: true,
		type: "text"
	}
}

DesignerManager.prototype.addObjects = function(parents, parent){
	for(var key in parents) {
		var i = parents[key];
		var o = this.addObject(i.type, i.id, i.name, i.options, parent);
		if(i.parents != undefined && i.parents.length > 0) {
			this.addObjects(i.parents, o);
		}
	}
}

DesignerManager.prototype.windowResize = function(){
	var prop = $(".window-prop-height");
	if(prop.length > 0) {
		prop.css("max-height", ($("#grid").outerHeight() / 2) - 20);
	}

	this.update();
}

var MouseState = {NONE: 0, UP: 1, DOWN: 2, MOVE: 3};
DesignerManager.prototype.mouseEvent = function(type, event) {
	if(type=="up") {
		this.mouse.state = MouseState.UP;
		this.mouse.event = "none";
		this.mouse.save = {};

		if(this.latestPropsEdited != null && this.latestPropsEdited.length > 0) {
			var fe = this.latestPropsEdited[1];
			console.log(fe);
			if (!fe.is(event.target) && fe.has(event.target).length === 0) {
				fe.hide();
				this.latestPropsEdited[0].show();
			}
		}
	}else if(type=="down") {
		this.mouse.state = MouseState.DOWN;
	}else if(type=="move") {
		this.mouse.state = MouseState.MOVE;
		this.mouse.x = event.pageX;
		this.mouse.y = event.pageY;

		if(this.mouse.event == "resize-right") {
			var mw = this.mouse.x - this.mouse.savex;			
			var selected = this.getSelected();
			if(selected != null) {
				var prop = this.findProp('width');

				var sW = this.mouse.save["width"];
				if(sW == undefined || sW == null) { sW = this.mouse.save["width"] = prop[3]; }

				if(selected.type == 'root') {
					mw*=2; //TODO: pro center by to chtělo obě strany
				}
				var w = parseFloat(sW) + mw;
				if(w < 0) w = 0;
				if(selected.setOptions('width', w)){
					this.refreshTree();
					this.updatePropText(prop[2], selected);
				}
			}
		}
		if(this.mouse.event == "resize-bottom") {
			var mh = this.mouse.y - this.mouse.savey;			
			var selected = this.getSelected();
			if(selected != null) {
				var prop = this.findProp('height');

				var sH = this.mouse.save["height"];
				if(sH == undefined || sH == null) { sH = this.mouse.save["height"] = prop[3]; }

				if(selected.type == 'root') {
					mh*=2; //TODO: pro center by to chtělo obě strany
				}
				var h = parseFloat(sH) + mh;
				if(h < 0) h = 0;
				if(selected.setOptions('height', h)){
					this.refreshTree();
					this.updatePropText(prop[2], selected);
				}
			}
		}
	}
}

DesignerManager.prototype.saveMouse = function(){
	this.mouse.savex = this.mouse.x;
	this.mouse.savey = this.mouse.y;
}

DesignerManager.prototype.objectDefinitions = {
	root: DesignerObjectRoot,
	canvas: DesignerObjectCanvas,
	line: DesignerObjectLine,
	container: DesignerObjectContainer,
	text: DesignerObjectText
};

DesignerManager.prototype.addObject = function(type, id, name, options, parent){
	var creator = this.objectDefinitions[type];
	if(creator == undefined) {
		if(parent == null)
			creator = this.objectDefinitions.root;
		else
			throw "Unkown type creator '" + type + "'";
	}

	return this.add(new creator(0, name, options, []), parent, id);
}

DesignerManager.prototype.add = function(object, parent, id) {
	var p = parent;
	if(parent == undefined) { 
		parent = this.objects; 
	} 
	else { 
		if(parent.parents == null) { 
			parent.parents = []; 
		} 
		parent = parent.parents;
	}

	if(id == undefined || id == null) object.id = "object_"+(++this.id_counter);
	else object.id = id;
	object.options.set("id", object.id);
	object.manager = this;
	object.parent = p;

	parent.push(object);

	return object;
}

var DesignerProperty = function(id, name, type, parents, callback, setter, builder){
	this.id = id;
	this.name = name;
	this.type = type;
	this.parents = parents == undefined? null: parents;
	this.callback = callback == undefined? null: callback;
	this.setter = setter == undefined? null: setter;
	this.builder = builder == undefined? null: builder;

	this.div = null;
}
DesignerProperty.TYPE_TEXT = 1;
DesignerProperty.TYPE_NUMBER = 2;
DesignerProperty.TYPE_SELECT = 3;
DesignerProperty.TYPE_PARENT = 4;
DesignerProperty.TYPE_CATEGORY = 5;
DesignerProperty.TYPE_CHECK = 6;
DesignerProperty.TYPE_COLOR = 7;
DesignerProperty.TYPE_CUSTOM = 8;

DesignerManager.prototype.save = function(){
	var ret = {
		items: this.generateData()[0],
		animations: [],
		settings: [],
		openedTree: this.openedTree,
		id_counter: this.id_counter,
		selected: this.selected
	};

	$.post(_PAGE_URL + "adminv2/content/generators-edit/"+this.id+"/?__type=ajax&save", {data: JSON.stringify(ret) }, function (data) {
		if (data.ok) {	
			NotificationCreate(data.ok, null, "#", "ok");
		}else{
			NotificationCreate(data.error, null, "#", "error");
		}
	}, "json");
}

DesignerManager.prototype.generateData = function(objects){
	var isRoot = objects == undefined;
	if (objects == undefined) objects = this.objects;

	var gen = "[";
	var index = 0;
	for(var key in objects) {
		var o = objects[key];
		gen+="{";
			gen+="\"id\":\"" + o.id+"\",";
			gen+="\"name\":\"" + o.name+"\",";
			gen+="\"type\":\"" + o.type+"\",";
			gen+="\"options\":" + o.options.generateData();
			if(o.parents != null && o.parents.length > 0) {
				var g = this.generateData(o.parents);
				gen+=",\"parents\":" + g[1];
			}		
		gen+="}";		
		index++;
		if(objects.length != index) { gen+=","; }
	}
	gen+="]";

	return [JSON.parse(gen), gen];
}

DesignerManager.buildDefaultPropertyes = function(){
	return [
		new DesignerProperty("", "Object", DesignerProperty.TYPE_CATEGORY),
		new DesignerProperty("id", "Id", DesignerProperty.TYPE_TEXT),
		new DesignerProperty("name", "Name", DesignerProperty.TYPE_TEXT),
	];
}

DesignerManager.buildDefaultStylePropertyes = function(){
	return [
		new DesignerProperty("", "Style", DesignerProperty.TYPE_CATEGORY),
		new DesignerProperty("position[]", "Position", DesignerProperty.TYPE_PARENT, [
			new DesignerProperty("position", "Position", DesignerProperty.TYPE_SELECT, [{id: "static"}, {id: "relative"}, {id: "fixed"}, {id: "absolute"}, {id: "sticky"}], null, function(element, value){ element.css("position", value); }),
			new DesignerProperty("left", "Left", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.css("left", fixCssNumber(value)); }),
			new DesignerProperty("top", "Top", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.css("top", fixCssNumber(value)); })
		], function(parents, object) {
			var pos = object.options.get("position"); if(pos == null) pos = "static";
			var left = object.options.get("left"); if(left == null) left = 0;
			var top = object.options.get("top"); if(top == null) top = 0;
			return pos + " " + fixCssNumber(left) + ", " + fixCssNumber(top);
		}),
		new DesignerProperty("size[]", "Size", DesignerProperty.TYPE_PARENT, [
			new DesignerProperty("width", "Width", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.css("width", fixCssNumber(value)); }),
			new DesignerProperty("height", "Height", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.css("height", fixCssNumber(value)); }),
		], function(parents, object) {
			var width = object.options.get("width"); if(width == null) width = 0;
			var height = object.options.get("height"); if(height == null) height = 0;
			return width + " x " + height;
		}),	
		new DesignerProperty("background", "Background", DesignerProperty.TYPE_COLOR, null, null, function(element, value){ element.css("background", value); }),
		new DesignerProperty("border", "Border", DesignerProperty.TYPE_PARENT, [
			new DesignerProperty("border-width", "Width", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ element.css("border-width", fixCssNumber(value)); }),
			new DesignerProperty("border-color", "Color", DesignerProperty.TYPE_COLOR, null, null, function(element, value){ element.css("border-color", value); }),
			new DesignerProperty("border-style", "Style", DesignerProperty.TYPE_SELECT, [{id: "none"}, {id: "solid"}, {id: "dashed"}, {id: "dotted"}, {id: "double"}, {id: "groove"}, {id: "hidden"}, {id: "inherit"}, {id: "initial"}, {id: "inset"}], null, function(element, value){ element.css("border-style", value); }),
			new DesignerProperty("border-radius", "Radius", DesignerProperty.TYPE_TEXT, null, null, function(element, value){ console.log(element, value); element.css("border-radius", fixCssNumber(value)); }),
		], function(parents, object) {
			var width = object.options.get("border-width"); if(width == null) width = 0;
			var color = object.options.get("border-color"); if(color == null) color = "";
			var style = object.options.get("border-style"); if(style == null) style = "";

			return fixCssNumber(width)+" "+style+" "+color;
		}),	
		new DesignerProperty("dock", "Dock", DesignerProperty.TYPE_CUSTOM, null, 
			function(parents, object){
				return "click to see";
			}, function(element, value){ 
				//element.css("background", value); 
			}, function(div, object){
				for(var i = 0; i < 3; i++) {
					var btnRow = $("<div class=btn-grid></div>");
					var btnLeftTop = $("<a href=# class='btn-grid-small btn-grid-btn'></a>");
					btnRow.append(btnLeftTop);
					var btnMiddleTop = $("<a href=# class='btn-grid-big btn-grid-btn'></a>");
					btnRow.append(btnMiddleTop);
					var btnRightTop = $("<a href=# class='btn-grid-small btn-grid-btn'></a>");
					btnRow.append(btnRightTop);
					div.append(btnRow);
				}
				div.append("<hr class='btn-grid-separator'/>");
				var row = $("<div class=btn-grid></div>");
				var btnNope = $("<a href=# class='btn-grid-big btn-grid-btn'></a>");
				btnNope.text("Nothing");
				row.append(btnNope);
				div.append(row);
				//div.append("Hello " + object.options.get("width"));
			}
		),
	];
}

DesignerManager.buildDefault = function(options){
	var div = $("<div></div>");
	div.css("background", options.get("background"));
	div.css("width", options.get("width"));
	div.css("height", options.get("height"));
	
	div.css("border", options.get("border"));
	div.css("border-radius", fixCssNumber(options.get("border-radius")));

	div.css("position", options.get("position"));
	div.css("left", options.get("left"));
	div.css("top", options.get("top"));


	return div;
}

DesignerManager.prototype.treeDraw = function(item, level, parent, last){
	var div = $("<div class=item></div>");
	div.data("id", item.id);
	div.data("data", item.options);	
	parent.append(div);	

	if(this.openedTree.includes(item.id)){
		div.addClass("open");
	}

	if(item.parents != null && item.parents.length != undefined && item.parents.length > 0) {
		var opener = $("<span class=opener></span>");
		opener.data("manager", this);
		opener.data("div", div);
		opener.data("item", item);
		opener.on("click", function(){ 
			var item = $(this).data("item");
			var manager = $(this).data("manager");
			$(this).data("div").toggleClass("open"); 
			if(manager.openedTree.includes(item.id)){
				ArrayRemove(manager.openedTree, item.id);
			}else{
				manager.openedTree.push(item.id);
			}
		});		
		div.append(opener);
		div.addClass("with-parent")
	}
	var title = $("<span class=title>"+item.options.get("name")+"</span>");
	div.append(title);
	if(this.selected == item.id) { title.addClass("selected"); }

	var contentmenu = new Menu(div, [{text: "Smazat", classes: "red", key: "delete"}], {side: "right", closeButton: false});
    contentmenu.click(function(self, pos, title){                        
        self.close();
		console.log(self, pos, title);
    });
	title.data("contentmenu", contentmenu);
	title.on("contextmenu", function(event){
		$(this).data("contentmenu").show();
		event.preventDefault();
	});

	title.data("manager", this);
	title.addClass("tree-item-"+item.id);
	title.data("id", item.id);
	title.data("item", item);
	title.on("click", function(){	
		if($(this).hasClass("selected")) return;
		if($(this).data("manager").selected != null) {
			$(".tree-item-" + $(this).data("manager").selected).removeClass("selected");
		}
		$(this).addClass("selected");
		$(this).data("manager").selected = $(this).data("id");		
		$(this).data("manager").renderObjectProps();
		$(this).data("manager").renderSelector();
	});
	title.on("mouseover", function(){
		$(this).data("manager").hover = $(this).data("item");
		$(this).data("manager").updateHover();
	});
	title.on("mouseout", function(){
		$(this).data("manager").hover = null;
		$(this).data("manager").updateHover();
	});

	var subtree = $("<div class='childrens'></div>");
	parent.append(subtree);

	if(item.parents != undefined) {
		for(var key = 0; key < item.parents.length; key++){
			var sub = $("<div class='subtree'></div>");
			if(last) {
				sub.addClass("last");
			}
			subtree.append(sub);

			this.treeDraw(item.parents[key], level + 1, sub, false);
		}
	}
}

DesignerManager.prototype.renderSelector = function(){
	if(this.selected == null) return;

	var o = $("#designer_object_" + this.selected);
	this.selectedResizer.show();
	this.selectedResizer.css("top", o.position().top);
	this.selectedResizer.css("left", o.position().left);
	this.selectedResizer.css("width", o.outerWidth());
	this.selectedResizer.css("height", o.outerHeight());

	var resizeBottom = $(this.selectedResizer.find(".bottom-resizer"));
	resizeBottom.css("left", o.outerWidth() / 2 - resizeBottom.outerWidth() / 2);
	var resizeRight = $(this.selectedResizer.find(".right-resizer"));
	resizeRight.css("top", o.outerHeight() / 2 - resizeRight.outerHeight() / 2);
}

DesignerManager.prototype.updateHover = function(){
	if(this.hover != null) {
		var o = this.hover.object;
		this.highlighter.show();
		this.highlighter.css("top", o.position().top);
		this.highlighter.css("left", o.position().left);
		this.highlighter.css("width", o.outerWidth());
		this.highlighter.css("height", o.outerHeight());
	}else{
		this.highlighter.hide();
	}
}

DesignerManager.prototype.update = function(){
	this.renderSelector();
	this.updateHover();

	//this.timeLineCanvas.width = this.timelineWindow.get().outerWidth();
	//this.timeLineCanvas.height = this.timelineWindow.get().outerHeight();
	//$(this.timeLineCanvas).css("flex", 1);

	//var divCanvas = $("<div></div>");
	/*var canvas = createHiDPICanvas();/*document.createElement('canvas');* /
	divCanvas.append(canvas);
	win.append(divCanvas);
	this.timeLineCanvas = canvas;*/
	//win.append(divCanvas);

	var win = this.timelineWindow.get();
	win.find("div").html("");
	var self = this;
	this.timeLineCanvas = createHiDPICanvas(this.timelineWindow.get().outerWidth(), this.timelineWindow.get().outerHeight() + 5, null, function(){ self.updateTimeLine(); });
	$(this.timeLineCanvas).css("margin-bottom", "-7px");
	win.find("div").append(this.timeLineCanvas);

	/*
	$(this.timeLineCanvas).hide();
	console.log(this.timelineWindow.get().outerHeight());
	$(this.timeLineCanvas).css("width", "100%");
	$(this.timeLineCanvas).css("height", this.timelineWindow.get().outerHeight());//this.timelineWindow.get().outerHeight() 145
	$(this.timeLineCanvas).css("margin-bottom", "-7px");
	$(this.timeLineCanvas).show();
	*/

	this.updateTimeLine();
}

DesignerManager.prototype.updateTimeLine = function(){
	var canvas = this.timeLineCanvas;
	var canvasContext = canvas.getContext("2d");
	var mx = $(canvas).data("mousex");
	var my = $(canvas).data("mousey");

	canvasContext.clearRect(0, 0, canvas.width, canvas.height);
	canvasContext.fillStyle = "rgba(0, 0, 225, 1.0)";
    canvasContext.fillRect(mx, my, 25, 25);
	canvasContext.fillText($(canvas).data("mousex") + "x" + $(canvas).data("mousey"), 70, 70);
};

DesignerManager.prototype.buildTimeLine = function(){
	var win = this.timelineWindow.get();
	var divCanvas = $("<div></div>");
	/*var canvas = createHiDPICanvas();/*document.createElement('canvas');* /
	divCanvas.append(canvas);
	win.append(divCanvas);
	this.timeLineCanvas = canvas;*/
	win.append(divCanvas);
};

DesignerManager.prototype.refreshTree = function() {
	var tree = this.objectWindow.get();

	var div;
	if(tree.find(".tree").length == 0) {
		div = $("<div class=tree></div>");
		tree.append(div);
	}else{
		div = tree.find(".tree");
		div.html("");
	}

	for(var key = 0; key < this.objects.length; key++) {
		this.treeDraw(this.objects[key], 0, div, key == this.objects.length - 1);
	}
}

DesignerManager.prototype.buildDesignerObject = function(element, parent){
	var object = element.create();
	object.attr("id", "designer_object_" + (element.id));
	parent.append(object);
	element.object = object;

	if(element.parents != undefined) {
		for(var key = 0; key < element.parents.length; key++) {
			this.buildDesignerObject(element.parents[key], object);
		}
	}
}

DesignerManager.prototype.rebuildObjects = function(){
	this.designerWindow.get().html("");
	for(var key = 0; key < this.objects.length; key++) {
		this.buildDesignerObject(this.objects[key], this.designerWindow.get());
	}

	var highlighter = $("<div class=highlighter></div>");
	highlighter.hide();
	this.highlighter = highlighter;
	this.designerWindow.get().append(highlighter);

	var selectedResizer = $("<div class=selected-resizer></div>");
	selectedResizer.hide();
	this.selectedResizer = selectedResizer;
	this.designerWindow.get().append(selectedResizer);

	var rightResize = $("<div class=right-resizer></div>");
	selectedResizer.append(rightResize);	
	rightResize.data("manager", this);
	rightResize.on("mousedown", function(){
		var m = $(this).data("manager");
		var s = m.getSelected();
		m.saveMouse();
		if(s != null) {
			m.mouse.event = "resize-right";
		}
	});

	var bottomResize = $("<div class=bottom-resizer></div>");
	selectedResizer.append(bottomResize);
	bottomResize.data("manager", this);
	bottomResize.on("mousedown", function(){
		var m = $(this).data("manager");
		var s = m.getSelected();
		m.saveMouse();
		if(s != null) {
			m.mouse.event = "resize-bottom";
		}
	});

	this.renderSelector();
	this.updateHover();
}

DesignerManager.prototype.buildDesigner = function(){	
	this.rebuildObjects();	
	this.buildTimeLine();

	/*
	var timelineTools = $("<div class=gridWindowSubTitle></div>");
	var labelTitle = $("<span class=label></span>");
	labelTitle.html("Name");
	timelineTools.append(labelTitle);
	this.timelineLabelInput = $("<input type='text' />");
	timelineTools.append(this.timelineLabelInput);
	timelineTools.append("<span>id: </span>");
	this.timelineLabelId = $("<span></span>");
	this.timelineLabelId.html("0");
	timelineTools.append(this.timelineLabelId);
	this.timelineSaveButton = $("<button class='btn btn-primary'></button>");
	this.timelineSaveButton.html("Save");
	timelineTools.append(this.timelineSaveButton);
	this.timelineWindow.get().append(timelineTools);
	*/

	var controls = this.designerWindow.getControlls();
	var saveBtn = $("<a href=# class=controll-button></a>");
	saveBtn.append("<i class=\"fas fa-save\"></i> Save");
	saveBtn.attr("title", "Save");
	saveBtn.data("manager", this);
	saveBtn.click(function(){
		$(this).data("manager").save();
	});
	controls.append(saveBtn);

	var controlsObjects = this.objectWindow.getControlls();
	var addBtn = $("<a href=# class=controll-button></a>");
	addBtn.append("<i class=\"fas fa-plus\"></i>");
	addBtn.attr("title", "Add object");
	addBtn.data("manager", this);
	addBtn.click(function(){
		$(this).data("manager").objectWindow.get().find(".object-picker").toggle();
	});
	controlsObjects.append(addBtn);

	var tree = this.objectWindow.get();
	var picker = $("<div class=object-picker></div>");
	tree.append(picker);
	for(var key in this.avalibleObjects) {
		var o = this.avalibleObjects[key];
		if(!o.canBeAdded) continue;

		var butt = $("<a href=#></a>");
		butt.text(o.name);
		butt.data("props", o);
		butt.data("manager", this);
		butt.click(function(){
			var p = $(this).data("props");
			var m = $(this).data("manager");
			var o = m.addObject(p.type, null, p.name, null, m.getSelected());
			m.refreshTree();
			m.rebuildObjects();
			m.objectWindow.get().find(".object-picker").toggle();			
		});
		picker.append(butt);
	}
}

DesignerManager.prototype.find = function(objectId, parent) {
	if(parent == undefined) {
		parent = this.objects;
	}

	for(var key = 0; key < parent.length; key++) {
		if(parent[key].id == objectId) {
			return parent[key];
		}
		if(parent[key].parents != undefined) {
			var sel = this.find(objectId, parent[key].parents);
			if(sel != null)
				return sel;
		}
	}

	return null;
} 

DesignerManager.prototype.getSelected = function(){
	return this.find(this.selected);
}

DesignerManager.prototype.findProp = function(object, propId, props){
	var obj;
	if(typeof object === 'number')
		obj = this.find(object);
	else if(typeof propId == 'undefined' && this.getSelected() != null){
		propId = object;
		obj = this.getSelected();
	}
	else
		obj = object;
	
	if(props == undefined)
		props = obj.props;

	for(var key in props) {
		var prop = props[key];
		if(prop.id == propId) {
			return [true, propId, prop, obj.options.get(prop.id)];
		}else if(prop.type == DesignerProperty.TYPE_PARENT) {
			var find = this.findProp(obj, propId, prop.parents);
			if(find[0] != false) {
				return find;
			}
		}
	}

	return [false, null, null];
}

DesignerManager.prototype.renderProp = function(prop) {

}

DesignerManager.prototype.updatePropText = function(prop, object){
	if(prop.type == DesignerProperty.TYPE_TEXT || prop.type == DesignerProperty.TYPE_SELECT) {	
		var val = object.options.get(prop.id);
		if(val == undefined || val == "") {
			prop.div.text("nothign");
			prop.div.addClass("empty");
		}else{
			prop.div.text(val);
			prop.div.removeClass("empty");
		}

		if(prop.input != undefined) {
			prop.input.val(val);
		}
	}else if(prop.type == DesignerProperty.TYPE_COLOR) {
		var val = object.options.get(prop.id);
		var c = prop.div.find(".color");
		var t = prop.div.find(".text");

		if(val == undefined || val == "") {
			c.css("background", null);
			t.text("nothign");
			prop.div.addClass("empty");
		}else{
			c.css("background", val);
			t.text(val);
			prop.div.removeClass("empty");
		}

		if(prop.input != undefined) {
			prop.input.val(val);
		}
	}else if(prop.type == DesignerProperty.TYPE_PARENT || prop.type == DesignerProperty.TYPE_CUSTOM){
		prop.div.text(prop.callback(prop.parents, object));
	}

	this.update();
}

DesignerManager.prototype.renderObjectProps = function(props, parent){
	if(this.selected == null) return;

	var object = this.getSelected();

	if(props == undefined) {
		props = object.props;
	}
	if(parent == undefined) {
		var propWin = this.propertyWindow.get();		
		propWin.html("");
		var divI = $("<div class='window-prop-height'></div>");
		propWin.append(divI);
		parent = divI;
		this.windowResize();
	}

	if(props == undefined || props == null) return;

	for(var key = 0; key < props.length; key++) {
		var prop = props[key];
		var div = null;

		if(prop.type == DesignerProperty.TYPE_CATEGORY) {
			var divo = $("<div></div>");
			divo.addClass("prop-category");
			divo.html(prop.name);
			parent.append(divo);

			var inside = $("<div class=prop-category-childrens></div>");
			parent.append(inside);

			parent = inside;
		}else if(prop.type == DesignerProperty.TYPE_TEXT || prop.type == DesignerProperty.TYPE_COLOR) {
			div = $("<div></div>");
			div.addClass("prop-text");
			var name = $("<div></div>");
			name.text(prop.name);
			div.append(name);
			var value = $("<div class=valedit></div>");
			var valueText;
			var valueInput = $("<input type=text />");

			if(prop.type == DesignerProperty.TYPE_COLOR) {
				valueText = $("<div class=textColor></div>");
				var cCol = $("<span class=color></div>");
				cCol.css("background", prop.value);
				cCol.data("input", valueInput);
				cCol.on("click", function(){
					new ColorPicker().popup($(this), $(this).data("input"));
				});
				var tText = $("<span class=text></span>");
				valueText.append(cCol);
				valueText.append(tText);
				value.append(valueText);
			}else{
				valueText = $("<div></div>");
				value.append(valueText);
			}

			prop.div = valueText;
			this.updatePropText(prop, object);
			
			prop.input = valueInput;

			valueInput.val(object.options.get(prop.id));
			value.append(valueInput);
			div.append(value);			

			valueText.data("manager", this);
			valueText.data("input", valueInput);
			valueText.on("dblclick", function(){
				var m = $(this).data("manager");
				if(m.latestPropsEdited.length == 2) {
					m.latestPropsEdited[0].show();
					m.latestPropsEdited[1].hide();
					m.latestPropsEdited = [];
				}

				$(this).hide();
				$(this).data("input").show();
				$(this).data("input").focus();
				$(this).data("input").select();
				
				m.latestPropsEdited = [$(this), $(this).data("input")];
			});

			function afterInputEdit(type, event, element) {
				if((type == "keyup" && event.keyCode == 13) || type == "blur") {
					element.hide();
					element.data("text").show();
					if(element.data("object").setOptions(element.data("prop").id, element.val())){
						//element.data("text").text(element.val());
						element.data("manager").refreshTree();
						element.data("manager").updatePropText(element.data("prop"), element.data("object"));
					}
				}
			}

			valueInput.data("manager", this);
			valueInput.data("object", object);
			valueInput.data("options", object.options);
			valueInput.data("prop", prop);
			valueInput.data("text", valueText);
			valueInput.data("oncomplete", afterInputEdit);
			valueInput.on("blur", function(e){
				$(this).data("oncomplete")("blur", e, $(this));
				return;
			});
			valueInput.on("keyup", function(e){
				$(this).data("oncomplete")("keyup", e, $(this));
				return;
			});
			parent.append(div);
		}else if(prop.type == DesignerProperty.TYPE_SELECT) {
			div = $("<div></div>");
			div.addClass("prop-text");
			var name = $("<div></div>");
			name.text(prop.name);
			div.append(name);
			var value = $("<div class=valedit></div>");
			var valueText = $("<div></div>");
			prop.div = valueText;			

			var valueInput = $("<select></select>");
			prop.input = valueInput;

			value.append(valueText);
			this.updatePropText(prop, object);

			valueText.data("manager", this);
			valueText.data("input", valueInput);
			valueText.on("click", function(){
				var m = $(this).data("manager");
				if(m.latestPropsEdited.length == 2) {
					m.latestPropsEdited[0].show();
					m.latestPropsEdited[1].hide();
					m.latestPropsEdited = [];
				}

				$(this).hide();
				$(this).data("input").show();
				$(this).data("input").focus();
				$(this).data("input").select();
				
				m.latestPropsEdited = [$(this), $(this).data("input")];
			});
			
			var opt = $("<option></option>");
			opt.attr("value", "").text("-------").prop("disabled", true);
			valueInput.append(opt);

			for(var skey in prop.parents) {
				var op = prop.parents[skey];

				var opt = $("<option></option>");
				opt.attr("value", op.id);
				if(op.name != undefined) { opt.text(op.name); } else { opt.text(op.id); }
				valueInput.append(opt);
			}
			value.append(valueInput);
			valueInput.val(object.options.get(prop.id));

			function afterInputEdit(type, event, element) {
				if(type == "blur") {
					element.hide();
					element.data("text").show();
					if(element.data("object").setOptions(element.data("prop").id, element.val())){
						element.data("text").text(element.val());
						element.data("manager").refreshTree();
						element.data("manager").updatePropText(element.data("prop"), element.data("object"));
					}
				}
			}

			valueInput.data("manager", this);
			valueInput.data("object", object);
			valueInput.data("options", object.options);
			valueInput.data("prop", prop);
			valueInput.data("text", valueText);
			valueInput.data("oncomplete", afterInputEdit);
			valueInput.on("blur change", function(e){
				$(this).data("oncomplete")("blur", e, $(this));
				return;
			});

			div.append(value);
			parent.append(div);
		}else if(prop.type == DesignerProperty.TYPE_PARENT) {
			div = $("<div></div>");
			div.addClass("prop-text");
			div.addClass("prop-expandable")
			var name = $("<div></div>");
			var opener = $("<span class=opener></span>");
			opener.data("div", div);
			opener.on("click", function(){
				$(this).data("div").toggleClass("opened");
			});
			name.append(opener);
			var text = $("<span></span>");
			text.text(prop.name);
			name.append(text);
			div.append(name);
			var value = $("<div class='valtext'></div>");
			var valueDiv = $("<div></div>");
			prop.div = valueDiv;

			this.updatePropText(prop, object);
			//valueDiv.html(prop.callback(prop.parents, object));
			value.append(valueDiv);
			div.append(value);

			parent.append(div);

			var childrens = $("<div></div>");
			parent.append(childrens);

			this.renderObjectProps(prop.parents, childrens);			
		}
		else if(prop.type == DesignerProperty.TYPE_CHECK) {
			div = $("<div></div>");
			div.addClass("prop-text");
			var name = $("<div></div>");
			var text = $("<span></span>");
			text.text(prop.name);
			name.append(text);
			div.append(name);
			var value = $("<div class='valtext'></div>");
			var valueDiv = $("<div></div>");
			prop.div = valueDiv;	
			
			var check = $("<input type=checkbox />");
			prop.input = check;
			check.prop("checked", object.options.get(prop.id) == "true");
			valueDiv.append(check);
			valueDiv.addClass("centered-value");

			check.data("object", object);
			check.data("manager", this);
			check.data("prop", prop);
			check.on("change", function(){
				if($(this).data("object").setOptions($(this).data("prop").id, $(this).is(":checked")?"1":"0")){
					$(this).data("manager").refreshTree();
					$(this).data("manager").updatePropText($(this).data("prop"), $(this).data("object"));
				}
			});

			value.append(valueDiv);
			div.append(value);

			parent.append(div);			
		}
		else if(prop.type == DesignerProperty.TYPE_CUSTOM) {
			//prop.builder
			div = $("<div></div>");
			div.addClass("prop-text");
			var name = $("<div></div>");
			var text = $("<span></span>");
			text.text(prop.name);
			name.append(text);
			div.append(name);
			var value = $("<div class='valtext'></div>");
			var valueDiv = $("<div></div>");
			prop.div = valueDiv;
			this.updatePropText(prop, object);

			var valueInput = $("<div></div>");
			valueInput.addClass("data-input-popup");
			prop.input = valueInput;

			valueDiv.data("prop", prop);
			valueDiv.data("object", object);
			valueDiv.data("manager", this);
			valueDiv.data("input", valueInput);
			valueDiv.on("click", function(){
				var prop = $(this).data("prop");
				$(this).data("input").html("");
				prop.builder($(this).data("input"), $(this).data("object"));
				$(this).show();

				var m = $(this).data("manager");
				if(m.latestPropsEdited.length == 2) {
					m.latestPropsEdited[0].show();
					m.latestPropsEdited[1].hide();
					m.latestPropsEdited = [];
				}

				$(this).hide();
				$(this).data("input").show();
				
				m.latestPropsEdited = [$(this), $(this).data("input")];
			});

			value.append(valueDiv);
			value.append(valueInput);
			div.append(value);			

			parent.append(div);		
		}
	}
}

function bytesToSize(bytes) {
	var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
	if (bytes == 0) return '0 Byte';
	var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
	return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
}

var AnimationManager = function(element, duration, animations){
	this.element = $(element);
	this.animations = animations;

	if(duration == undefined) duration = AnimationManager.defaultDuration;
	this.duration = duration;
	this.data = {};
	
	this.category = {};
	this.reset();
};


AnimationManager.defaultDuration = 1000;
AnimationManager.speed = 1;

AnimationManager.prototype.reset = function(){
	this.current = 0;
	this.timeFrom = null;
	this.timeTo = null;
	this.pause = false;
	this.progress = 0;

	this.category = {};
	for(var key in this.animations) {
		var animation = this.animations[key];
		var anim = {name: "default", fun: DefaultTransitionUsage};
		if(TransitionUsage[key] != undefined) anim = TransitionUsage[key];
		if(this.category[anim.name] == undefined) this.category[anim.name] = {fun: anim.fun, list: []};
		this.category[anim.name].list[key] = animation;
	}
}

AnimationManager.prototype.prepare = function(){
	if (this.timeFrom == undefined) {
        this.timeFrom = Date.now();
        this.timeTo = Date.now() + this.duration;
    }
}

AnimationManager.prototype.start = function(){
	this.prepare();
	this.step();
}

AnimationManager.prototype.pause = function(){
	this.pause = true;
}

AnimationManager.prototype.step = function(){
	var $self = this;

	this._max = (this.timeTo - this.timeFrom);
    this._one = (this.timeTo - Date.now()) / this._max;

	this._step = AnimationManager.speed / this._one;
    this.progress = this.current / this.duration;

	//var c = pickHex(from, to, this.progress);
    //this.element.css("background", "rgba("+c[0]+", "+c[1]+", "+c[2]+", 1)");

	for(var key in this.category) {
		var cat = this.category[key];
		cat.fun(this.element[0], this.progress, this.duration, cat.list, this.data);
	}

	this.current += this._step;

	if(this.progress < 1 && !this.pause) {		
		setTimeout(function(){ 
			$self.step(); 			
		}, AnimationManager.speed);
	}
}

function ColorTransition(element, progress, duration, data, customData){
    for(var key in data) {
        var prop = data[key];

        if(customData["anim_" + key] == undefined) {
            customData["anim_" + key] = {
                value: prop,
                valueOriginal: prop,
                cssProp: key,
                cssValue: element.style[key],
                cssCurrent: "",
                parent: element.parentElement
            };

            if (typeof customData["anim_" + key].value == "string") customData["anim_" + key].value = hexToRgbA(customData["anim_" + key].value);
            if (typeof customData["anim_" + key].cssValue == "string") customData["anim_" + key].cssValue = hexToRgbA(customData["anim_" + key].cssValue);
        }

        var c = pickHex(customData["anim_" + key].cssValue, customData["anim_" + key].value, progress);
        element.style[key] = customData["anim_" + key].cssCurrent = "rgba("+c[0]+", "+c[1]+", "+c[2]+", 1)";
    }
}

function StandartCssTransition(element, progress, duratiom, data, customData) {
    for(var key in data) {
        var prop = data[key];

        var convertor = StandartCssTransitionCalulcator[key];
        if(customData["anim_" + key] == undefined) {
            customData["anim_" + key] = {
                value: parseCssNumber(prop),
                originalValue: prop,
                cssProp: key,
                cssValue: element.style[key],
                cssNumber: parseCssNumber(element.style[key]),
                parent: element.parentElement,
                withoutUnit: false,
                textValue: false,
                changeValueAtFinish: false,                
            };
           
            if(convertor != undefined) {
                if(convertor.textValue != undefined) {
                    customData["anim_" + key].textValue = convertor.textValue;
                }

                if(!customData["anim_" + key].textValue) {
                    if(customData["anim_" + key].value.unit == "%" && convertor.fromPercent == undefined){
                        customData["anim_" + key].value.unit = "px";
                        customData["anim_" + key].value.number = convertor.toPercent(element, customData["anim_" + key].parent, customData["anim_" + key].value);
                    }
                    else if(customData["anim_" + key].value.unit == "px" && customData["anim_" + key].cssNumber.unit == "%" && convertor.fromPercent != undefined) {
                        customData["anim_" + key].cssNumber.unit = "px";
                        customData["anim_" + key].cssNumber.number = convertor.fromPercent(element, customData["anim_" + key].parent, customData["anim_" + key].cssNumber);
                    }else if(customData["anim_" + key].value.unit == "%" && customData["anim_" + key].cssNumber.unit == "px" && convertor.toPercent != undefined){
                        customData["anim_" + key].cssNumber.unit = "%";
                        customData["anim_" + key].cssNumber.number = convertor.toPercent(element, customData["anim_" + key].parent, customData["anim_" + key].cssNumber);
                    }
                }

                if(customData["anim_" + key].cssNumber.number == null && convertor.default != undefined) {
                    customData["anim_" + key].cssNumber = convertor.default;
                }

                if(convertor.changeValueAtFinish != undefined) {
                    customData["anim_" + key].changeValueAtFinish = convertor.changeValueAtFinish;
                }
            }

            if(!customData["anim_" + key].textValue) {
                if(customData["anim_" + key].cssNumber.number == null) {
                    customData["anim_" + key].cssNumber.number = 0;
                }

                if(customData["anim_" + key].value.operation == "+=") {
                    customData["anim_" + key].value.number = parseFloat(customData["anim_" + key].cssNumber.number + customData["anim_" + key].value.number);                
                }
                else if(customData["anim_" + key].value.operation == "-=") {
                    customData["anim_" + key].value.number = parseFloat(customData["anim_" + key].cssNumber.number - customData["anim_" + key].value.number);
                }

                if(convertor != undefined) {
                    if(convertor.max != undefined && customData["anim_" + key].value.number > convertor.max) { customData["anim_" + key].value.number = convertor.max; }
                    if(convertor.min != undefined && customData["anim_" + key].value.number < convertor.min) { customData["anim_" + key].value.number = convertor.min; }
                    if(convertor.withoutUnit != undefined) { customData["anim_" + key].withoutUnit = convertor.withoutUnit; }
                }

                customData["anim_" + key].max = customData["anim_" + key].value.number - customData["anim_" + key].cssNumber.number;
            }
        }

        var anim = customData["anim_" + key];
        if(progress == 0) {
            //console.log(anim);
            //console.log(customData["anim_" + key].value.number, customData["anim_" + key].cssNumber.number, customData["anim_" + key].max);
        }

        if(convertor != undefined && convertor.udpate != undefined) {
            convertor.udpate(element, progress, customData["anim_" + key], customData);
        }

        if(customData["anim_" + key].textValue) {
            if((progress == 1 || !customData["anim_" + key].changeValueAtFinish) && customData["anim_" + key].cssValue != customData["anim_" + key].originalValue) {
                element.style[key] = customData["anim_" + key].cssValue = customData["anim_" + key].originalValue;
            }
        }else{
            var state = customData["anim_" + key].cssNumber.number + (customData["anim_" + key].max * progress);        
            element.style[key] = customData["anim_" + key].cssValue = state + (customData["anim_" + key].withoutUnit? "": customData["anim_" + key].value.unit);
        }
    }
}

let StandartCssTransitionCalulcator = {
    "width": {
        toPercent: function(element, parent, cssNumber){
            var parentWidth = parent.offsetWidth;
            //console.log(parseFloat(cssNumber.number), parseFloat(parentWidth));
            return (parseFloat(cssNumber.number) / parseFloat(parentWidth)) * 100;
        },
        fromPercent: function(element, parent, cssNumber){
            var parentWidth = parent.offsetWidth;
            return parentWidth;
        },
        udpate: function(element, progress, data, customData) {
            if(progress == 1) {
                element.style.overflow = customData.saveOverflow;
                delete customData.saveOverflow;
            }else{
                if(customData.saveOverflow == undefined) {
                    customData.saveOverflow = element.style.overflow;
                }
                element.style.overflow = "hidden";
            }
        }
    },
    "height": {
        toPercent: function(element, parent, cssNumber){
            var parentHeight = parent.offsetHeight;
            return (parseFloat(cssNumber.number) / parseFloat(parentHeight)) * 100;
        },
        fromPercent: function(element, parent, cssNumber){
            var parentHeight = parent.offsetHeight;
            return parentHeight;
        },
        udpate: function(element, progress, data, customData) {
            if(progress == 1) {
                element.style.overflow = customData.saveOverflow;
                delete customData.saveOverflow;
            }else{
                if(customData.saveOverflow == undefined) {
                    customData.saveOverflow = element.style.overflow;
                }
                element.style.overflow = "hidden";
            }
        }
    },
    "opacity": {		
        min: 0,
        max: 1,
        default: parseCssNumber(1),
        withoutUnit: true,
        toPercent: function(element, parent, cssNumber){
            var num = cssNumber.number;
            return num / 100;
        }
    },
    "display": {
        textValue: true,
        changeValueAtFinish: true
    }
};

let DefaultTransitionUsage = StandartCssTransition;
let TransitionUsage = {
    "color": 			{name: "color", fun: ColorTransition},
    "background": 		{name: "color", fun: ColorTransition},
    "backgroundColor": 	{name: "color", fun: ColorTransition}
}
let AnimationTypes = {
	"default": DefaultTransitionUsage,
	"color": ColorTransition
};

let wellKnowColorToHEX = {
    "aliceblue": "#f0f8ff",
    "antiquewhite": "#faebd7",
    "aqua": "#00ffff",
    "aquamarine": "#7fffd4",
    "azure": "#f0ffff",
    "beige": "#f5f5dc",
    "bisque": "#ffe4c4",
    "black": "#000000",
    "blanchedalmond": "#ffebcd",
    "blue": "#0000ff",
    "blueviolet": "#8a2be2",
    "brown": "#a52a2a",
    "burlywood": "#deb887",
    "cadetblue": "#5f9ea0",
    "chartreuse": "#7fff00",
    "chocolate": "#d2691e",
    "coral": "#ff7f50",
    "cornflowerblue": "#6495ed",
    "cornsilk": "#fff8dc",
    "crimson": "#dc143c",
    "cyan": "#00ffff",
    "darkblue": "#00008b",
    "darkcyan": "#008b8b",
    "darkgoldenrod": "#b8860b",
    "darkgray": "#a9a9a9",
    "darkgreen": "#006400",
    "darkgrey": "#a9a9a9",
    "darkkhaki": "#bdb76b",
    "darkmagenta": "#8b008b",
    "darkolivegreen": "#556b2f",
    "darkorange": "#ff8c00",
    "darkorchid": "#9932cc",
    "darkred": "#8b0000",
    "darksalmon": "#e9967a",
    "darkseagreen": "#8fbc8f",
    "darkslateblue": "#483d8b",
    "darkslategray": "#2f4f4f",
    "darkslategrey": "#2f4f4f",
    "darkturquoise": "#00ced1",
    "darkviolet": "#9400d3",
    "deeppink": "#ff1493",
    "deepskyblue": "#00bfff",
    "dimgray": "#696969",
    "dimgrey": "#696969",
    "dodgerblue": "#1e90ff",
    "firebrick": "#b22222",
    "floralwhite": "#fffaf0",
    "forestgreen": "#228b22",
    "fuchsia": "#ff00ff",
    "gainsboro": "#dcdcdc",
    "ghostwhite": "#f8f8ff",
    "goldenrod": "#daa520",
    "gold": "#ffd700",
    "gray": "#808080",
    "green": "#008000",
    "greenyellow": "#adff2f",
    "grey": "#808080",
    "honeydew": "#f0fff0",
    "hotpink": "#ff69b4",
    "indianred": "#cd5c5c",
    "indigo": "#4b0082",
    "ivory": "#fffff0",
    "khaki": "#f0e68c",
    "lavenderblush": "#fff0f5",
    "lavender": "#e6e6fa",
    "lawngreen": "#7cfc00",
    "lemonchiffon": "#fffacd",
    "lightblue": "#add8e6",
    "lightcoral": "#f08080",
    "lightcyan": "#e0ffff",
    "lightgoldenrodyellow": "#fafad2",
    "lightgray": "#d3d3d3",
    "lightgreen": "#90ee90",
    "lightgrey": "#d3d3d3",
    "lightpink": "#ffb6c1",
    "lightsalmon": "#ffa07a",
    "lightseagreen": "#20b2aa",
    "lightskyblue": "#87cefa",
    "lightslategray": "#778899",
    "lightslategrey": "#778899",
    "lightsteelblue": "#b0c4de",
    "lightyellow": "#ffffe0",
    "lime": "#00ff00",
    "limegreen": "#32cd32",
    "linen": "#faf0e6",
    "magenta": "#ff00ff",
    "maroon": "#800000",
    "mediumaquamarine": "#66cdaa",
    "mediumblue": "#0000cd",
    "mediumorchid": "#ba55d3",
    "mediumpurple": "#9370db",
    "mediumseagreen": "#3cb371",
    "mediumslateblue": "#7b68ee",
    "mediumspringgreen": "#00fa9a",
    "mediumturquoise": "#48d1cc",
    "mediumvioletred": "#c71585",
    "midnightblue": "#191970",
    "mintcream": "#f5fffa",
    "mistyrose": "#ffe4e1",
    "moccasin": "#ffe4b5",
    "navajowhite": "#ffdead",
    "navy": "#000080",
    "oldlace": "#fdf5e6",
    "olive": "#808000",
    "olivedrab": "#6b8e23",
    "orange": "#ffa500",
    "orangered": "#ff4500",
    "orchid": "#da70d6",
    "palegoldenrod": "#eee8aa",
    "palegreen": "#98fb98",
    "paleturquoise": "#afeeee",
    "palevioletred": "#db7093",
    "papayawhip": "#ffefd5",
    "peachpuff": "#ffdab9",
    "peru": "#cd853f",
    "pink": "#ffc0cb",
    "plum": "#dda0dd",
    "powderblue": "#b0e0e6",
    "purple": "#800080",
    "rebeccapurple": "#663399",
    "red": "#ff0000",
    "rosybrown": "#bc8f8f",
    "royalblue": "#4169e1",
    "saddlebrown": "#8b4513",
    "salmon": "#fa8072",
    "sandybrown": "#f4a460",
    "seagreen": "#2e8b57",
    "seashell": "#fff5ee",
    "sienna": "#a0522d",
    "silver": "#c0c0c0",
    "skyblue": "#87ceeb",
    "slateblue": "#6a5acd",
    "slategray": "#708090",
    "slategrey": "#708090",
    "snow": "#fffafa",
    "springgreen": "#00ff7f",
    "steelblue": "#4682b4",
    "tan": "#d2b48c",
    "teal": "#008080",
    "thistle": "#d8bfd8",
    "tomato": "#ff6347",
    "turquoise": "#40e0d0",
    "violet": "#ee82ee",
    "wheat": "#f5deb3",
    "white": "#ffffff",
    "whitesmoke": "#f5f5f5",
    "yellow": "#ffff00",
    "yellowgreen": "#9acd32"
};
var hexToRGBACache = {};
function hexToRgbA(hex){
    if(hexToRGBACache[hex] != undefined) return hexToRGBACache[hex];
    if(wellKnowColorToHEX[hex] != undefined) hex = wellKnowColorToHEX[hex];

    var c, r;
    if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
        c= hex.substring(1).split('');
        if(c.length== 3){
            c= [c[0], c[0], c[1], c[1], c[2], c[2]];
        }
        c= '0x'+c.join('');
        r = [(c>>16)&255, (c>>8)&255, c&255, 1];
    }
    else 
        r = [255, 255, 255, 1];
    
    hexToRGBACache[hex] = r;
    return r;
}

function pickHex(color1, color2, weight) {
    var w1 = weight;
    var w2 = 1 - w1;
    var rgb = [Math.round(color2[0] * w1 + color1[0] * w2),
        Math.round(color2[1] * w1 + color1[1] * w2),
        Math.round(color2[2] * w1 + color1[2] * w2)];
    return rgb;
}

function fixCssNumber(number) {
	var css = parseCssNumber(number);
	if(css.number == null) return null;
	return css.number + css.unit;
}

function parseCssNumber(number){    
    var operation = "=";
    if(typeof number == "string") {
        number = number.trim();

        if(number.substr(0,2) == "+="){
            operation = "+=";
            number = number.substr(2).trim();
        }else if(number.substr(0,2) == "-="){
            operation = "-=";
            number = number.substr(2).trim();
        }
    }	
    
    if(typeof number == "number") {
        return {unit: "px", number, operation};
    }else if(typeof number == "string" && number.endsWith("px")){
        return {unit: "px", number: parseFloat(number.substr(0, number.length - 2)), operation};
    }else if(typeof number == "string" && number.endsWith("%")){
        return {unit: "%", number: parseFloat(number.substr(0, number.length - 1)), operation};
    }else if(number != null && number != ""){
        return {unit: "px", number: parseFloat(number), operation};
    }

    return {unit: "px", number: null, operation};
}

/////////ColorPicker/////////////
var ColorPickerFor = {INPUT: 0, COLORDIV: 1};
var ColorPicker = function(options){
	this.options = options;
	this.type = ColorPickerFor.INPUT;
	this.canHide = false;
	this.color = {h: 240, s: 100, v: 0, a: 1};
	this.val = null;
	this.firstColor = null;
	this.saveE = {left: 0, top: 0};
	this.build();
}
ColorPicker.div = null;

ColorPicker.prototype.popup = function(element, input, hex){
	this.element = $(element);
	if(this.element.is("input")) {
		this.type = ColorPickerFor.INPUT;
		this.input = $(element);
	}else{
		this.type = ColorPickerFor.COLORDIV;
		this.input = $(input);
	}
	
	var color;
	if(this.input.length > 0 && this.input.val() != null && hex == undefined) {
		var c = hexToRgbA(this.input.val());
		color = rgb2hsv(c[0], c[1], c[2]);
	}else{
		var c = hexToRgbA(hex);
		color = rgb2hsv(c[0], c[1], c[2]);
	}
	this.color = {h: color.h, s: color.s, v: color.v, a: 1};

	this.show();
}

ColorPicker.prototype.show = function(){
	$(window).data("colorPicker", this);
	//ColorPicker.div.show();
	ColorPicker.div.addClass("visible");
	this.position();
	this.update();
	setTimeout(function(){ $(window).data("colorPicker").canHide = true; }, 100);
	
	if(isMobile()) {
		$("body").css("overflow", "hidden");
	}
}

ColorPicker.prototype.hide = function(){
	//ColorPicker.div.hide();
	ColorPicker.div.removeClass("visible");

	if(isMobile()) {
		$("body").css("overflow", "auto");
	}
}

ColorPicker.prototype.position = function(){
	if(ColorPicker.div == null || (!ColorPicker.div.is(":visible") && !ColorPicker.div.hasClass("visible"))) return;

	if(isMobile()) {
		ColorPicker.div.css("left", $(window).width() / 2 - ColorPicker.div.outerWidth() / 2);
		ColorPicker.div.css("top", $(window).height() / 2 - ColorPicker.div.outerHeight() / 2);
	}else{
		var redrawTop = false;
		if(redrawTop) {
			ColorPicker.div.css("left", this.element.offset().left);
			ColorPicker.div.css("top", this.element.offset().top - ColorPicker.div.outerHeight());
		}else{
			ColorPicker.div.css("left", this.element.offset().left);
			ColorPicker.div.css("top", this.element.offset().top + 2 + this.input.outerHeight());
		}		
	}
}

ColorPicker.prototype.update = function(){
	var rgb = hsvToRgb(this.color.h, this.color.s, this.color.v);
	var css = "--color: " + this.getColor(1);

	var l = this.color.s*((ColorPicker.colorPalleteContainer.outerWidth())/100) - (ColorPicker.colorPicker.outerWidth() / 2);
	var t = (100 - this.color.v) * ((ColorPicker.colorPalleteContainer.outerHeight())/100) - (ColorPicker.colorPicker.outerHeight() / 2);
	ColorPicker.colorPicker.attr("style", css+";left: "+l+"px; top: "+t+"px;");

	var css2 = "--color: " + this.getColor(0);
	ColorPicker.colorPallete.attr("style", css2);
	ColorPicker.colorChooserPicker.attr("style", css2+";left: calc("+((this.color.h/360)*100)+"% - "+(ColorPicker.colorChooserPicker.outerWidth()/2)+"px)");

	var q = hsvToRgb(this.color.h, this.color.s, this.color.v);
	var col = rgbToHex(q[0], q[1], q[2]);
	if(this.input != null && this.firstColor != null && this.firstColor != col && (q[0] !=0 && q[1] != 0 && q[2] != 0)) {
		this.input.val(col).trigger("blur");
		this.element.css("background-color", col);
	}else{
		if(q[0] !=0 && q[1] != 0 && q[2] != 0)
			this.firstColor = col;
	}
}

ColorPicker.prototype.getColor = function(type){
	var rgb;
	if(type == 1) {
		rgb = hsvToRgb(this.color.h, this.color.s, this.color.v);
	}else{
		rgb = hsvToRgb(this.color.h, 100, 100);
	}

	return "rgba("+rgb[0]+", "+rgb[1]+", "+rgb[2]+", 1)";
}

ColorPicker.prototype.build = function(){
	if(ColorPicker.div != null) return;	

	var div = ColorPicker.div = $("<div class='cp'></div>");

	var section = $("<div class='cp-section'></div>");
	div.append(section);

	var colorPalleteContainer = ColorPicker.colorPalleteContainer = $("<div class='cp-palette-container'></div>");
	section.append(colorPalleteContainer);

	var colorPicker = ColorPicker.colorPicker = $("<div class='cp-picker'></div>");
	//colorPicker.attr("style", "--color: #0015ff;");
	colorPicker.on("mousedown touchstart", function(event){
		var cp = $(window).data("colorPicker");
		if(cp.move != "picker") {
			cp.move = "picker";
			var m = getPositionFromEvent(event);
			cp.saveX = m.x;
			cp.saveY = m.y;
			cp.saveE = { left: parseFloat($(this).css("left")), top: parseFloat($(this).css("top"))};
			ColorPicker.div.addClass("cp-moving");
		}
	});
	colorPalleteContainer.append(colorPicker);

	var colorPallete = ColorPicker.colorPallete = $("<div class='cp-palette'></div>");
	//colorPallete.attr("style", "--color: #0015ff;");
	colorPalleteContainer.append(colorPallete);

	var colorChooserContainer = ColorPicker.colorChooserContainer = $("<div class='cp-chooser-container'></div>");
	section.append(colorChooserContainer);
	var colorChooserPicker = ColorPicker.colorChooserPicker = $("<div class='cp-picker'></div>");
	colorChooserPicker.on("mousedown touchstart", function(event){
		var cp = $(window).data("colorPicker");		
		if(cp.move != "hue") {
			cp.move = "hue";
			cp.saveX = getPositionFromEvent(event).x;
			cp.saveE = { left: parseFloat($(this).css("left"))};
			ColorPicker.div.addClass("cp-moving");
		}
	});
	colorChooserContainer.append(colorChooserPicker);
	var colorChooserHue = $("<div class='cp-hue cp-bar'></div>");
	colorChooserContainer.append(colorChooserHue);

	this.update();

	$(window).data("colorPicker", this);
	$("body").append(div);
	$("body").on('click', function (e) {
		var cp = $(window).data("colorPicker");
		if (cp.canHide && $(e.target).closest(ColorPicker.div).length === 0 && ColorPicker.div.is(":visible")) {
			cp.hide();
		}
	});
	$(window).on("resize scroll", function(){ $(this).data("colorPicker").position(); });
	$(window).on("mouseup touchend", function() {
		var cp = $(window).data("colorPicker");
		cp.move = null;
		ColorPicker.div.removeClass("cp-moving");
	});
	$(window).on("mousemove touchmove", function(event){
		var cp = $(window).data("colorPicker");

		var mouse = getPositionFromEvent(event);
		var x = cp.mouseX = mouse.x;
		var y = cp.mouseY = mouse.y;
		var sx = cp.saveX;
		var sy = cp.saveY;

		if(cp.move == "hue") {
			var l = cp.saveE.left + x - sx + (ColorPicker.colorChooserPicker.outerWidth()/2);
			var pW = ColorPicker.colorChooserContainer.outerWidth();
			cp.color.h = (l/parseFloat(pW)*360);	
			if (cp.color.h < 0) cp.color.h = 0;
			if (cp.color.h > 360) cp.color.h = 360;		

			cp.update();
		}
		else if(cp.move == "picker") {
			var l = cp.saveE.left + x - sx + ColorPicker.colorPicker.outerWidth()/2;
			var t = cp.saveE.top + y - sy + ColorPicker.colorPicker.outerHeight()/2;

			var pW = 0;ColorPicker.colorPicker.outerWidth();
			var pH = 0;ColorPicker.colorPicker.outerHeight();
			var w = ((ColorPicker.colorPalleteContainer.outerWidth() - pW / 2)/100);
			var h = ((ColorPicker.colorPalleteContainer.outerHeight() - pH / 2)/100);
			cp.pallete = [
				l / w,
				t / h
			];

			if(l < 0 - pW / 2) { l = 0 - pW / 2; }
			if(t < 0 - pH / 2) { t = 0 - pH / 2; }
			if(cp.pallete[0] > 100) { l = (ColorPicker.colorPalleteContainer.outerWidth() - pW / 2); }
			if(cp.pallete[1] > 100) { t = (ColorPicker.colorPalleteContainer.outerHeight() - pH / 2); }
			cp.pallete = [l / w, t / h];
			if(cp.pallete[0] < 0) cp.pallete[0] = 0;
			if(cp.pallete[1] < 0) cp.pallete[1] = 0;

			cp.color.s = cp.pallete[0];			//cp.color.s*((cp.colorPalleteContainer.outerWidth() - cp.colorPicker.outerWidth() / 2)/100)
			cp.color.v = 100 - cp.pallete[1];	//(100 - cp.color.v) * ((cp.colorPalleteContainer.outerHeight() - cp.colorPicker.outerHeight() / 2)/100)
			//20 = 100 - 80
			cp.update();

			//cp.colorPicker.css("left", l);
			//cp.colorPicker.css("top", t);
		}
		//$(this).data("colorPicker").
	});
}
/////////ColorPicker/////////////

function hsvToRgb(h, s, v) {
	h = (h / 360) * 6;
    s /= 100;
    v /= 100;

    const i = Math.floor(h);

    const f = h - i;
    const p = v * (1 - s);
    const q = v * (1 - f * s);
    const t = v * (1 - (1 - f) * s);

    const mod = i % 6;
    const r = [v, q, p, p, t, v][mod];
    const g = [t, v, v, q, p, p][mod];
    const b = [p, p, t, v, v, q][mod];

    return [
        r * 255,
        g * 255,
        b * 255
    ];
}

function rgbToHex(r, g, b) {
	return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).split('.')[0];
}

function rgb2hsv(r, g, b) {
    let rabs, gabs, babs, rr, gg, bb, h, s, v, diff, diffc, percentRoundFn;
    rabs = r / 255;
    gabs = g / 255;
    babs = b / 255;
    v = Math.max(rabs, gabs, babs),
    diff = v - Math.min(rabs, gabs, babs);
    diffc = c => (v - c) / 6 / diff + 1 / 2;
    percentRoundFn = num => Math.round(num * 100) / 100;
    if (diff == 0) {
        h = s = 0;
    } else {
        s = diff / v;
        rr = diffc(rabs);
        gg = diffc(gabs);
        bb = diffc(babs);

        if (rabs === v) {
            h = bb - gg;
        } else if (gabs === v) {
            h = (1 / 3) + rr - bb;
        } else if (babs === v) {
            h = (2 / 3) + gg - rr;
        }
        if (h < 0) {
            h += 1;
        }else if (h > 1) {
            h -= 1;
        }
    }
    return {
        h: Math.round(h * 360),
        s: percentRoundFn(s * 100),
        v: percentRoundFn(v * 100)
    };
}

function getPositionFromEvent(e) {
	var x,y;
	if(e.type == 'touchstart' || e.type == 'touchmove' || e.type == 'touchend' || e.type == 'touchcancel'){
        var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
        x = touch.clientX;//touch.pageX;
        y = touch.clientY;//touch.pageY;
    } else if (e.type == 'click' || e.type == 'mousedown' || e.type == 'mouseup' || e.type == 'mousemove' || e.type == 'mouseover'|| e.type=='mouseout' || e.type=='mouseenter' || e.type=='mouseleave') {
        x = e.pageX;//e.clientX;
        y = e.pageY;//e.clientY;
    }

	return {x, y};
}

function makeid(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
   }
   return result;
}

var PIXEL_RATIO = (function () {
    var ctx = document.createElement("canvas").getContext("2d"),
        dpr = window.devicePixelRatio || 1,
        bsr = ctx.webkitBackingStorePixelRatio ||
              ctx.mozBackingStorePixelRatio ||
              ctx.msBackingStorePixelRatio ||
              ctx.oBackingStorePixelRatio ||
              ctx.backingStorePixelRatio || 1;

    return dpr / bsr;
})();
function GET_PIXEL_RATIO(ctx) {
    var dpr = window.devicePixelRatio || 1,
        bsr = ctx.webkitBackingStorePixelRatio ||
              ctx.mozBackingStorePixelRatio ||
              ctx.msBackingStorePixelRatio ||
              ctx.oBackingStorePixelRatio ||
              ctx.backingStorePixelRatio || 1;

    return dpr / bsr;
};
var TO_RADIANS = Math.PI/180; 
function createHiDPICanvas(w, h, ratio, update) {
	if (!ratio || ratio == null) { ratio = PIXEL_RATIO; }
	this.ratio = ratio;
	var can = document.createElement("canvas");
	this.can = can;
	can.id = "canvas";
	$(can).attr("style", "image-rendering: optimizeSpeed; image-rendering: -moz-crisp-edges;image-rendering: -webkit-optimize-contrast;image-rendering: -o-crisp-edges;image-rendering: crisp-edges;")
	can.width = w * ratio;
	can.height = h * ratio;
	//console.log(can.width);
	//console.log(ratio);
	can.style.width = w + "px";
	can.style.height = h + "px";
	can.getContext("2d").webkitImageSmoothingEnabled = false;
	can.getContext("2d").mozImageSmoothingEnabled = false;
	can.getContext("2d").imageSmoothingEnabled = false;
	can.getContext("2d").setTransform(ratio, 0, 0, ratio, 0, 0);
	//var canObj = this;
	var canvas = can;
	$(can).data("update", update);
	can.addEventListener('mousemove', function(evt) {
			var mousePos = getMousePos(canvas, evt);
			$(this).data("mousex", mousePos.x);
			$(this).data("mousey", mousePos.y);
			var u = $(this).data("update");
			u();
		}, false);
	//canObj.touchx = 0;
	//canObj.touchy = 0;
	//canObj.touch = false;
	/*
	$(document).on('touchstart', '#canvas', function(e) {
		$(this).data("touchx", e.originalEvent.touches[0].pageX);
		$(this).data("touchy", e.originalEvent.touches[0].pageY);
		$(this).data("touch", true);
		return false;
	});
	$(document).on('touchmove', '#canvas', function(e) {
		$(this).data("touchx", e.originalEvent.touches[0].pageX);
		$(this).data("touchy", e.originalEvent.touches[0].pageY);
		$(this).data("touch", true);
		return false;
	});
	$(document).on('touchend touchcancel', function() {
		$(this).data("touch", false);
		return false;
	});*/
	return can;
}

function getMousePos(canvas, evt) {
    var rect = canvas.getBoundingClientRect();
    return {
        x: evt.clientX - rect.left,
        y: evt.clientY - rect.top
    };
}


(function ($) {
	$.fn.getHiddenDimensions = function (includeMargin) {
		var $item = this,
		props = { position: 'absolute', visibility: 'hidden', display: 'block' },
		dim = { width: 0, height: 0, innerWidth: 0, innerHeight: 0, outerWidth: 0, outerHeight: 0 },
		$hiddenParents = $item.parents().andSelf().not(':visible'),
		includeMargin = (includeMargin == null) ? false : includeMargin;
	
		var oldProps = [];
		$hiddenParents.each(function () {
			var old = {};
	
			for (var name in props) {
				old[name] = this.style[name];
				this.style[name] = props[name];
			}
	
			oldProps.push(old);
		});
	
		dim.width = $item.width();
		dim.outerWidth = $item.outerWidth(includeMargin);
		dim.innerWidth = $item.innerWidth();
		dim.height = $item.height();
		dim.innerHeight = $item.innerHeight();
		dim.outerHeight = $item.outerHeight(includeMargin);
	
		$hiddenParents.each(function (i) {
			var old = oldProps[i];
			for (var name in props) {
				this.style[name] = old[name];
			}
		});
	
		return dim;
	}
}(jQuery));