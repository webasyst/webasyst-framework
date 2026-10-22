(function($) {
    "use strict";

    $.site.aiImage = $.site.aiImage || {};

    function EditorAdapter(config) {
        this.locale = config.locale || {};
        this.context = Vue.ref({});
    }

    EditorAdapter.prototype.getVueComponent = function() {
        const that = this;

        return {
            props: ['block_id'],
            // emits: ['change'],
            data() {
                this.locale = that.locale;

                return {
                    context: {}
                };
            },
            computed: {
                has_reference() {
                    return !!(this.context.current_image_url &&
                        (parseInt(this.context.current_file_id) || this.context.current_image_attachable));
                },
            },
            template: `
                <div class="dropdown width-100 site-aiimage-sidebar-dropdown custom-my-8">
                    <button type="button" class="dropdown-toggle button purple width-100 text-ellipsis" style="font-size: 0.875rem;">
                        <div class="flexbox-inline width-100">
                            <svg class="site-aiimage-dropdown-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7.2852 6.69318C6.97949 6.3875 6.48624 6.38085 6.17242 6.67814L0.410199 12.045C-0.0304081 12.4623 -0.0398736 13.1613 0.38933 13.5905C0.818111 14.0192 1.51588 14.0098 1.93295 13.5696L7.30025 7.80791C7.59797 7.49366 7.59127 6.99924 7.2852 6.69318Z" fill="white"/><path d="M2.23132 3.44625C2.26153 3.33953 2.4128 3.33953 2.44301 3.44625L2.84282 4.85865C2.87923 4.98729 2.97977 5.08783 3.10841 5.12424L4.52081 5.52405C4.62753 5.55426 4.62753 5.70553 4.52081 5.73574L3.10841 6.13554C2.97977 6.17195 2.87923 6.27249 2.84282 6.40113L2.44301 7.81353C2.4128 7.92026 2.26153 7.92025 2.23132 7.81353L1.83152 6.40113C1.79511 6.27249 1.69457 6.17195 1.56593 6.13554L0.153528 5.73574C0.0468057 5.70553 0.0468059 5.55426 0.153528 5.52405L1.56593 5.12424C1.69457 5.08783 1.79511 4.98729 1.83152 4.85865L2.23132 3.44625Z" fill="#FF8040"/><path d="M4.37545 0.060339C4.39869 -0.0201131 4.51505 -0.020113 4.53829 0.0603392L4.84583 1.12507C4.87384 1.22205 4.95118 1.29784 5.05013 1.32529L6.13659 1.62668C6.21869 1.64945 6.21869 1.76348 6.13659 1.78626L5.05013 2.08765C4.95118 2.1151 4.87384 2.19089 4.84583 2.28786L4.53829 3.3526C4.51505 3.43305 4.39869 3.43305 4.37545 3.3526L4.06791 2.28786C4.0399 2.19089 3.96256 2.1151 3.86361 2.08765L2.77715 1.78626C2.69505 1.76348 2.69505 1.64945 2.77715 1.62668L3.86361 1.32529C3.96256 1.29784 4.0399 1.22205 4.06791 1.12507L4.37545 0.060339Z" fill="#6CC5FF"/><path d="M7.64059 10.3225C7.66337 10.2421 7.7774 10.2421 7.80017 10.3225L8.10156 11.3873C8.12901 11.4843 8.20481 11.56 8.30178 11.5875L9.36651 11.8889C9.44696 11.9117 9.44696 12.0257 9.36651 12.0485L8.30178 12.3499C8.20481 12.3773 8.12901 12.4531 8.10156 12.5501L7.80017 13.6148C7.7774 13.6953 7.66337 13.6953 7.64059 13.6148L7.3392 12.5501C7.31175 12.4531 7.23596 12.3773 7.13899 12.3499L6.07426 12.0485C5.9938 12.0257 5.9938 11.9117 6.07426 11.8889L7.13899 11.5875C7.23596 11.56 7.31175 11.4843 7.3392 11.3873L7.64059 10.3225Z" fill="#6DFF6D"/><path d="M11.6798 7.97039C11.7086 7.86859 11.8529 7.86859 11.8817 7.97039L12.2631 9.3176C12.2978 9.44031 12.3937 9.5362 12.5164 9.57094L13.8636 9.95229C13.9654 9.9811 13.9654 10.1254 13.8636 10.1542L12.5164 10.5356C12.3937 10.5703 12.2978 10.6662 12.2631 10.7889L11.8817 12.1361C11.8529 12.2379 11.7086 12.2379 11.6798 12.1361L11.2985 10.7889C11.2637 10.6662 11.1678 10.5703 11.0451 10.5356L9.69793 10.1542C9.59613 10.1254 9.59613 9.9811 9.69793 9.95229L11.0451 9.57094C11.1678 9.5362 11.2637 9.44031 11.2985 9.3176L11.6798 7.97039Z" fill="#F5C400"/><path d="M9.38773 0.1538C9.44578 -0.0512668 9.73644 -0.0512664 9.79449 0.1538L10.5627 2.86773C10.6327 3.1149 10.8259 3.30809 11.073 3.37806L13.787 4.14628C13.992 4.20433 13.992 4.49499 13.787 4.55303L11.073 5.32126C10.8259 5.39122 10.6327 5.58441 10.5627 5.83159L9.79449 8.54551C9.73644 8.75058 9.44578 8.75058 9.38773 8.54551L8.61951 5.83159C8.54954 5.58441 8.35636 5.39122 8.10918 5.32126L5.39525 4.55303C5.19019 4.49499 5.19019 4.20433 5.39525 4.14628L8.10918 3.37806C8.35636 3.30809 8.54954 3.1149 8.61951 2.86773L9.38773 0.1538Z" fill="#2BFFFF"/></svg>
                            <span class="wide align-center">{{ t('generate') }}</span>
                         </div>
                    </button>
                    <div class="dropdown-body">
                        <ul class="menu">
                            <li>
                                <a href="javascript:void(0);" class="site-aiimage-mode" @click.stop="openDrawer('create')">
                                    <i class="fas fa-image text-purple"></i>
                                    <span>{{ t('create_new') }}</span>
                                </a>
                            </li>
                            <li :class="{'disabled':!has_reference}" :title="has_reference ? '' : t('reference_unavailable')" @click.stop="openDrawer('edit')">
                                <a href="javascript:void(0);" class="site-aiimage-mode site-aiimage-mode-edit">
                                    <i class="fas fa-pen text-purple"></i>
                                    <span>{{ t('edit') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            `,
            methods: {
                openDrawer(mode) {
                    openDrawer(this.context, { mode });
                },
                t(key) {
                    return this.locale[key] || key;
                },
                updateContext() {
                    this.context = that.getContext(this.block_id) || {};
                    that.context = this.context;
                    if (typeof that.onContextUpdate === 'function') {
                        that.onContextUpdate({ ...that.context });
                    }
                },
            },
            created() {
                $.wa.editor._block_settings_drawer_promise.then(() => {
                    this.updateContext();
                });
            },
            mounted() {
                $(this.$el).waDropdown({
                    hover: false,
                    items: ".site-aiimage-mode",
                    update_title: false,
                    hide: true,
                    open: () => {
                        this.updateContext();
                    }
                });
            }
        };
    };

    EditorAdapter.prototype.getBackgroundTarget = function(data, block_id) {
        let $dropdown = $(".background-dropdown .color-dropdown.is-opened");
        if (!$dropdown.length) {
            $dropdown = $(".background-dropdown:first .color-dropdown");
        }
        if (!$dropdown.length) {
            return null;
        }

        const inline_props = data.inline_props || {};
        const layer_index = $dropdown.closest('.background-dropdown').index();
        let element = null;
        Object.keys(inline_props).some(function(key) {
            var value = inline_props[key];
            if (value && value.background && $dropdown.hasClass(key)) {
                element = key;
                return true;
            }
            return false;
        });

        var background = element ? inline_props[element].background : inline_props.background;
        if (!background?.layers) {
            return null;
        }

        var layer = background.layers[layer_index];
        if (layer?.type !== "image" || !layer?.file_url) {
            return null;
        }

        return {
            kind: "background",
            block_id: block_id,
            element: element,
            layer_key: layer_index
        };
    };

    EditorAdapter.prototype.getBackgroundObject = function(data, target) {
        const inline_props = data.inline_props || {};
        return target.element ? inline_props[target.element].background : null;
    };

    EditorAdapter.prototype.getBackgroundLayer = function(data, target) {
        const background = this.getBackgroundObject(data, target || {});
        const layers = background?.layers;
        if (!layers) return null;

        const layer = layers[target.layer_key];
        return layer || null;
    };

    EditorAdapter.prototype.getSelectedImageReference = function() {
        const block_id = $.wa.editor.selected_block_id;
        const context = this.getContext(block_id);
        return context;
    };

    EditorAdapter.prototype.getContext = function(block_id) {
        const editor = $.wa.editor;
        const kind = this.getImageKind(editor, block_id);
        if (kind === null) {
            return null;
        }

        const data = editor.block_storage.getData(block_id) || {};
        let image = {};
        if (kind === 'image') {
            image = this.getCurrentImage(block_id, data);
        } else if (kind === 'background') {
            const background = this.getBackgroundTarget(data, block_id);
            if (!background) return null;

            const layer = this.getBackgroundLayer(data, background);
            if (layer?.type !== "image" || !layer.file_url) {
                return null;
            }

            var file = editor.block_storage.getFile(block_id, "bg_image") || null;
            if (file && this.normalizeUrl(file.url) !== this.normalizeUrl(layer.file_url)) {
                file = null;
            }

            image = { file: file, url: layer.file_url };
        }

        return {
            block_id,
            kind,
            current_file_id: image.file && image.file.id ? image.file.id : 0,
            current_image_url: image.url,
            current_image_attachable: this.isAttachableImage(image),
            // block_data: data
        };
    };

    EditorAdapter.prototype.getImageKind = function(editor, block_id) {
        const config = editor.block_storage.getFormConfig(block_id);
        if (!config) {
            return null;
        }
        if (config.type === 'site.Image') {
            return 'image';
        }
        const is_background = (Object.values(config.sections || [])).find(section => section.type === 'BackgroundColorGroup');
        return is_background ? 'background' : null;
    };

    EditorAdapter.prototype.getCurrentImage = function(block_id, data) {
        const image_data = data.image || {};
        let url = "";
        let file = null;
        if (image_data.type === "upload") {
            const storage = $.wa.editor.block_storage;
            const files = storage?.data?.[block_id]?.files ? storage.data[block_id].files : {};

            file = files[""] || storage.getFile(block_id, "") || null;
            url = file && file.url ? file.url : "";
        } else if (image_data.type === "address") {
            url = image_data.url_text || "";
            file = this.normalizeUrl(url);
        }
        return { file, url };
    };

    EditorAdapter.prototype.isAttachableImage = function(image) {
        if (!image || !image.url) {
            return false;
        }
        if (image.file && parseInt(image.file.id) > 0) {
            return true;
        }

        try {
            var url = new URL(image.url, window.location.href);
            if (url.origin !== window.location.origin) {
                return false;
            }
            return /\.(?:gif|jpe?g|png|webp)$/i.test(url.pathname) &&
                /\/(?:wa-apps|wa-content|wa-plugins|wa-widgets|wa-data\/public)\//.test(url.pathname);
        } catch (e) {
            return false;
        }
    };

    EditorAdapter.prototype.normalizeUrl = function(url) {
        url = String(url || "").trim();
        if (!url) return "";
        try {
            return new URL(url, window.location.href).href.replace(/#.*$/, "");
        } catch (e) {
            return url;
        }
    };

    EditorAdapter.prototype.getApplyTarget = function() {
        return { ...this.context };
    };

    EditorAdapter.prototype.applyGeneratedImage = function(file, target) {
        const block_id = parseInt(target.block_id);
        if (target.kind === "background") {
            return this.applyGeneratedBackground(file, target);
        }

        const block_data = $.extend(true, {}, $.wa.editor.block_storage.getData(block_id) || {});
        block_data.image = {
            type: "upload",
            name: file.name
        };

        $.wa.editor.saveBlockData(block_id, block_data, {
            notify_editor_inside_iframe: true,
            notify_block_settings_form: true,
            mode: "update",
            delay: 0
        });

        return $.wa.editor.uploadFile(block_id, "", file);
    };

    EditorAdapter.prototype.applyGeneratedBackground = function(file, target) {
        const that = this;
        const block_id = parseInt(target?.block_id) || 0;
        const current_data = block_id ? $.wa.editor.block_storage.getData(block_id) || {} : {};
        target = that.getBackgroundTarget(current_data, block_id) || {};
        const current_layer = that.getBackgroundLayer(current_data, target);
        if (!block_id || !current_layer || current_layer.type !== "image" || !current_layer.file_url) {
            return $.Deferred().reject(new Error(that.locale["request_failed"])).promise();
        }

        return $.wa.editor.uploadFile(block_id, "bg_image", file).then(function(url) {
            var block_data = $.extend(true, {}, $.wa.editor.block_storage.getData(block_id) || {});
            var layer = that.getBackgroundLayer(block_data, target);
            if (!layer || layer.type !== "image" || !layer.file_url) {
                return $.Deferred().reject(new Error(that.locale["request_failed"])).promise();
            }

            layer.file_name = file.name;
            layer.file_url = url;
            layer.value = that.getBackgroundImageValue(layer, url);
            that.updateBackgroundValue(block_data, target);

            $.wa.editor.saveBlockData(block_id, block_data, {
                notify_editor_inside_iframe: true,
                notify_block_settings_form: true,
                mode: "update",
                delay: 0
            });

            return url;
        });
    };

    EditorAdapter.prototype.getBackgroundImageValue = function(layer, url) {
        var alignment_x = layer.alignmentX || "center";
        var alignment_y = layer.alignmentY || "center";
        var space = layer.space || "cover";
        var space_value = space !== "contain" ? " / " + space : "";
        return alignment_x + " " + alignment_y + space_value + " url(" + url + ")";
    };

    EditorAdapter.prototype.updateBackgroundValue = function(data, target) {
        var background = this.getBackgroundObject(data, target || {});
        var layers = background && background.layers;
        if (!background || !layers) {
            return;
        }

        var values = [];
        $.each(layers, function(i, layer) {
            if (layer && layer.type !== "palette" && layer.type !== "video" && layer.value) {
                values.push(layer.value);
            }
        });
        background.value = values.join(", ");
    };

    function HtmlEditorAdapter(config) {
        this.config = config || {};
        this.locale = this.config.locale || {};
        this.saved_range = null;
        this.active_image = null;
        this.image_urls = {};
    }

    HtmlEditorAdapter.prototype.init = function() {
        const that = this;
        that.ensureButton();

        $(document).on("click.siteAiimageHtml", () => {
            if (!$.site.aiImage.drawer) {
                return;
            }
            setTimeout(() => {
                if (typeof that.onContextUpdate === 'function') {
                    that.onContextUpdate({ ...that.getApplyTarget() });
                }
            }, 0);
        });
    };

    HtmlEditorAdapter.prototype.getTextarea = function() {
        return $("#wa-page-content");
    };

    HtmlEditorAdapter.prototype.ensureButton = function() {
        var that = this;
        var $textarea = this.getTextarea();
        if (!$textarea.length) {
            return;
        }

        this.bindEditorSelection();

        var $wrapper = $textarea.closest(".wa-editor-core-wrapper");
        var $cheatsheet = $wrapper.find(".wa-cheatsheet").first();
        var $toolbar = $cheatsheet.children("div").first();
        if (!$toolbar.length) {
            $toolbar = $cheatsheet;
        }

        var $variables = $toolbar.find(".js-show-variables").first();
        if (!$variables.length) {
            return;
        }

        var $button = $("#js-aiimage-button");
        if (!$button.length) {
            $button = $(
                '<button id="js-aiimage-button" class="b-ai-button button nobutton nowrap light-gray text-dark-gray" type="button">' +
                    '<span class="icon webasyst-magic-wand-ai custom-pr-8"></span>' +
                    '<span></span>' +
                '</button>'
            );
            $button.find("span:last").text(this.t("html_button"));
        }

        if (!$button.data("siteAiimageHtmlBound")) {
            $button
                .on("mousedown.siteAiimageHtml", function(e) {
                    e.preventDefault();
                    that.saveSelection();
                })
                .on("click.siteAiimageHtml", function(e) {
                    that.openDrawer(e);
                })
                .data("siteAiimageHtmlBound", 1);
        }

        if ($button[0] !== $variables.prev()[0]) {
            $variables.before($button);
        }
    };

    HtmlEditorAdapter.prototype.getWrapper = function() {
        return this.getTextarea().closest(".wa-editor-core-wrapper");
    };

    HtmlEditorAdapter.prototype.getRedactor = function() {
        var $textarea = this.getTextarea();
        return $textarea.length ? $textarea.data("redactor") : null;
    };

    HtmlEditorAdapter.prototype.getRedactorEditor = function() {
        const $textarea = this.getTextarea();
        try {
            return $textarea.redactor("core.editor");
        } catch {
            return $();
        }
    };

    HtmlEditorAdapter.prototype.bindEditorSelection = function() {
        var that = this;
        var $editor = this.getRedactorEditor();
        if (!$editor.length || $editor.data("siteAiimageHtmlBound")) {
            return;
        }

        $editor.data("siteAiimageHtmlBound", 1);
        $editor
            .on("mousedown.siteAiimageHtml click.siteAiimageHtml", "img", function() {
                that.active_image = this;
            })
            .on("keyup.siteAiimageHtml mouseup.siteAiimageHtml", function() {
                that.rememberSelection();
            });
    };

    HtmlEditorAdapter.prototype.openDrawer = function(e) {
        e.preventDefault();
        this.saveSelection();
    };

    HtmlEditorAdapter.prototype.getContext = function() {
        return {
            block_id: 0,
            // page_id: null,
            current_file_id: 0,
            current_image_attachable: false,
            current_image_url: "",
            kind: 'image'
        };
    };

    HtmlEditorAdapter.prototype.getSelectionNode = function(editor) {
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) {
            return null;
        }

        var range = selection.getRangeAt(0);
        var node = range.commonAncestorContainer || selection.anchorNode;
        if (!node) {
            return null;
        }

        var element = node.nodeType === 1 ? node : node.parentNode;
        return element && (editor.isSameNode(element) || $.contains(editor, element)) ? node : null;
    };

    HtmlEditorAdapter.prototype.rememberSelection = function() {
        var $editor = this.getRedactorEditor();
        var selection = window.getSelection();
        if (!$editor.length || !selection || !selection.rangeCount) {
            return false;
        }

        var range = selection.getRangeAt(0);
        var container = range.commonAncestorContainer;
        var element = container && container.nodeType === 1 ? container : container && container.parentNode;
        if (!element || (!$editor[0].isSameNode(element) && !$.contains($editor[0], element))) {
            return false;
        }

        this.saved_range = range.cloneRange();

        return true;
    };

    HtmlEditorAdapter.prototype.saveSelection = function() {
        this.rememberSelection();

        const redactor = this.getRedactor();
        if (!redactor?.selection?.save) {
            return;
        }

        try {
            redactor.selection.save();
        } catch (e) {}
    };

    HtmlEditorAdapter.prototype.restoreSelection = function() {
        var redactor = this.getRedactor();
        if (redactor && redactor.selection && redactor.selection.restore) {
            try {
                redactor.selection.restore();
                return true;
            } catch (e) {}
        }

        if (!this.saved_range) {
            return false;
        }

        try {
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.saved_range);
            return true;
        } catch (e) {
            return false;
        }
    };

    HtmlEditorAdapter.prototype.getApplyTarget = function() {
        return this.getTextarea().data("redactor") ? this.getSelectedImageReference() || this.getContext() : null;
    };

    HtmlEditorAdapter.prototype.applyGeneratedImage = function(file) {
        const that = this;
        const url = that.image_urls[file.name];
        if (url) {
            that.insertImage(url);
            return $.Deferred().resolve(url);
        }

        return this.uploadPageImage(file).then(function(url) {
            that.image_urls[file.name] = url;
            that.insertImage(url);
            return url;
        });
    };

    HtmlEditorAdapter.prototype.uploadPageImage = function(file) {
        const url = this.config.urls?.image_upload;
        const form = new FormData();
        const fallback_message = this.t("request_failed");

        form.append("file", file, file.name);

        return $.ajax({
            url,
            method: "POST",
            data: form,
            processData: false,
            contentType: false
        }).then(function(response) {
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch {}
            }
            if (response?.url) {
                return response.url;
            } else {
                throw new Error(response?.error?.[0] || fallback_message);
            }
        });
    };

    HtmlEditorAdapter.prototype.insertImage = function(url) {
        const $textarea = this.getTextarea();
        const html = "\n<figure><img src=\"" + this.escapeAttr(url) + "\"></figure>\n";
        if (!this.restoreSelection()) {
            try {
                $textarea.redactor("focus.start");
            } catch {}
        }

        $textarea.waEditor2("insert", html);
        $textarea.waEditor2("sync");

        this.markChanged();
    };

    HtmlEditorAdapter.prototype.markChanged = function() {
        this.getTextarea().trigger("input").trigger("change");
        $("#wa-page-button, .js-page-submit-button").removeClass("green").addClass("yellow");
    };

    HtmlEditorAdapter.prototype.getSelectedImageReference = function() {
        var $image = this.getSelectedEditorImage();
        if (!$image.length) {
            return null;
        }

        var image_url = $image.attr("src");
        if (!image_url || !this.isAttachableImage({ url: image_url })) {
            return null;
        }

        var context = this.getContext();
        context.current_image_url = image_url;
        context.current_image_attachable = true;

        return context;
    };

    HtmlEditorAdapter.prototype.getSelectedEditorImage = function() {
        var $editor = this.getRedactorEditor();
        if (!$editor.length) {
            return $();
        }

        var redactor = this.getRedactor();
        if (redactor && redactor.observe && redactor.observe.image) {
            var $observed = $(redactor.observe.image);
            if ($observed.length && $.contains($editor[0], $observed[0])) {
                return $observed;
            }
        }

        var $active = $editor.find("img").filter(function() {
            return String(this.style.opacity) === ".5" || String($(this).css("opacity")) === "0.5";
        }).last();
        if ($active.length) {
            return $active;
        }

        if (this.active_image && $.contains($editor[0], this.active_image)) {
            return $(this.active_image);
        }

        var node = this.getSelectionNode($editor[0]);
        if (!node) {
            return $();
        }

        var element = node.nodeType === 1 ? node : node.parentNode;
        if (!element?.tagName) {
            return $();
        }

        if (element.tagName.toLowerCase() === "img") {
            return $(element);
        }

        var $container = $(element);
        var $image = $container.closest("figure", $editor[0]).find("img").first();
        return $image.length ? $image : $container.find("img").first();
    };

    HtmlEditorAdapter.prototype.escapeAttr = function(value) {
        return $("<div />").text(value == null ? "" : String(value)).html().replace(/"/g, "&quot;");
    };

    HtmlEditorAdapter.prototype.t = function(key) {
        return this.locale[key] || key;
    };

    HtmlEditorAdapter.prototype.normalizeUrl = EditorAdapter.prototype.normalizeUrl;
    HtmlEditorAdapter.prototype.isAttachableImage = EditorAdapter.prototype.isAttachableImage;

    // helper
    function getDrawerPlacement() {
        const $sidebar = $(".block-settings-sidebar").first();
        const direction = $sidebar.hasClass("leftPosition") ? "left" : "right";

        // Other editor
        let width = '28rem';

        // Blockpage editor
        if ($sidebar.length) {
            width = '19rem';
        }

        return { direction, width };
    };

    function openDrawer(context, { mode = 'create', select_chat_id = null } = {}) {
        const deferred = $.Deferred();

        const { direction, width } = getDrawerPlacement();

        const open = () => {
            $.site.aiImage.drawer.open(context, $.site.aiImage.adapter, { direction, width, mode, select_chat_id });
        };

        if (!$.site.aiImage.drawer) {
            $.site.helper.preventDupeRequest(() => {
                return $.get('?module=editor&action=aiImageDrawer', (html) => {
                    $('body').append(html);
                    open();
                    deferred.resolve();
                });
            });
        } else {
            open();
            deferred.resolve();
        }

        return deferred.promise();
    };

    $.site.aiImage.getUUID = () =>
        typeof crypto?.randomUUID === 'function'
            ? crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });

    // db
    $.site.aiImage.getDbStorage = (function({
        ver = 1,
        db_name = "site_aiimage",
        chats_table = "chats",
        messages_table = "messages",
        max_recent_chats = 10
    } = {}) {
        const request = indexedDB.open(db_name, ver);

        request.onupgradeneeded = (e) => {
            const db = e.target.result;

            if (db.objectStoreNames.contains(chats_table)) {
                db.deleteObjectStore(chats_table);
            }
            db.createObjectStore(chats_table, { keyPath: "id" });

            if (db.objectStoreNames.contains(messages_table)) {
                db.deleteObjectStore(messages_table);
            }
            const messageStore = db.createObjectStore(messages_table, { keyPath: "id" });
            messageStore.createIndex('by-chat-id', 'chat_id', { unique: false });
            messageStore.createIndex('by-status', 'status', { unique: false });
        };

        const takeResult = request =>
            new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });

        // Очистка "зомби"-генераций при старте
        const resetZombieGenerations = async (db) => {
            const tx = db.transaction([chats_table, messages_table], 'readwrite');
            const messageStore = tx.objectStore(messages_table);
            const status_index = messageStore.index("by-status");

            const active_messages = await takeResult(status_index.getAll(IDBKeyRange.only("sending")));
            if (!active_messages?.length) return;

            const now = Date.now();
            const heartbeat_max_pause = 2500;
            const affected_chat_ids = new Map();

            // $.site.log('Zombies found: ', active_messages.length);

            for (const msg of active_messages) {
                const sending_heartbeat = msg.sending_heartbeat || 0;
                if (now - sending_heartbeat > heartbeat_max_pause) {
                    const msg_count = (affected_chat_ids.get(msg.chat_id) || 0) + 1;
                    affected_chat_ids.set(msg.chat_id, msg_count);

                    msg.error_message = null;
                    msg.status = 'interrupted';
                    messageStore.put(msg);
                }
            }

            if (!affected_chat_ids.size) return;

            const chatStore = tx.objectStore(chats_table);
            for (const [chat_id, msg_count] of affected_chat_ids.entries()) {
                const chat = await takeResult(chatStore.get(chat_id));
                if (chat) {
                    chat.active_generations_count = Math.max(0, chat.active_generations_count - msg_count);
                    chatStore.put(chat);
                }
            }
        };

        const dbPromise = new Promise((resolve, reject) => {
            request.onsuccess = async () => {
                await resetZombieGenerations(request.result);
                resolve(request.result);
            };
            request.onerror = () => {
                console.error("Failed to IndexedDB:", request.error);
                reject(request.error);
            };
        });

        const useStore = (store_name, handler, mode = "readonly") =>
            dbPromise.then((db) => {
                const transaction = db.transaction(store_name, mode);
                const store = transaction.objectStore(store_name);
                if (typeof handler === 'function') {
                    return handler(store);
                }
            });

        const shouldRemoveFirstChat = async (tx) => {
            const chatStore = tx.objectStore(chats_table);
            const messageStore = tx.objectStore(messages_table);

            const chats = await takeResult(chatStore.getAll()) || [];
            if (chats.length <= max_recent_chats) return;

            chats.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            const chatsToDelete = chats.slice(0, chats.length - max_recent_chats);
            // $.site.log('Chats to delete:', chatsToDelete.map(c => c.id));

            for (const chat of chatsToDelete) {
                await takeResult(chatStore.delete(chat.id));

                const index = messageStore.index('by-chat-id');
                const range = IDBKeyRange.only(chat.id);
                const cursorRequest = index.openCursor(range);
                cursorRequest.onsuccess = (e) => {
                    const cursor = e.target.result;
                    if (cursor) {
                        cursor.delete();
                        cursor.continue();
                    }
                };
            }
        };

        const getChats = () => {
            return useStore(chats_table, async (store) => {
                const chats = await takeResult(store.getAll());
                return (chats || []).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            });
        };

        const addChat = async (tx, { id = null, title = '', draft_prompt = null, active_generations_count = 0 } = {}) => {
            if (!title) return null;

            const final_id = id || $.site.aiImage.getUUID();

            await takeResult(
                tx.objectStore(chats_table).add({
                    id: final_id,
                    title,
                    ...(draft_prompt ? { draft_prompt } : {}),
                    active_generations_count,
                    unread_message_ids: [],
                    created_at: new Date().toISOString()
                })
            );

            await shouldRemoveFirstChat(tx);
            return final_id;
        };

        const updateChat = ({ chat_id = '', draft_prompt = '', active_generations_count = 0, unread_message_ids = [] } = {}) => {
            return useStore(chats_table, async (store) => {
                if (!chat_id) return false;

                const chat = await takeResult(store.get(chat_id));
                if (chat) {
                    if (draft_prompt) chat.draft_prompt = draft_prompt;
                    if (typeof active_generations_count === 'number') chat.active_generations_count = active_generations_count;
                    if (Array.isArray(unread_message_ids)) chat.unread_message_ids = unread_message_ids;

                    store.put(chat);
                    return true;
                }
                return false;
            }, "readwrite");
        };

        const getMessagesByChat = (chat_id) => {
            return useStore(messages_table, async (store) => {
                const index = store.index('by-chat-id');
                const range = IDBKeyRange.only(chat_id);
                const result = await takeResult(index.getAll(range));

                return (result || []).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            });
        };

        const getMessageById = (id) => {
            return useStore(messages_table, async (store) => {
                const result = await takeResult(store.get(id));
                return result;
            });
        };

        const addMessageToChat = async ({ chat_id = null, prompt = '', reference_files = [] } = {}) => {
            const db = await dbPromise;
            const tx = db.transaction([chats_table, messages_table], 'readwrite');
            const chatStore = tx.objectStore(chats_table);
            const messageStore = tx.objectStore(messages_table);

            if (!chat_id) {
                chat_id = $.site.aiImage.getUUID();
                await addChat(tx, { id: chat_id, title: prompt, active_generations_count: 1 });
            } else {
                const chat = await takeResult(chatStore.get(chat_id));
                if (chat) {
                    chat.active_generations_count = (chat.active_generations_count || 0) + 1;
                    await takeResult(chatStore.put(chat));
                }
            }

            const message_id = $.site.aiImage.getUUID();
            await takeResult(
                messageStore.add({
                    id: message_id,
                    chat_id,
                    prompt,
                    reference_files,
                    status: 'sending',
                    sending_heartbeat: 0,
                    created_at: new Date().toISOString(),
                })
            );

            return { chat_id, message_id };
        };

        // Обновляет статус и данные конкретного сообщения, корректирует счетчик генераций чата
        const updateChatMessage = async ({
            message_id = '', chat_id = '', status = 'success', generated_images = [], error_message = null } = {},
            mark_unread = false
        ) => {
            if (!message_id || !chat_id) return false;

            const db = await dbPromise;
            const tx = db.transaction([chats_table, messages_table], 'readwrite');
            const chatStore = tx.objectStore(chats_table);
            const messageStore = tx.objectStore(messages_table);

            const chat = await takeResult(chatStore.get(chat_id));
            if (chat) {
                if (chat.active_generations_count > 0) {
                    chat.active_generations_count -= 1;
                }

                if (mark_unread) {
                    if (!chat.unread_message_ids) chat.unread_message_ids = [];

                    if (!chat.unread_message_ids.includes(message_id)) {
                        chat.unread_message_ids.push(message_id);
                    }
                }
                await takeResult(chatStore.put(chat));
            }

            const message = await takeResult(messageStore.get(message_id));
            if (!message) return false;

            message.status = status;
            message.generated_images = generated_images || [];
            message.error_message = error_message;

            const result = await takeResult(messageStore.put(message));
            return result;
        };

        // Сбрасывает непрочитанные сообщения у чата
        const markChatAsRead = (chat_id) => {
            if (!chat_id) return Promise.resolve(false);
            return updateChat({ chat_id, unread_message_ids: [] });
        };

        // Проверяет, есть ли в данный момент хоть одна активная генерация
        const findLatestActiveChatId  = () => {
            return useStore(messages_table, async (messageStore) => {
                const index = messageStore.index("by-status");
                const range = IDBKeyRange.only("sending");
                // const keys = await takeResult(index.getAllKeys(range));
                // return !!keys?.length;
                const request = index.openCursor(range, 'prev');
                return new Promise((resolve) => {
                    request.onsuccess = (e) => {
                        const cursor = e.target.result;
                        if (cursor) {
                            resolve(cursor.value.chat_id || null);
                        } else {
                            resolve(null);
                        }
                    };
                    request.onerror = () => resolve(null);
                });
            });
        };

        // Находит первый попавшийся чат с непрочитанными сообщениями
        const findUnreadChat = () => {
            return useStore(chats_table, async (chatStore) => {
                const allChats = await takeResult(chatStore.getAll()) || [];
                allChats.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                const unreadChat = allChats.find(chat => chat.unread_message_ids?.length > 0);
                return unreadChat || null;
            });
        };

        return {
            getChats,
            getMessagesByChat,
            getMessageById,
            addMessageToChat,
            updateChatMessage,
            markChatAsRead,
            findLatestActiveChatId,
            findUnreadChat
        };
    })();

    // AI button
    $.site.aiImage.initHeaderButton = () => {
        const $aiimage_button = $('#js-aiimage-button');
        const $aiimage_icon = $aiimage_button.find('.webasyst-magic-wand-ai');
        const { findLatestActiveChatId, findUnreadChat } = $.site.aiImage.getDbStorage;
        const channel = new BroadcastChannel('ai-generation-channel');
        let unread_chat_id = null;
        let active_chat_id = null;

        const showGenerationLoading = () => {
            $aiimage_button.removeClass('nobutton');
            $aiimage_icon.addClass('shimmer');
        };
        const toggleStatusDot = (show = false) => {
            if (show) {
                $aiimage_button.removeClass('nobutton');
            }
            $aiimage_button.toggleClass('status-dot', show);
        };
        const updateStatus = () => {
            findLatestActiveChatId().then(chat_id => {
                if (chat_id) {
                    showGenerationLoading();
                }
                active_chat_id = chat_id ?? null;
            });
            findUnreadChat().then(chat => {
                unread_chat_id = chat?.id ?? null;
                toggleStatusDot(!!chat);
            });
        };
        updateStatus();

        channel.addEventListener('message', (event) => {
            const type = event.data?.type;

            if (type === 'GENERATION_SENDING') {
                showGenerationLoading();
                active_chat_id = event.data?.chat_id ?? null;

            } else if (type === 'GENERATION_SUCCESS' || type === 'GENERATION_FAILED') {
                $aiimage_icon.removeClass('shimmer');
                updateStatus();
                active_chat_id = null;

            } else if (type === 'DRAWER_CLOSED') {
                updateStatus();
            }
        });

        $aiimage_button.on('click', (e) => {
            e.preventDefault();
            $aiimage_button.prop('disabled', true);

            openDrawer($.site.aiImage.adapter.getApplyTarget(), {
                mode: "create",
                select_chat_id: active_chat_id || unread_chat_id
            }).then(() => {
                $aiimage_button.prop('disabled', false);
            });
        });
    };

    // define
    $.site.aiImage.initEditorAdapter = function(config) {
        $.site.aiImage.adapter = new EditorAdapter(config);

        $.site.aiImage.initHeaderButton();

        return $.site.aiImage.adapter;
    };

    $.site.aiImage.initHtmlEditorAdapter = function(config) {
        $.site.aiImage.adapter = new HtmlEditorAdapter(config);
        $.site.aiImage.adapter.init();

        $.site.aiImage.initHeaderButton();

        return $.site.aiImage.adapter;
    };

})(jQuery);
