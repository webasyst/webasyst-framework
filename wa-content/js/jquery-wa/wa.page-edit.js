var h = $("div.wa-page-editor").height() - $("div.wa-page-editor .wa-gray-toolbar").height() - 50;
if (h < 300) {
    h = 300;
}
var editorKey = false;
var editorKeyCallback = function (press) {
    if (press) {
        return function (e) {
            if (!$('#wa-page-button').length) {
                return;
            }
            if (e.ctrlKey && e.which == 115 && !editor_key) {
                $('#wa-page-button').click();
                e.preventDefault();
            }
        }
    } else {
        return function (e) {
            editor_key = false;
            if (!$('#wa-page-button').length) {
                return;
            }
            if (e.ctrlKey && e.which == 83) {
                editor_key = true;
                $('#wa-page-button').click();
                e.preventDefault();
            }
            if (e.metaKey) {
                return;
            }
            if ((e.which < 33 || e.which > 40) &&
                (e.which > 27 || e.which == 8 || e.which == 13) &&
                (e.which < 112 || e.which > 124) &&
                (!e.ctrlKey || e.which != 67)
                ) {
                $('#wa-page-button').removeClass('green').addClass('yellow');
            }
        }
    }
}

var codemirrorEditor = CodeMirror.fromTextArea(document.getElementById("wa-page-content"), {
    mode: "text/html",
    tabMode: "indent",
    height: "dynamic",
    lineWrapping: true,
    onKeyEvent: function (editor, e) {
        var event = jQuery.Event(e);
        if (event.type == 'keydown') {
            editorKeyCallback()(e);
        } else if (event.type = 'keypress') {
            editorKeyCallback(true)(e);
        }
    }
});

$(".CodeMirror-scroll").css('min-height', h + 'px');

h -= 55;
if ($("#wa-app").height() < $("#wa").height() - $("#wa-header").height()) {
    h += $("#wa").height() - $("#wa-header").height() - $("#wa-app").height();
}
if (h < 300) {
    h = 300;
}

// init elrte editor
elRTE.prototype.beforeSave = function () {};
elRTE.prototype.options.toolbars.waPageToolbar = ['wa_style', 'alignment', 'colors', 'format', 'indent', 'lists', 'wa_image', 'wa_links', 'wa_elements', 'wa_tables', 'direction'];

$("#wa-page-content").elrte({
    height: h,
    cssfiles: [wa_url + "wa-content/css/wa/wa-1.0.css"],
    toolbar: 'waPageToolbar',
    lang: wa_lang,
    wa_image_upload: '?module=pages&action=uploadimage',
    wa_image_upload_path: wa_upload_url,
    width: "100%"
});

var f = $("#wa-page-content").elrte()[0].elrte.filter.source;
$("#wa-page-content").elrte()[0].elrte.filter.source = function (html) {
    var html = f.call($("#wa-page-content").elrte()[0].elrte.filter, html);
    html = html.replace(/%7B\$wa_url%7D/, '{$wa_url}');
    html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
        var i = full.indexOf("</script", offset + match.length);
        var j = full.indexOf('<script', offset + match.length);
        if (i == -1 || (j != -1 && j < i)) {
            match = match.replace(/&gt;/g, '>');
            match = match.replace(/&lt;/g, '<');
            match = match.replace(/&amp;/g, '&');
            match = match.replace(/&quot;/g, '"');
        }
        return match;
    });
    return html;
};
$('.el-rte iframe').contents()
    .keydown(editorKeyCallback())
    .keypress(editorKeyCallback(true))
    .keyup(function(e) {
        //all dialogs should be closed when Escape is pressed
        if (e.keyCode == 27) {
            jQuery(".dialog:visible").trigger('esc');
        }
    });
$('.el-rte .toolbar li').click(function () {
    $('#wa-page-button').removeClass('green').addClass('yellow');
});

// bind click handlers to buttons
$("#wysiwyg").click(function () {
    $("ul.wa-page-wysiwyg-html-toggle li.selected").removeClass('selected');
    $(this).parent().addClass('selected');
    $("div.CodeMirror-wrap").hide();
    $("#wa-page-content").elrte('val', codemirrorEditor.getValue());
    $('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
        var s = decodeURIComponent($(this).attr('src'));
        $(this).attr('data-src', s);
        $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
    });
    $(".el-rte").show();
    $('.el-rte iframe').contents().find('body').focus();
    return false;
});

$("#html").click(function () {
    $("ul.wa-page-wysiwyg-html-toggle li.selected").removeClass('selected');
    $(this).parent().addClass('selected');
    $('.el-rte iframe').contents().find("img[data-src!='']").each(function () {
        $(this).attr('src', $(this).attr('data-src'));
    });
    codemirrorEditor.setValue($("#wa-page-content").elrte('val'));
    $(".el-rte").hide();
    $("div.CodeMirror-wrap").show();
    return false;
});

// show active editor
if (true || $.storage.get('site/editor') == 'wysiwyg') {
    $("ul.wa-page-wysiwyg-html-toggle li.selected").removeClass('selected');
    $("#wysiwyg").parent().addClass('selected');
    $('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
        var s = decodeURIComponent($(this).attr('src'));
        $(this).attr('data-src', s);
        $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
    });
    $("div.CodeMirror-wrap").hide();
} else {
    $(".el-rte").hide();
}

$("div.wa-page-app-url input[type=text]").keyup(function () {
    $("div.wa-page-app-url span.wa-page-url-part").html($(this).val());
});

var iButtonInit = function () {
    $("#wa-page-v").iButton({
        labelOn: "",
        labelOff: "",
        classContainer: 'ibutton-container mini'
    });
};
if ($("#wa-page-settings").is(":visible")) {
    setTimeout(iButtonInit, 200);
} else {
    $("#wa-page-settings-toggle").one('click', function () {
        setTimeout(iButtonInit, 100);
    });
}
var status_check = function(item){
    if ($(item).is(':checked')) {
        $('#wa-page-v-open-label').addClass('wa-page-gray');
        $('#wa-page-v-private-label').removeClass('wa-page-gray');
    }
    else {
        $('#wa-page-v-open-label').removeClass('wa-page-gray');
        $('#wa-page-v-private-label').addClass('wa-page-gray');
    }
};
status_check($('#wa-page-v'));
$('#wa-page-v').change(function(){
    $('#wa-page-button').removeClass('green').addClass('yellow');
    status_check(this);
});

$('#wa-page-settings-toggle').click(function(){
    $('#wa-page-settings').toggle();
    return false;
});

$('#wa-page-url-edit-link').click(function (){
    $('#wa-page-url-editable').hide();
    $('#wa-page-url-edit').show().focus();
    return false;
});

$("#wa-pages").sortable({
    distance: 5,
    helper: 'clone',
    items: 'li',
    opacity: 0.75,
    tolerance: 'pointer',
    stop: function (event, ui) {
        var li = $(ui.item);
        var id = li.attr('id').replace(/page-/, '');
        var pos = li.prevAll('li').size() + 1;
        $.post("?module=pages&action=sort", { id: id, pos: pos}, function () {
        }, "json");
    }
});


function waPageUpdateTextarea() {
    if ($(".el-rte").length && $(".el-rte").is(':visible')) {
        $('.el-rte iframe').contents().find("img[data-src!='']").each(function () {
            $(this).attr('src', $(this).attr('data-src'));
        });
        $("#wa-page-content").val($("#wa-page-content").elrte('val'));
        $("#wa-page-content").elrte('val', $("#wa-page-content").val());
        $('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
            var s = decodeURIComponent($(this).attr('src'));
            $(this).attr('data-src', s);
            $(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
        });
    } else if (codemirrorEditor) {
        $("#wa-page-content").val(codemirrorEditor.getValue());
    }
}