/**
 * Coding sandbox JS controller.
 */
$(function($) { "use strict";

    // Initialize "persistent" storage
    $.storage = new $.store();

    // Get code from storage, if exists
    var old_code = $.storage.get('devpg/code');
    var old_tmpl = $.storage.get('devpg/tmpl');

    // Callback for Codemirror to ignore Alt+Enter
    var onKeyEvent = function(editor, e) {
        e = new $.Event(e.type, e);
        if (!e || !e.which) {
            return;
        }
        if (e.which == 13 && e.altKey) {
            return true; // prevents codemirror default action
        }
    };

    // Initialize editors
    var editorPhp = CodeMirror($('#php-editor-wrapper')[0], {
        value: old_code || $('#default-php-code').text(),
        mode: 'text/x-php',
        onKeyEvent: onKeyEvent,
        indentWithTabs: true,
        matchBrackets: true,
        lineWrapping: true,
        lineNumbers: true,
        enterMode: "keep",
        tabMode: "shift",
        indentUnit: 4
    });
    var editorSmarty = CodeMirror($('#smarty-editor-wrapper')[0], {
        value: old_tmpl || $('#default-smarty-code').text(),
        onKeyEvent: onKeyEvent,
        mode: "text/x-smarty",
        lineWrapping: true,
        tabMode: "indent"
    });
    $('#d-smarty').hide();

    /** Helper to set almost-to-the-bottom height of the entire coding sandbox */
    var setSandboxHeight = function() {
        var current_sandbox_height = $('#d-sandbox').height();
        var max_sandbox_height = 0.89 * ( $(window).height() - $('#wa-header').height() );

        //if ( current_sandbox_height < max_sandbox_height) {
            $('#d-sandbox').height( max_sandbox_height );
            setPHPandSmartyHeights();
        //}
    };

    /** Helper to set height of PHP and Smarty editing areas to fill the entire #d-sandbox */
    var setPHPandSmartyHeights = function() {

        var available_editor_height = $('#d-sandbox').height() - $('#d-compile-toolbar').height() - 60;

        // Result column
        var result_wrapper = $('#result-wrapper');
        result_wrapper.height(available_editor_height - result_wrapper.parent().find('.d-header').height());

        // Editors column
        if ($('#d-smarty-mode-enabled').is(':checked')) {
            $('#d-smarty').show();
            $('#d-php').height( 0.66 * available_editor_height );
            $('#d-php .d-editor').height( 0.66 * available_editor_height - $('#d-php .d-header').height() );
            $('#d-smarty').height( 0.33 * available_editor_height );
            $('#d-smarty .d-editor').height( 0.33 * available_editor_height - $('#d-smarty .d-header').height() );
            $('#smarty-editor-wrapper .CodeMirror .CodeMirror-scroll').height($('#d-smarty .d-editor').height());
            editorSmarty.refresh();
        } else {
            $('#d-smarty').hide();
            $('#d-php').height( 1 * available_editor_height );
            $('#d-php .d-editor').height( 1 * available_editor_height - $('#d-php .d-header').height() );
        }
        $('#php-editor-wrapper .CodeMirror .CodeMirror-scroll').height($('#d-php .d-editor').height());
        editorPhp.refresh();
    };

    // Change editor height on window resize
    $(window).resize(function() {
        setSandboxHeight();
    }).resize();

    // Checkbox to toggle smarty editor
    $('#d-smarty-mode-enabled').change(function() {
        setPHPandSmartyHeights();
    });
    if (old_tmpl) {
        $('#d-smarty-mode-enabled').attr('checked', true);
        setPHPandSmartyHeights();
    }

    // Set up AJAX to never use cache
    $.ajaxSetup({
        cache: false
    });

    // submit button
    $('#send').click(function() {
        var result_wrapper = $('#result-wrapper');
        result_wrapper.parent().find('.d-header h2').append('<i class="icon16 loading"></i>');

        var code = editorPhp.getValue();
        var tmpl = '';
        if ($('#d-smarty-mode-enabled').is(':checked')) {
            tmpl = editorSmarty.getValue();
        }

        $.storage.set('devpg/code', code);
        $.storage.set('devpg/tmpl', tmpl);

        $.post('?action=exec', { code: code, tmpl: tmpl }, function(data) {
            result_wrapper.html('<h2>'+$_('PHP')+'</h2><pre>'+data); // closing </pre> is added by PHP code
            result_wrapper.parent().find('.d-header .loading').remove();
        });

        return false;
    });

    // Run on Alt + Enter
    $(document).keyup(function(e) {
        if (!e || !e.which) {
            return;
        }
        if (e.which == 13 && e.altKey) {
            $('#send').click();
            return false;
        }
    });

    // Snippets list
    var snippets = $.storage.get('devpg/snippets') || [];
    if (!(snippets instanceof Array)) {
        snippets = [];
    }
    var cleanSnippets = function() {
        for (var i = 0; i < snippets.length; i++) {
            if (!snippets[i]) {
                snippets.splice(i, 1);
                i--;
            }
        }
        return snippets;
    };
    var rebuildSnippets = function(name) {
        cleanSnippets();
        var list = $('#snippets-list').empty().append(
            $('<option value="none"></option>').text($_('saved templates'))
        );
        if (snippets.length) {
            for(var i = 0; i < snippets.length; i++) {
                if (snippets[i]) {
                    list.append(
                        $('<option value="'+i+'"'+((name && name === snippets[i].name) ? ' selected' : '')+'></option>').text(snippets[i].name)
                    );
                }
            }
        }
        $.storage.set('devpg/snippets', snippets);

        if (name) {
            $('#d-delete-template').show();
        } else {
            $('#d-delete-template').hide();
        }
    };
    rebuildSnippets();

    // Button to save snippet as ...
    $('#save-snippet').click(function() {
        // suggest most recently saved snippet name
        var last_snippet_name = (new Date()).toLocaleDateString();
        var last_save = 0;
        for(var i = 0; i < snippets.length; i++) {
            if ((snippets[i].last_load || snippets[i].update_datetime) > last_save) {
                last_snippet_name = snippets[i].name;
                last_save = snippets[i].last_load || snippets[i].update_datetime;
            }
        }

        // Ask for snippet name
        var name = prompt($_('Enter template name (both PHP and Smarty editors will be saved):'), last_snippet_name);
        if (name) {
            var new_snippet = {
                name: name,
                code: editorPhp.getValue(),
                tmpl: editorSmarty.getValue(),
                update_datetime: (new Date()).getTime(),
                last_load: (new Date()).getTime()
            };

            // When snippet with this name already exists then delete it
            for(var i = 0; i < snippets.length; i++) {
                if (snippets[i].name === name) {
                    delete snippets[i];
                }
            }

            snippets.push(new_snippet);
            rebuildSnippets(name);
        }
    });

    // Load snippet
    $('#snippets-list').change(function() {
        var i = $(this).val();
        if (i === 'none' || !snippets[i]) {
            $('#d-delete-template').hide();
            return;
        }
        editorPhp.setValue(snippets[i].code);
        editorSmarty.setValue(snippets[i].tmpl);

        snippets[i].last_load = (new Date()).getTime();
        rebuildSnippets(snippets[i].name);

        if ($.trim(snippets[i].tmpl)) {
            $('#d-smarty-mode-enabled').attr('checked', true);
        } else {
            $('#d-smarty-mode-enabled').attr('checked', false);
        }
        setPHPandSmartyHeights();
    });

    // Delete snippet
    $('#d-delete-template').click(function() {
        var i = $('#snippets-list').val();
        if (i === 'none' || !snippets[i]) {
            return;
        }

        if (confirm($_('Permanently delete template “%s”?').replace('%s', snippets[i].name))) {
            delete snippets[i];
            rebuildSnippets();
        }
    });
});
