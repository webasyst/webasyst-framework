/**
 * {literal}
 */
if (!lang_strings) {
    var lang_strings = {
        'edit_link': 'edit link',
        'field_title': 'DoubleClick to edit',
        'save_link': 'save'
    }
}
Printform = {
    edit: function (node) {
        if (node.edited != 'edited') {
            node.edited = 'edited';
            var value = node.innerHTML.replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ');
            var clean_value = value.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
            node.innerHTML = '';
            var inputTag = window.document.createElement('input');
            inputTag.type = "text";
            inputTag.value = clean_value;
            inputTag.className = 'text';
            inputTag.defaultValue = clean_value;
            inputTag.placeholder =  clean_value;
            inputTag.size = 2 + clean_value.length;
            node.appendChild(inputTag);

            var saveTag = window.document.createElement('input');
            saveTag.type = "button";
            saveTag.value = lang_strings['save_link'];
            saveTag.onclick = function () {
                Printform.save(node);
                return false;
            };
            node.appendChild(saveTag);

            var printTag = window.document.createElement('span');
            printTag.className = "printable";
            printTag.innerHTML = value;

            node.appendChild(printTag);

            inputTag.focus();
        }
    },
    editAll: function (class_name) {
        var els = document.getElementsByTagName('*');
        var pattern = new RegExp("(^|\\s)" + class_name + "(\\s|$)");
        for (var i = 0; els[i]; i++) {
            if (pattern.test(els[i].className)) {
                this.edit(els[i]);
            }
        }
    },
    save: function (node) {
        if (node.edited == 'edited') {
            node.edited = '';
            node.innerHTML = node.firstChild.value.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\s+/g, ' ');
            node.style.backgroundColor = '';
        }

    },
    cancel: function (node) {
        if (node.edited == 'edited') {
            node.edited = '';
            node.innerHTML = node.firstChild.defaultValue.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\s+/g, ' ');
            node.style.backgroundColor = '';
        }

    },
    saveAll: function (class_name) {
        var els = document.getElementsByTagName('*');
        var elsLen = els.length;
        var pattern = new RegExp("(^|\\s)" + class_name + "(\\s|$)");
        for (var i = 0; i < elsLen; i++) {
            if (els[i] && els[i].className && pattern.test(els[i].className)) {
                this.save(els[i]);
            }
        }
    },
    init: function (class_name) {
        var els = document.getElementsByTagName('*');
        var elsLen = els.length;
        var pattern = new RegExp("(^|\\s)" + class_name + "(\\s|$)");
        var founded = 0;
        for (var i = 0; i < elsLen; i++) {
            if (pattern.test(els[i].className)) {
                founded++;
                els[i].ondblclick = function () {
                    this.style.backgroundColor = '';
                    Printform.edit(this)
                };
                els[i].onmouseover = function () {
                    this.style.backgroundColor = '#FFC';
                };
                els[i].onmouseout = function () {
                    this.style.backgroundColor = '';
                };
                els[i].title = (els[i].title ? els[i].title + ' ' : '') + lang_strings['field_title'];
                els[i].onkeydown = function (e) {
                    var code = null;
                    try {
                        if (window.event) {
                            code = window.event.keyCode;
                        } else if (e.which) {
                            code = e.which;
                        } else if (e.keyCode) {
                            code = e.keyCode;
                        }
                    } catch (e) {
                        alert(e.message);
                    }
                    if (code == 13) {
                        Printform.save(this);
                    } else if (code == 27) {
                        Printform.cancel(this);
                    }
                    try {
                        var input = this.firstChild;
                        var length = input.value.length;
                        if (length < 5) {
                            input.size = 7;
                        } else if (length < 80) {
                            input.size = 2 + length;
                        } else {
                            input.size = 82;
                        }
                    } catch (e) {
                    }

                }
            }
        }
        if (founded) {
            var printButton = window.document.getElementById('print_button');
            if (printButton) {
                var old_function = printButton.onclick;
                printButton.onclick = function () {
                    Printform.saveAll(class_name);
                    old_function();
                };
                var linkTag = window.document.createElement('a');
                linkTag.href = page_url || '' + '#edit';
                linkTag.innerHTML = lang_strings['edit_link'];
                linkTag.style.display = 'inline';
                linkTag.style.margin = '60px 3px';
                linkTag.disabled = true;
                linkTag.onclick = function () {
                    Printform.editAll(class_name);
                    return false;
                };

                printButton.parentNode.appendChild(linkTag);
            }

            window.document.body.onbeforeprint = function () {
                Printform.saveAll(class_name);
            }
        }
    }
};

/**
 * {/literal}
 */