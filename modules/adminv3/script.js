var pageManager = function (selected, separator) {
    this.contentUrl = _router_url + "adminv3-load/";
    this.title = document.title;
    this.title_separator = separator == null || separator == ""? "-": separator;
    this.timeout = 150;
    this.lastButtonSubmit = null;
    this.opening_li = [selected];
    this.selected = selected;
    this.hasMenu = selected;
    this.currentPageUrl = "";
    this.animation = {
        margin: 30
    }
    this.windows = [];

    var self = this;

    $("body").on("click", ".content-page form input[type=submit], .content-page form button.submit, .content-page form button[type=submit]", function(e){        
        e.preventDefault();
        manager.submitForm($(this));
    });

    $("body").on("click", ".content-page a[href], .admin-breadcrumb a[href]", function(e){        
        var href = $(this).attr("href");
        if(href == "#" || $(this).attr("target") == "_blank") return;

        var no_animation = $(this).data("animation") === false;

        btnLoading($(this), true);
        e.preventDefault();        

        self.loadPage($(this).attr("href"), this, true, no_animation);
    });
};

pageManager.prototype.clearWindows = function(){
    for(var key in this.windows) {
        var win = this.windows[key];
        win.background.remove();
        win.dialog.remove();
    }
    this.windows = [];
}

pageManager.prototype.animateOut = function(callback){
    var callback = callback;
    $("#main-page").animate({
        opacity: 0,
        "margin-left": (this.animation.margin * -1)+"px"
    }, this.timeout, function () {
        callback();
    });
}

pageManager.prototype.getConfig = function(){
    var config = Cookies.get("admin-settings");    
    var configJson;
    if(config == undefined) configJson = {};
    else configJson = JSON.parse(config);
    
    if(configJson.cards == undefined) {
        configJson.cards = {};
    }
    return configJson;
};
pageManager.prototype.saveConfig = function(config){
    Cookies.set("admin-settings", JSON.stringify(config));
};
pageManager.prototype.getCardState = function(id, state) {
    var config = this.getConfig();
    id = this.getPageKey() + "-" + id;
    if(config.cards[id] == undefined) return [];
    return config.cards[id][state];
};
pageManager.prototype.setCardState = function(id, state, value){
    var config = this.getConfig();
    id = this.getPageKey() + "-" + id;
    if(config.cards[id] == undefined) config.cards[id] = {};
    config.cards[id][state] = value;
    this.saveConfig(config);
}
pageManager.prototype.getPageKey = function(){
    var data = window.location.href.replace(_router_url+"adminv3/","").split("/");
    if(data.length == 0) return "root";
    if(data.length == 1) return data[0];
    return data[0]+"-"+data[1];
};
pageManager.prototype.makeKey = function(id) {
    if(id == undefined) return undefined;
    return this.getPageKey() + "-" + id;
}
pageManager.prototype.onPageLoad = function(){
    var self = this;
    var config = this.getConfig();

    tinyMCE.remove();
    loadeditor();
    replaceInputes();

    $(".content-page .card").each(function(){
        var id = $(this).data("id");
        if(id == undefined) return;

        var card = $(this);
        var collapsable = $(this).data("collapsable") == 1;

        var titleElement = $(this).find(".card-title");
        var title = titleElement.text();

        //if(config.cards[id] == undefined) config.cards[id] = {};
        if(/*config.cards[id].closed*/ self.getCardState(id, "closed") === true) {
            card.addClass("closed");
        } 

        var titleHtml = $("<div></div>").addClass("card-title-name").text(title);
        var actionHtml = $("<div></div>").addClass("card-title-action");

        if(collapsable) {
            var collaps = $("<a></a>").attr("href", "#").addClass("card-title-expander");
            collaps.data("id", id);
            collaps.data("manager", self);

            collaps.on("click", function(e){
                e.preventDefault();
                
                var card = $(this).parents(".card");
                var content = card.find(".card-content");
                var isClosed = card.hasClass("closed");
                content.stop();
                $(this).data("manager").setCardState($(this).data("id"), "closed", !isClosed);

                if(isClosed) {                            
                    card.removeClass("closed");
                    content.css("height", "");
                    var h = content.outerHeight(false);
                    card.addClass("opening");
                    var opening_box = [card, content];
                    
                    content.css("height", 0);        
                    content.animate({height: h}, 500, function(){
                        opening_box[0].removeClass("opening");
                        opening_box[0].addClass("open");
                        opening_box[1].css("height", "");
                    });
                }else{
                    card.removeClass("open");
                    card.addClass("closing");
                    var opening_box = [card, content];

                    content.animate({height: 0}, 500, function(){
                        opening_box[0].removeClass("closing");
                        opening_box[0].addClass("closed");                        
                    });
                }
            });

            actionHtml.append(collaps);
        }

        titleElement.html("").append(titleHtml).append(actionHtml);
    });
   /*
    $(".content-page form input[type=submit], .content-page form button.submit").on("click", function(e){        
        e.preventDefault();
        manager.submitForm($(this));
    });

    $(".content-page a[href]").on("click", function(e){
        e.preventDefault();
        console.log($(this), $(this).attr("href"));

        var href = $(this).attr("href");
        if(href == "#") return;
    });    
   */
}

/* Need to make buffer */
pageManager.prototype.notification = function(text, type, timeout){
    if(timeout == undefined) timeout = 3000;

    var self = this;
    var notif = $(".admin-title-nofitication");
    notif.find(".text").html(text);
    notif.find(".notification-progress").css("width", notif.outerWidth());
    notif.removeClass("notification-success").removeClass("notification-error");
    notif.addClass("notification-"+type);
    notif.css("top", notif.outerHeight() * -1);
    notif.css("opacity", 0);
    notif.show();
    notif.find(".notification-progress").animate({width: 0}, timeout);
    notif.animate({
        top: 0,
        opacity: 1
    }, this.timeout, function () {
        setTimeout(function(){self.notificationHide();}, timeout);
    });
}
pageManager.prototype.notificationHide = function(){
    var notif = $(".admin-title-nofitication");
    notif.animate({
        top: notif.outerHeight() * -1,
        opacity: 0
    }, this.timeout, function () {
        notif.hide();
    });
}

function replaceValueInFormData(array, name, value) {
    for(var key in array) {
        var data = array[key];
        if(data.name == name) {
            data.value = value;
            return true;
        }
    }

    return false;
}

pageManager.prototype.submitForm = function(button, callback){ //button as form!
    var self = this;
    var callback = callback;

    button = $(button);
    btnLoading(button, true);
    this.lastButtonSubmit = button;

    var form = button.parents("form");   
    var action = form.attr('action');     
    var url = action.split('adminv3/', 2);        
    var data = form.serializeArray();
    data.push({ name: "_pageLoad", value: url[1] });

    form.find("textarea").each(function(index) { //This will go throught all the tinimce editors and fill the textarea with tinimce content
        if($(this).hasClass("tinimcenocheck")) return;

        var tinimce = tinymce.get($(this).attr("id"));
        if(tinimce != null) {
            replaceValueInFormData(data, $(this).attr("name"), tinimce.getContent());
        }
    });

    //console.log(form, data, action, action.split('adminv3/', 2)[1]);

    $.ajax({
        type: form.attr('method'),
        url: url[0]+"adminv3-load/",
        data: $.param(data),
        success: function(data) {
            //console.log(data);
            btnLoading(manager.lastButtonSubmit, false);
            self.handleRequest(data);
            callback(data);
        }
    });
}

pageManager.prototype.handleRequest = function(data) {    
    if(data.reload) {
        this.reload();
    }

    if(data.state == "success"){
        this.notification(data.text, "success");
        return true;
    }
    else if(data.state == "error"){
        this.notification(data.text, "error");
        return false;
    }
    initializeSpecialComponents();    
    return false;
}

pageManager.prototype.reload = function(){
    this.loadPage(this.currentPageUrl, null, true, true);
}

pageManager.prototype.loadPage = function (url, a, force, no_animation) {
    this.clearWindows();
    
    if(no_animation == undefined) no_animation = false;
    if(force == undefined) force = false;
    if(url.includes("/adminv3/")) {
        var _url = url.split('/adminv3/', 2);
        url = _url[1];
    }
    this.currentPageUrl = url;
    console.log("Loading page", url);
    if(force !== true && window.location.href == _router_url+"adminv3/" + url) return false;

    window.history.pushState("", "", _router_url+"adminv3/" + url);
    this.toggleLeftMenu(true);

    var li = null;
    if(this.selected != null && a != null) this.selected.removeClass("selected");
    if(a != null) { 
        var ul = $(a).closest("ul");
        if(!ul.hasClass("card-menu")){
            li = $(a).closest("li"); 
            li.addClass("selected"); 
            this.selected = li; 
        }
    }
    //console.log(li, li != null? li.hasClass("has-submenu"): null, this.hasMenu);
    if(li != null && li.hasClass("has-submenu")){ 
        //console.log(this.hasMenu.attr("class"), li.attr("class"));
        if(this.hasMenu.attr("class") != li.attr("class")) {
            //this.hasMenu.removeClass("open").addClass("closed"); 
            openMenu(this.hasMenu.find(".opener"), true);
            //this.hasMenu.removeClass("closed").addClass("open");        
            this.hasMenu = li; 
            openMenu(this.hasMenu.find(".opener"), true);       
        } 
    }

    var self = this;
    var no_animation = no_animation;

    var query = url.split('?');
    if(query.length > 1) query[1] = "?" + query[1]; else query[1] = "";
    
        $("#main-page").animate({opacity: 0.5}, 200, function(){
            $.post(self.contentUrl + query[1], { _pageLoad: query[0] }, function (data) {
                //console.log(data);
                var data = data;
                var fun = function(){
                    $("#main-page").html(data.content);            
                    start();
                    self.onPageLoad();
                            
                    $("#main-page").css("margin-left", "0px");
                    if(!no_animation) {
                        $("#main-page").css("margin-right", self.animation.margin + "px");
                        $("#main-page").animate({
                            opacity: 1,
                            "margin-right": "0px"
                        }, self.timeout, function () {
                            
                        });
                    }
                }
                if(no_animation) {
                    $("#main-page").animate({opacity: 1}, 200);
                    fun();
                }
                else self.animateOut(fun);
        
                $(".page-title h1").text(data.title);
                $(".page-title .admin-breadcrumb li:last-child a").text(data.title);
                $(".page-title .admin-breadcrumb li.-sub").remove();
        
                for(var key in data.back) {
                    var link = data.back[key];
                    console.log(link.name);
                    var li = $("<li></li>");
                    li.addClass("-sub");
                    var a = $("<a></a>");
                    a.attr("href",  _router_url + "adminv3/"+link.url);
                    a.text(link.name);
                    li.append(a);
                    li.insertBefore($(".page-title .admin-breadcrumb li:last-child"));
                }
        
                document.title = data.title + self.title_separator + self.title;        
            });
        });

    return false;
};

pageManager.prototype.toggleLeftMenu = function(forceClose){
    if(parseInt($(".left-menu").css("z-index")) != 100) return;
    if(forceClose == undefined) forceClose = false;

    var menu = $(".admin-content .left-menu");
    if(menu.is(":visible") || forceClose) {
        menu.stop();
        menu.animate({left: menu.outerWidth() * -1}, 200, function() {
            menu.css("left", '');
            menu.css("display", '');
            $(".left-side .menu-bar").removeClass("open");
        });
    }else{
        menu.stop();
        menu.show();
        menu.css("left", menu.outerWidth() * - 1);
        menu.animate({left: 0}, 200, function() {
            $(".left-side .menu-bar").addClass("open");
        });
    }
}

pageManager.prototype.get = function(url, data, callback){
    var url = url.split('adminv3/', 2); 
    var self = this;

    var callback = callback;
    var _data = data;
    _data["_pageLoad"] = url[1];
    $.post(url[0]+"adminv3-load/", _data, function (data) {
        var isSuccess = self.handleRequest(data);
        callback(data, isSuccess);
    });
};

function openMenu(opener, noClose){
    if(noClose == undefined) noClose = false;

    var opener = $(opener);
    var li = opener.parents("li");    
    var me = li.find("ul.admin-sub-menu");
    console.log(me);
    var isClosed = li.hasClass("closed");

    if(!noClose && manager.hasMenu != null) {
        //console.log(manager.hasMenu.attr("class"), li.attr("class"));
        if(manager.hasMenu.attr("class") != li.attr("class")) {
            if(!isClosed) {        
                manager.hasMenu = null;
            }else{
                openMenu(manager.hasMenu.find(".opener"));
                manager.hasMenu = li;
            }
        }
    }

    me.stop();

    if(isClosed) {        
        li.removeClass("closed");
        me.css("height", "");
        var h = me.outerHeight(false);
        li.addClass("opening");
        manager.opening_li = [li, me];
        
        me.css("height", 0);        
        me.animate({height: h}, 500, function(){
            manager.opening_li[0].removeClass("opening");
            manager.opening_li[0].addClass("open");
        });
    }else{
        li.removeClass("open");
        li.addClass("closing");
        manager.closing_li = [li, me];

        me.animate({height: 0}, 500, function(){
            manager.closing_li[0].removeClass("closing");
            manager.closing_li[0].addClass("closed");
        });
    }
}

var TableManager = function(id, url, columns){
    this.id = id;
    this.table = $("#table-"+id);
    this.url = url;
    this.columns = columns;

    this.page = 1;
    this.limit = 13;
    this.total = 0;

    this._filter = {};

    this.reload();
};

TableManager.prototype.filter = function(name, value){
    this._filter[name] = value;
    return this;
}

TableManager.prototype.reload = function(){
    var self = this;

    var f = Object.keys(this._filter).length == 0? null: this._filter;
    manager.get(this.url, {page: this.page, limit: this.limit, filter: f},  function (data) {
        self.table.find("tbody").html(data.content);
    });
};

TableManager.prototype.recalculate = function(){
    this.totalPages = Math.ceil(this.total / this.limit);

    var pag = $("#table-"+this.id+"-paginator");
    pag.find(".total-count").html(this.total);
    pag.find(".page-number").html(this.page);
    pag.find(".page-total").html(this.totalPages);

    if(this.totalPages == 0) {
        this.table.find("tbody").html("<tr><td colspan='"+this.columns.length+"' class='no-data'>No data</td></tr>");
    }

    var paginator = GetPaginatorArray(this.page, this.limit, this.total);
    var pg = pag.find(".paginator");
    pg.html("");
    for(var key in paginator.pages) {
        var p = paginator.pages[key];
        if(p.static) {
            var text = $("<span></span>");
            text.text(p.text);
            pg.append(text);
            continue;
        }
        var btn = $("<a></a>");
        btn.addClass("button button-small");
        btn.text(p.text);
        btn.data("page", p.page);
        btn.attr("href", "#");
        if(p.current) {
            btn.addClass("selected");
        }
        btn.data("manager", this);
        btn.on("click", function(e){
            var m = $(this).data("manager");
            e.preventDefault();
            m.page = $(this).data("page");
            m.reload();
        })
        pg.append(btn);
     }
};

TableManager.prototype.pushInfo = function(data) {
    this.total = data.total;
    this.page = data.page;
    this.recalculate();
};

window.toggle_swipe_default_icon_off = "close";
window.toggle_swipe_default_icon_on = "check";

var FTPFileType = { FILE: 0, FOLDER: 1 };
var FTPButtonAction = function (title, icon, fun, funShow) {
    this.title = title;
    this.icon = icon;
    this.fun = fun;
    this.funShow = funShow;
};
var FTPHeaderItemOrder = { NONE: 0, ASC: 1, DESC: 2 };
var FTPHeaderItem = function (id, title, width, order) {
    this.id = id;
    this.title = title;
    this.width = width;
    if (order === undefined) { order = FTPHeaderItemOrder.NONE; }
    this.order = order;
};
var FTP = function (container) {
    this.container = $(container);
    this.container.addClass("ftp-containter");
    this.selected = [];
    this.control = false;

    this.actions = [
        new FTPButtonAction("New folder", "fas fa-folder-plus", function () { }),
        new FTPButtonAction("New file", "fas fa-file", function () { },),
        new FTPButtonAction("Go up", "fas fa-level-up-alt", function (self) { self.goBack(); }, function (self) { return self.getDir() != "/"; }),
        new FTPButtonAction("Unselect all", "fas fa-ban", function (self) { self.deselectAll(); }, function (self) { return self.selected.length > 0; }),
        new FTPButtonAction("Delete", "fas fa-trash-alt", function () { }, function (self) { return self.selected.length > 0; }),
        new FTPButtonAction("Unzip", "fas fa-file-archive", function () { }, function (self) { return self.selected.length == 1; }),
        new FTPButtonAction("Zip", "fas fa-paperclip", function () { }, function (self) { return self.selected.length > 1; }),
    ];

    this.headers = [
        new FTPHeaderItem("name", "File name", "30%"),
        new FTPHeaderItem("size", "File size", "15%"),
        new FTPHeaderItem("type", "File type", "15%"),
        new FTPHeaderItem("last-modify", "Last modified", "25%"),
        new FTPHeaderItem("permissions", "Permissions", "15%"),
    ];

    this.build();
    var self = this;
    $(window).on("resize", function () { self.reloadAll(); });
    $("body").on("keydown", function (e) {
        if (e.originalEvent.code == "ControlLeft") {
            self.control = true;
        }
    })
    $("body").on("keyup", function (e) {
        if (e.originalEvent.code == "ControlLeft") {
            self.control = false;
        }
    })

    /*
    this.addFileList("tmp", "", FTPFileType.FOLDER, "17.06.2021 14:20", "777");
    for (var i = 0; i < 25; i++) {
        this.addFileList("Name " + i, (12 + i) + "b", FTPFileType.FILE, "17.06.2021 14:20", "777");
    }*/

    this._apiLoadFilesFromFolder(this.addressInput.val());

    //callbacks
    this.fn_onResize = function(w,h){};
    this.fn_onOpenEditor = function(url, name, text){};
};
FTP.prototype.onOpenEditor = function(callback) {
    this.fn_onOpenEditor = callback;
};
FTP.prototype.onResize = function(callback) {
    this.fn_onResize = callback;
};
FTP.prototype.build = function () {
    var c = this.container;
    c.html("");

    var dialog = $("<div class=dialog></div>");
    var title = $("<div class=title></div>");
    title.html("Hello world");
    dialog.append(title);
    var desc = $("<div class=text></div>");
    desc.html("Hello ipsum dolor sit amen<br/>OwO whats this?");
    dialog.append(desc);
    dialog.hide();
    c.append(dialog);

    var menu = $("<ul class='ftp-toolbar'></ul>");
    this.menu = menu;

    for (var key in this.actions) {
        var action = this.actions[key];
        var li = $("<li></li>");
        var icon = $("<i/>");
        icon.addClass(action.icon);

        li.data("fun", action.fun);
        li.data("self", this);
        li.attr("title", action.title);
        li.on("click", function () {
            $(this).data("fun")($(this).data("self"));
        });
        li.append(icon);

        action.container = li;
        if (action.funShow != null && !action.funShow(this)) { li.hide(); }
        menu.append(li);
    }

    c.append(menu);

    var address = $("<div class=address></div>");
    this.address = address;
    var text = $("<span></span>");
    text.html("Address: ");
    address.append(text);
    this.addressInput = $("<input type=text />");
    this.addressInput.val("/");
    this.addressInput.data("self", this);
    this.addressInput.on("keyup", function(e){
        if(e.originalEvent.keyCode == 13) {
            $(this).data("self").setDir($(this).val());
        }
    });
    address.append(this.addressInput);
    var btnGo = $("<button></button>");
    btnGo.html("<i class='fas fa-arrow-alt-circle-right'/>");
    btnGo.data("self", this);
    btnGo.on("click", function(){
        var self = $(this).data("self");
        self.setDir(self.addressInput.val());
    });
    address.append(btnGo);
    c.append(address);

    var fileList = $("<div class=file-list></div>");
    this.fileList = fileList;
    var fileListHeader = $("<div class=header></div>");
    this.fileListHeader = fileListHeader;
    var fileListFiles = $("<div class=list></div>");
    this.fileListFiles = fileListFiles;

    for (var index in this.headers) {
        var header = this.headers[index];

        var head = $("<div></div>");
        head.html(header.title);
        head.css("width", header.width);
        head.data("data", header);

        this.fileListHeader.append(head);
    }

    fileList.append(fileListHeader);
    fileList.append(fileListFiles);
    c.append(fileList);

    var status = $("<div class=status></div>");
    this.status = status;
    status.html("Standby...");
    c.append(status);

    this.recalculateSizes();
};
FTP.prototype.reloadAll = function () {
    this.reloadToolbar();
    this.recalculateSizes();
};
FTP.prototype.reloadToolbar = function () {
    for (var key in this.actions) {
        var action = this.actions[key];
        if (action.funShow != null && !action.funShow(this)) { action.container.hide(); }
        else { action.container.show(); }
    }
};
FTP.prototype.recalculateSizes = function () {
    var s = $(window).height();
    s -= this.menu.outerHeight();
    s -= this.address.outerHeight();
    s -= this.status.outerHeight();
    s -= $(".admin .page-title").outerHeight();
    s -= $(".admin-title").outerHeight();
    s -= 38;
    this.fileList.css("height", s);

    if(this.fn_onResize != undefined)
        this.fn_onResize(this.container.parent().outerWidth(), this.container.outerHeight());
};
FTP.prototype.clearFileList = function () {
    this.fileListFiles.html("");
};
FTP.prototype.addFileList = function (name, size, type, lastModifed, permissions) {
    var row = $("<div class=ftp-row></div>");
    row.data("data", { name, size, type, lastModifed, permissions });
    for (var index in this.headers) {
        var header = this.headers[index];

        var head = $("<div></div>");
        head.css("width", header.width);
        head.data("data", header);

        var val = "";
        if (header.id == "name") { val = name; head.attr("title", name); }
        if (header.id == "size") { val = size; }
        if (header.id == "type") {
            if (type == FTPFileType.FILE) {
                val = "file";
            } else if (type == FTPFileType.FOLDER) {
                val = "folder";
            }
        }
        if (header.id == "last-modify") { val = lastModifed; }
        if (header.id == "permissions") { val = permissions; }

        head.html(val);

        row.append(head);
    }
    row.data("self", this);
    row.on("click", function () {
        var self = $(this).data("self");
        self.selectItem(this);
    });
    row.on("dblclick", function () {
        var self = $(this).data("self");
        self.selectItem(this);
        self.openItem(this);
    });
    this.fileListFiles.append(row);
};
FTP.prototype.goBack = function(){
    var dir = this.getDir().substring(0, this.getDir().length - 1);
    var lastIndex=dir.lastIndexOf("/");
    this.setDir(dir.slice(0,lastIndex+1));
}
FTP.prototype.setDir = function(dir) {
    if(dir == "") dir = "/";
    if(dir.substring(dir.length - 1, dir.length) != "/") dir+="/";

    this.addressInput.val(dir);   
    this.addressInput.blur(); 
    this.reload();
};
FTP.prototype.getDir = function(){
    if(this.addressInput == undefined) return "/";
    return this.addressInput.val();
}
FTP.prototype.reload = function(){
    this._apiLoadFilesFromFolder(this.addressInput.val());
};
FTP.prototype.openItem = function(item){
    item = $(item);
    var data = item.data("data");
    if(data.type == FTPFileType.FOLDER) {
        this.setDir(this.getDir() + data.name + "/");
        return;
    }else{
        //this.fn_onOpenEditor(this.getDir() + data.name, data.name, "text");
        this._apiLoadFileData(data.name);
    }
};
FTP.prototype.selectItem = function (item) {
    if (!this.control) {
        this.fileListFiles.find(">.ftp-row").removeClass("selected");
        this.selected = [];
    }

    if ($(item).hasClass("selected")) {
        $(item).removeClass("selected");
        var arr = [];
        for (var key in this.selected) {
            var sel = this.selected[key];
            if (sel.data("data").name == $(item).data("data").name) continue;
            arr.push(sel);
        }
        this.selected = arr;
    } else {
        $(item).addClass("selected");
        this.selected.push($(item));
    }
    this.reloadAll();
};
FTP.prototype.deselectAll = function () {
    this.fileListFiles.find(">.ftp-row").removeClass("selected");
    this.selected = [];
    this.reloadAll();
}
FTP.prototype.saveFile = function(text, callback){
    var self = this;
    var callback = callback;
    this.status.html("Saving file: " + this.fileSave.file);
    manager.get(_router_url+"adminv3/system/api_ftp_get_files/", {saveFile: this.fileSave.file, text: text},  function (data) {
        if(data.error == "FILE_NOT_EXISTS") {
            messageBox(data.message);
            self.status.html(data.message);            
            return;
        }
        if(data.error == "FILE_CANT_SAVE") {
            messageBox(data.message);
            self.status.html(data.message);            
            return;
        }

        self.status.html("Success saved file: " + self.fileSave.name);        
        self.reloadAll();
        NotificationCreate("", "Success saved file: " + self.fileSave.name, "#", "ok");
        self.fileSave = null;

        callback();
    });
}
FTP.prototype._apiLoadFilesFromFolder = function (folder) {
    var folder = folder;
    var self = this;
    this.selected = [];
    this.status.html("Loading folder: " + folder);
    //api_ftp_get_files
    manager.get(_router_url+"adminv3/system/api_ftp_get_files/", {list: folder},  function (data) {
        if(data.error == "FOLDER_NOT_EXISTS") {
            messageBox(data.message);
            self.status.html(data.message);            
            self.addressInput.val(self.lastDir);
            return;
        }

        self.lastDir = folder;
        self.fileListFiles.html("");
        self.status.html("Success loaded folder: " + folder);
        for(var key in data) {
            var file = data[key];
            self.addFileList(file.name, file.isdir? "": bytesToSize(file.size), file.isdir? FTPFileType.FOLDER: FTPFileType.FILE, file.lastmodify, file.perms);
        }
        
        self.reloadAll();
    });
};
FTP.prototype._apiLoadFileData = function(file) {
    var name = file;
    var file = this.getDir() + file;
    var self = this;

    this.fileSave = {name: name, file: file};

    this.status.html("Loading file: " + file);
    manager.get(_router_url+"adminv3/system/api_ftp_get_file/", {file: file, name: name},  function (data) {
        if(data.error == "FILE_NOT_EXISTS") {
            messageBox(data.message);
            self.status.html(data.message);            
            return;
        }

        self.status.html("Success loaded file: " + name);
        self.fn_onOpenEditor(file, data.name, data.text);
        self.reloadAll();
    });
}