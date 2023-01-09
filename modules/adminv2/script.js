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
    s -= $(".menu.sub").outerHeight();
    s -= $(".admin > .topmenu").outerHeight();
    s -= parseInt($(".admin").css("padding-top"));
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
    $.getJSON(_router_url+"adminv2/system/ftp/?__type=ajax", {saveFile: this.fileSave.file, text: text}, function(data){
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
    $.getJSON(_router_url+"adminv2/system/ftp/?__type=ajax", {list: folder}, function(data){
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
    $.getJSON(_router_url+"adminv2/system/ftp/?__type=ajax", {file: file, name: name}, function(data){
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