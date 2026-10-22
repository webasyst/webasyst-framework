(function($) {
    "use strict";

    $.site.aiImage = $.site.aiImage || {};

    function Drawer(config) {
        this.config = config || {};
        this.parameter_groups = this.config.locale.parameter_groups || [];
        this.max_references =  this.config.max_references || 5;
        this.drawer = null;
        this.context = {};
        this.adapter = null;
        this.mode = "create";
        this.aspect_ratio = config.aspect_ratio;
        this.generation_count = 1;
        this.references = [];
        this.reference_id = 0;
        this.object_urls = [];
        this.sending_count = 0;
        this.downloaded_variants = new Map();
        this.processed_message_ids = new Set();
        this.storage_keys = {
            draft_prompt: 'site/ai_image/draft_prompt',
            last_selected_option: 'site/ai_image/last_selected_option',
            generation_progress_alert_hidden: 'site/ai_image/generation_progress_alert_hidden',
        };

        this.recentChatStorage = null;
        this.serviceWorker = null;

        this.initServiceWorker();
    }

    Drawer.prototype.open = function(context, adapter, options) {
        options = options || {};
        this.$wrapper = this.config.$wrapper;
        this.sending_count = 0;
        this.context = context || {};
        this.adapter = adapter || {};
        this.mode = options.mode;
        this.aspect_ratio = this.config.aspect_ratio;
        this.references = [];
        this.generation_count = 1;
        this.downloaded_variants = new Map();
        this.controller = null;
        this.channel = new BroadcastChannel('ai-generation-channel');
        this.use_button_disabled = false;

        this.disposeReferences();
        if (this.mode === "edit") {
            this.addPageReference(this.context);
        }
        this.bindEvents();

        this.drawer = $.waDrawer({
            $wrapper: (() => this.$wrapper)(),
            direction: options.direction || "right",
            width: options.width || false,
            lock_body_scroll: false,
            onClose: this.onClose.bind(this)
        });
        this.initDropdowns();
        this.updateReferenceView();
        this.updateUseButtonEnabled();

        const draft_prompt = this.recallDraftPrompt();
        if (draft_prompt) {
            this.setDraftPrompt(draft_prompt);
        } else {
            this.focusPrompt();
        }
        this.initRecentChatStorage(options.select_chat_id);
        this.initToggleWidth();
        this.initBroadcastListener();

        this.adapter.onContextUpdate = (context) => {
            // $.site.log('onContextUpdate', context);
            this.updateReferencePickerState(context);
            this.updateUseButtonEnabled(context);
        };
    };

    Drawer.prototype.bindEvents = function() {
        var that = this;

        this.$wrapper.on("submit", ".site-aiimage-composer", function(e) {
            e.preventDefault();
            that.toggleGenerate();
        });

        this.$wrapper.on("input", ".site-aiimage-prompt", function() {
            that.autosizePrompt(this);
            that.updateEmptyState();
            that.rememberDraftPrompt(this.value);
        });

        this.$wrapper.on("keydown", ".site-aiimage-prompt", function(e) {
            if ((e.metaKey || e.ctrlKey) && e.keyCode === 13) {
                e.preventDefault();
                that.toggleGenerate();
            }
        });

        this.$wrapper.on("paste", ".site-aiimage-prompt", function(e) {
            that.handlePromptPaste(e);
        });

        this.$wrapper.on("click", ".site-aiimage-settings-toggle", function(e) {
            e.preventDefault();
            that.toggleParameters();
        });

        this.$wrapper.on("click", ".site-aiimage-generation-count", function(e) {
            e.preventDefault();
            that.nextGenerationCount();
        });

        this.$wrapper.on("click", ".site-aiimage-aspect-ratio-option", function(e) {
            e.preventDefault();
            that.setAspectRatio($(this).attr("data-aspect-ratio"));
        });

        this.$wrapper.on("click", ".site-aiimage-parameters-close", function(e) {
            e.preventDefault();
            that.toggleParameters(false);
        });

        this.$wrapper.on("click", ".site-aiimage-param-option", function(e) {
            e.preventDefault();
            that.appendPromptText($(this).attr("data-prompt-text"));
        });

        this.$wrapper.on("click", ".site-aiimage-param-heading", function(e) {
            e.preventDefault();
            const $self = $(this);
            const key = $self.data('key');
            if (!key) return;
            that.rememberGroupHeadingOption(key);
            $self.closest('li').siblings().hide();
            $self.hide().next('ul').show();
        });

        this.$wrapper.on("click", ".site-aiimage-param-back", function(e) {
            e.preventDefault();
            const $ul = $(this).closest('ul').hide();
            $ul.prev().show();
            $ul.closest('li').siblings().show();
            that.rememberGroupHeadingOption(null);
        });

        this.$wrapper.on("input", ".site-aiimage-custom-color", function() {
            var $input = $(this);
            var value = String($input.val() || "").toLowerCase();
            $input.closest(".site-aiimage-custom-color-row").find(".site-aiimage-custom-color-value").text(value);
        });

        this.$wrapper.on("click", ".site-aiimage-custom-color-add", function(e) {
            e.preventDefault();
            var $dropdown = $(this).closest(".site-aiimage-param-dropdown");
            var $input = $dropdown.find(".site-aiimage-custom-color").first();
            var label = $input.attr("data-prompt-label") || "";
            var value = String($input.val() || "").toLowerCase();
            if (!value) {
                return;
            }
            that.appendPromptText(label + ": " + value);

            var dropdown = $input.closest(".site-aiimage-param-dropdown").data("dropdown");
            if (dropdown && dropdown.hide) {
                dropdown.hide();
            }
        });

        this.$wrapper.on("click", ".site-aiimage-reference-upload-action", function(e) {
            e.preventDefault();
            that.$wrapper.find(".site-aiimage-reference-upload input").get(0).click();
        });

        this.$wrapper.on("change", ".site-aiimage-reference-upload input", function() {
            that.addUploadedReferences(this.files || []);
            this.value = "";
        });

        this.$wrapper.on("click", ".site-aiimage-reference-page-action", function(e) {
            e.preventDefault();
            const reference_context = that.adapter.getSelectedImageReference();
            if (reference_context) {
                that.addPageReference(reference_context);
            }
        });

        this.$wrapper.on("click", ".site-aiimage-reference-remove", function(e) {
            e.preventDefault();
            that.removeReference($(this).attr("data-reference-id"));
        });

        this.$wrapper.on("click", ".site-aiimage-use", function(e) {
            e.preventDefault();
            const $button = $(this);
            that.applyVariant($button.data("message-id"), $button.data("index"), $button);
        });

        this.$wrapper.on("click", ".site-aiimage-download", function(e) {
            e.preventDefault();
            that.downloadVariant($(this).data("message-id"), $(this).data("index"));
        });

        this.$wrapper.on("click", ".site-aiimage-attach-generated", function(e) {
            e.preventDefault();
            that.attachGeneratedVariant($(this).data("message-id"), $(this).data("index"));
        });

        this.$wrapper.on("click", ".site-aiimage-request-copy", function(e) {
            e.preventDefault();
            that.copyRequestPrompt($(this).data("prompt"), $(this));
        });

        this.$wrapper.on("click", ".site-aiimage-request-edit", function(e) {
            e.preventDefault();
            var request = $(this).data("aiimageRequest") || {};
            that.editRequest(request.prompt || "", request.references || []);
        });

        this.$wrapper.on("click", ".js-start-chat-drawer", function(e) {
            e.preventDefault();
            that.startChat();
        });
    };

    Drawer.prototype.nextGenerationCount = function() {
        this.generation_count = this.generation_count >= 4 ? 1 : this.generation_count + 1;
        this.updateGenerationCountButton();
    };

    Drawer.prototype.updateGenerationCountButton = function() {
        this.$wrapper.find(".site-aiimage-generation-count").text("×" + this.generation_count);
    };

    Drawer.prototype.setAspectRatio = function(value) {
        this.$wrapper.find(".site-aiimage-aspect-ratio-toggle").text(value);
        const dropdown = this.$wrapper.find(".site-aiimage-aspect-ratio-dropdown").data("dropdown");
        if (dropdown) {
            dropdown.hide();
        }
    };

    Drawer.prototype.wideDropdown = function($dropdown) {
        var $composer = this.$wrapper.find(".site-aiimage-composer");
        var $body = $dropdown.find("> .dropdown-body");
        var composer_offset = $composer.offset();
        var dropdown_offset = $dropdown.offset();
        if (!composer_offset || !dropdown_offset) {
            return;
        }

        $body.css({
            left: (composer_offset.left + 16 - dropdown_offset.left) + "px",
            right: "auto",
            width: Math.max(0, $composer.outerWidth() - 32) + "px"
        });
    };

    Drawer.prototype.initDropdowns = function() {
        const that = this;

        this.$wrapper.find(".site-aiimage-param-dropdown").each(function() {
            const $dropdown = $(this);
            if (!$dropdown.data("dropdown")) {
                $dropdown.waDropdown({
                    hover: false,
                    items: ".site-aiimage-param-option",
                    update_title: false,
                    hide: true,
                    open: (dropdown) => {
                        const $menu = dropdown.$menu.find('[data-group-key]');
                        const heading_key = that.recallGroupHeadingOption($menu.data('group-key'));
                        if (heading_key) {
                            $menu.find(`.site-aiimage-param-heading[data-key="${heading_key}"]`).trigger('click');
                        }
                    }
                });
            }
        });

        const $aspect_ratio_dropdown = this.$wrapper.find(".site-aiimage-aspect-ratio-dropdown");
        $aspect_ratio_dropdown.waDropdown({
            hover: false,
            items: ".site-aiimage-aspect-ratio-option",
            update_title: false,
            hide: true,
            open: (dropdown) => {
                that.wideDropdown(dropdown.$wrapper);
            },
            change: (_,el) => {
                that.aspect_ratio = el.dataset.aspectRatio;
            }
        });

        const $reference_dropdown = this.$wrapper.find(".site-aiimage-reference-dropdown");
        $reference_dropdown.waDropdown({
            hover: false,
            items: ".site-aiimage-reference-upload-action, .site-aiimage-reference-page-action",
            update_title: false,
            hide: true,
        });

        that.$wrapper.find(".site-aiimage-recent-chats-dropdown").waDropdown({
            hover: false,
            items: "li",
            update_title: false,
            open: (dropdown) => {
                that.wideDropdown(dropdown.$wrapper);
            }
        });

        if ($.fn.waUpload) {
            this.$wrapper.find(".site-aiimage-reference-upload").waUpload({
                show_file_name: false
            });
        }

        const maxWidth = (that.$wrapper.find('.drawer-body').width() - 10) || 'none';
        $(that.$wrapper.find('[data-wa-tooltip-content]')).waTooltip({ maxWidth });
    };

    Drawer.prototype.initRecentChatStorage = function(select_chat_id = null) {
        const that = this;

        that.recentChatStorage = that.initChatStorageManager({
            storage: $.site.aiImage.getDbStorage,
            onInit(storageManager) {
                const chats = storageManager.chats || [];
                const has_chats = chats.length > 0;

                that.$wrapper.find('.js-start-chat-drawer').toggleClass('hidden', !has_chats);

                const $dropdown = that.$wrapper.find(".site-aiimage-recent-chats-dropdown").toggle(has_chats);
                if (!$dropdown.length || !has_chats) return;

                const $dropdown_menu = $dropdown.find('.menu').empty();
                const $dropdown_button = $dropdown.find('.dropdown-toggle');

                const updateGlobalUnreadStatus = (has_unread) => {
                    $dropdown_button.toggleClass('status-dot', has_unread);
                };

                const affected_unread_chat_ids = new Set();
                const fragment = document.createDocumentFragment();
                for (const chat of chats) {
                    const has_unread = !!chat.unread_message_ids?.length;
                    if (has_unread) {
                        affected_unread_chat_ids.add(chat.id);
                    }

                    const $item = $('<li>').append(
                        $('<div class="item">')
                            .data('chat-id', chat.id || null)
                            .append(
                                $('<span>')
                                    .addClass(['text-ellipsis', has_unread ? 'status-dot' : ''])
                                    .text(chat.title)
                                    .attr('title', chat.title)
                            )
                    );

                    fragment.appendChild($item[0]);
                }

                $dropdown_menu.append(fragment);
                updateGlobalUnreadStatus(affected_unread_chat_ids.size > 0);

                // 3. Обработка клика (Делегирование событий на уровне контейнера)
                $dropdown_menu.off('click', '.item').on('click', '.item', async function () {
                    const $item = $(this);
                    const chat_id = $item.data('chat-id');
                    if (!chat_id) return;

                    const chat = storageManager.selectChat(chat_id);

                    if (affected_unread_chat_ids.has(chat.id)) {
                        const is_marked = await storageManager.markChatAsRead(chat.id);
                        if (is_marked) {
                            affected_unread_chat_ids.delete(chat.id);
                            updateGlobalUnreadStatus(affected_unread_chat_ids.size > 0);
                            $item.find('.status-dot').removeClass('status-dot');
                        }
                    }

                    const messages = await storageManager.getMessages();
                    that.restoreChat(chat, messages);
                });

                const openSelectChat = () => {
                    // console.log('select_chat_id', select_chat_id);
                    if (!select_chat_id) return;

                    $dropdown_menu.find('.item')
                        .filter((_, el) => $(el).data('chat-id') === select_chat_id)
                        .trigger('click');

                    select_chat_id = null;
                }
                openSelectChat();
            }
        });
    };

    Drawer.prototype.initBroadcastListener = function() {
        this.channel.addEventListener('message', async (e) => {
            const { message_id, type } = e.data || {};
            if (!message_id || !type) return;

            if (type !== 'GENERATION_SUCCESS' && type !== 'GENERATION_FAILED') return;

            if (this.processed_message_ids.has(message_id)) return;
            this.processed_message_ids.add(message_id);

            const message = await this.recentChatStorage.getMessageById(message_id);
            if (message && message.chat_id === this.recentChatStorage.active_chat_id) {
                // console.log('BroadcastChannel message processed:', message_id, type);
                this.renderMessage(message);
                this.recentChatStorage.markChatAsRead(message.chat_id);
            }
        });
    };

    Drawer.prototype.toggleGenerate = function() {
        if (this.sending_count) {
            this.abortGeneration();
        } else {
            this.generate();
        }
    };

    Drawer.prototype.generate = function() {
        var that = this;
        var prompt = $.trim(this.$wrapper.find(".site-aiimage-prompt").val());
        this.toggleParameters(false);
        if (!prompt) {
            this.$wrapper.find(".site-aiimage-prompt").trigger("focus");
            return;
        }
        this.clearStatus();
        var reference_files = this.cloneReferences(this.references);
        var request_data = this.getRequestData(prompt);
        this.clearComposerReferences();
        this.beginGenerationSubmit();
        this.$wrapper.find(".site-aiimage-prompt").val("").trigger("input");

        that.recentChatStorage.addMessageToChat(prompt, reference_files)
            .then(({ chat_id, message_id }) => {

                const $response = that.appendRequest({ prompt, reference_files, message_id });
                that.setMessageStatus($response, "sending");
                that.postMessage({ type: 'GENERATION_SENDING', chat_id });

                that.post(this.config.urls.generate, request_data, message_id)
                    .then(function(generated_images) {
                        generated_images = that.convertGeneratedImagesToFiles(generated_images);
                        that.finishGenerationSubmit();
                        that.handleSuccess(message_id, generated_images, $response);
                        that.recentChatStorage.updateChatMessage({
                            chat_id,
                            message_id,
                            status: 'success',
                            generated_images
                        });
                        that.postMessage({ type: 'GENERATION_SUCCESS', message_id });
                    }).catch(function(error) {
                        const status = error.name === "AbortError" ? "canceled" : "failed";
                        that.finishGenerationSubmit();
                        that.setMessageStatus($response, status, error.message);
                        that.recentChatStorage.updateChatMessage({
                            chat_id,
                            message_id,
                            status,
                            ...(status !== 'canceled' ? { error_message:error.message ?? error.name } : {})
                        });
                        that.postMessage({ type: 'GENERATION_FAILED', message_id });
                    });
            });
    };

    Drawer.prototype.handleSuccess = function(message_id, images, $response) {
        images = images || [];
        this.downloaded_variants.set(message_id, images);
        this.renderResults($response, message_id, images);
    };

    Drawer.prototype.appendRequest = function({ prompt = '', reference_files = [], message_id = '' } = {}) {
        var $chat = this.$wrapper.find(".site-aiimage-chat");
        var $request = $('<div class="site-aiimage-message site-aiimage-message-user"></div>');
        $("<div>").addClass("small box rounded highlighted blue break-word").text(prompt).appendTo($request);

        if (reference_files?.length) {
            var $reference = $('<div class="site-aiimage-message-reference flexbox wrap middle space-8 custom-mt-8 align-right"></div>');
            for (var i = 0; i < reference_files.length; i++) {
                $('<img alt="">').attr("src", reference_files[i].preview_url || "").appendTo($reference);
            }
            $reference.appendTo($request);
        }
        this.renderRequestActions(prompt, reference_files).appendTo($request);

        var $response = $('<div class="site-aiimage-message site-aiimage-message-ai"></div>').attr('data-message-id', message_id);
        $('<div class="site-aiimage-message-status state-hint"></div>').appendTo($response);

        $chat.append($request).append($response);
        this.updateEmptyState();
        setTimeout(() => this.scrollToBottom());

        return $response;
    };

    Drawer.prototype.renderRequestActions = function(prompt, references) {
        var request = {
            prompt: prompt || "",
            references: this.cloneReferences(references)
        };
        var $actions = $('<div class="site-aiimage-request-actions flexbox middle space-2 custom-mt-8"></div>');
        $('<button type="button" class="button nobutton circle smaller light-gray site-aiimage-request-copy"></button>')
            .attr("title", this.t("copy_request"))
            .data("prompt", request.prompt)
            .append('<i class="fas fa-copy"></i>')
            .appendTo($actions);
        if (request.references.length) {
            $('<button type="button" class="button nobutton circle smaller light-gray site-aiimage-request-edit"></button>')
                .attr("title", this.t("edit_request"))
                .data("aiimageRequest", request)
                .append('<i class="fas fa-pen"></i>')
                .appendTo($actions);
        }
        return $actions;
    };

    Drawer.prototype.renderResults = function($response, message_id, images) {
        $response.empty();
        if (!images.length) {
            this.setMessageStatus($response, "failed", this.t("status_failed"));
            return;
        }

        for (let i = 0; i < images.length; i++) {
            const url = this.createPreviewUrl(images[i]);
            const $item = $('<div class="site-aiimage-result custom-mb-20"></div>');
            $("<img />").attr({
                src: url,
                alt: images[i].name || this.t("view")
            }).appendTo($item);

            const $actions = $('<div class="flexbox middle space-2 custom-pt-10 custom-pb-4"></div>');
            $('<button type="button" class="button light-gray rounded smaller wide site-aiimage-use"></button>')
                .attr("data-index", i)
                .attr("data-message-id", message_id)
                .append($('<span>').addClass('custom-mr-4').append('<i class="fas fa-paste"></i>'))
                .append($("<span>").text(this.t("use")))
                .prop('disabled', this.use_button_disabled)
                .appendTo($actions);
            $('<button type="button" class="button light-gray circle smaller site-aiimage-download"></button>')
                .attr("data-message-id", message_id)
                .attr("data-index", i)
                .attr("title", this.t("download"))
                .append($('<span>').append('<i class="fas fa-download"></i>'))
                .appendTo($actions);
            $('<button type="button" class="button light-gray circle smaller site-aiimage-attach-generated"></button>')
                .attr("data-message-id", message_id)
                .attr("data-index", i)
                .attr("title", this.t("attach_image"))
                .append($('<span>').append('<i class="fas fa-pen"></i>'))
                .appendTo($actions);

            $actions.appendTo($item);
            $item.appendTo($response);
        }
    };

    Drawer.prototype.convertGeneratedImagesToFiles = function(images) {
        return images.map(image => this.dataUrlToFile(image.data_url, image.name));
    };

    Drawer.prototype.dataUrlToFile = function(data_url, filename) {
        const [header, base64] = data_url.split(',');
        const mime = header.match(/:(.*?);/)[1];
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);

        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        return new File([bytes], filename, { type: mime });
    };

    Drawer.prototype.downloadVariant = function(message_id, index) {
        const file = this.downloaded_variants.get(message_id)?.[index];
        if (!file) return;

        const blob_url = URL.createObjectURL(file);
        const link = document.createElement('a');
        link.style.display = 'none';
        link.href = blob_url;
        link.download = file.name;
        link.click();
        link.remove();
        URL.revokeObjectURL(blob_url);
    };

    Drawer.prototype.getVariantBlob = function(variant) {
        return fetch(variant.data_url, { credentials: "same-origin" })
            .then(response => response.blob())
            .then(blob => ({ blob, filename: variant.name }));
    };

    Drawer.prototype.attachGeneratedVariant = function(message_id, index) {
        const that = this;
        that.clearStatus();
        if (this.downloaded_variants.get(message_id)?.[index]) {
            that.addUploadedReferences([this.downloaded_variants.get(message_id)[index]]);
            that.focusPrompt();
        } else {
            that.setStatus("failed", error.message || that.t("save_failed"));
        }
    };

    Drawer.prototype.updateUseButtonEnabled = function(context = this.context) {
        const $button = this.$wrapper.find('.site-aiimage-use');
        const disabled = context?.block_id === undefined;
        this.use_button_disabled = disabled;
        this.disableButton($button, disabled);
    };

    Drawer.prototype.disableButton = function($button, disabled = true) {
        $button.prop('disabled', !!disabled);
    };

    Drawer.prototype.setButtonIconBusy = function($button, is_busy, fallback_icon) {
        if (!$button.length) {
            return;
        }
        this.disableButton($button, is_busy);

        const $icon_wrapper = $button.find("span").first();
        if (is_busy) {
            const $icon = $icon_wrapper.children().first();
            if (!$button.data("aiimageOriginalIconHtml")) {
                $button.data("aiimageOriginalIconHtml", $icon.length ? $icon[0].outerHTML : '<i class="' + fallback_icon + '"></i>');
            }
            $icon_wrapper.empty().append('<i class="fas fa-spinner fa-spin"></i>');
        } else {
            const original_icon = $button.data("aiimageOriginalIconHtml") || '<i class="' + fallback_icon + '"></i>';
            $icon_wrapper.empty().append(original_icon);
            $button.removeData("aiimageOriginalIconHtml");
        }

        this.updateSendButton();
    };

    Drawer.prototype.createReferenceFile = function(blob, filename) {
        var type = this.getImageMimeType(blob, filename);
        return new File([blob], filename, { type });
    };

    Drawer.prototype.getImageMimeType = function(blob, filename) {
        var type = blob && /^image\/(?:jpeg|png|gif|webp)$/i.test(blob.type || "") ? blob.type : "";
        if (type) {
            return type;
        }
        if (/\.png$/i.test(filename || "")) {
            return "image/png";
        }
        if (/\.gif$/i.test(filename || "")) {
            return "image/gif";
        }
        if (/\.webp$/i.test(filename || "")) {
            return "image/webp";
        }
        return "image/jpeg";
    };

    Drawer.prototype.copyRequestPrompt = function(prompt, $button) {
        prompt = $.trim(prompt || "");
        if (!prompt) return;

        function showCopied() {
            var original_html = $button.html();
            $button.html('<i class="fas fa-check"></i>');
            setTimeout(function() {
                $button.html(original_html);
            }, 1200);
        }

        $.wa.copyToClipboard(prompt).then(() => {
            showCopied();
        });
    };

    Drawer.prototype.editRequest = function(prompt, references) {
        prompt = $.trim(prompt || "");
        if (prompt) {
            this.appendPromptText(prompt);
        }
        this.attachRequestReferences(references || []);
        this.focusPrompt();
    };

    Drawer.prototype.attachRequestReferences = function(references) {
        var added = 0;
        for (var i = 0; i < references.length; i++) {
            if (this.references.length >= this.max_references) {
                this.setStatus("failed", this.t("max_references"));
                break;
            }

            var reference = this.createComposerReference(references[i]);
            if (!reference) {
                continue;
            }

            this.references.push(reference);
            added += 1;
        }
        if (added) {
            this.clearStatus();
            this.updateReferenceView();
        }
    };

    Drawer.prototype.createComposerReference = function(reference) {
        reference = reference || {};
        const that = this;
        const source = reference.source;
        if (source === "upload" && reference.file) {
            const id = "upload-" + (++that.reference_id);

            return {
                id,
                source: "upload",
                file: reference.file,
                preview_url: reference.preview_url || "",
                label: reference.label || (reference.file && reference.file.name) || that.t("uploaded_image"),
                // context: reference.context ? { ...reference.context } : null,
            };
        }

        if (source === "page" && (reference.context || reference.file)) {
            if (reference.context?.current_image_url && that.hasDuplicateReference(reference.context?.current_image_url)) {
                return null;
            }

            const id = "page-" + (++that.reference_id);

            // add file
            if (reference.context?.current_image_url) {
                that.getVariantBlob({
                    data_url: reference.context.current_image_url,
                    name: reference.context.current_image_url.split('/').pop()
                }).then(function(variant) {
                    const file = that.createReferenceFile(variant.blob, variant.filename);
                    const index = that.references.findIndex(ref => ref.id === id);
                    if (index !== -1) {
                        that.references[index].file = file;
                    }
                });
            } else if (!reference.file) {
                return null;
            }

            return {
                id,
                source: "page",
                preview_url: reference.preview_url || reference.context.current_image_url || "",
                label: reference.label || that.t("from_page"),
                ...(reference.file ? { file: reference.file } : {}),
                // context: { ...reference.context }
            };
        }

        return null;
    };

    Drawer.prototype.applyVariant = async function(message_id, index, $button) {
        var that = this;
        var target = this.adapter?.getApplyTarget ? this.adapter.getApplyTarget() : null;
        if (!target) {
            throw new Error('Adapter not found');
        }

        this.setButtonIconBusy($button, true, "fas fa-paste");
        this.clearStatus();
        if (this.downloaded_variants.get(message_id)?.[index]) {
            await that.adapter.applyGeneratedImage(this.downloaded_variants.get(message_id)[index], target);
        } else {
            that.setStatus("failed", error.message || that.t("save_failed"));
        }
        this.setButtonIconBusy($button, false, "fas fa-paste");
    };

    Drawer.prototype.getRequestData = function(prompt) {
        const form = new FormData();
        form.append("prompt", prompt || "");
        form.append("aspect_ratio", this.aspect_ratio);
        // form.append("variants", this.generation_count);
        form.append("_csrf", this.config._csrf);

        for (const reference of this.references) {
            if (reference.file) {
                form.append("reference_files[]", reference.file, reference.file.name);
            }
        }

        return form;
    };

    Drawer.prototype.post = function(url, data, message_id) {
        this.controller = new AbortController();

        const xhr = fetch(url, {
            method: "POST",
            body: data,
            credentials: "same-origin",
            headers: {
                'X-Message-Id': message_id || 0
            },
            signal: this.controller.signal
        })
        .then(async (response) => await response.json())
        .finally(() => {
            this.controller = null;
        });

        return xhr.then((response) => {
            if (response?.status !== "ok") {
                let message = this.t("request_failed");
                if (response?.errors?.text) {
                    message = response.errors.text || message;
                }
                const error = new Error(message);
                error.status = response.status;
                throw error;
            }

            return response.data;
        });
    };

    Drawer.prototype.updateSendButton = function() {
        const is_sending = this.sending_count > 0;
        this.$wrapper.find(".site-aiimage-prompt")
            .prop('required', !is_sending);
        this.$wrapper.find(".site-aiimage-send")
            .html(is_sending ? '<i class="fas fa-stop icon size-14"></i>' : '<i class="fas fa-arrow-up"></i>');
    };

    Drawer.prototype.beginGenerationSubmit = function() {
        this.sending_count += 1;
        this.updateSendButton();
    };

    Drawer.prototype.finishGenerationSubmit = function() {
        this.sending_count = Math.max(0, this.sending_count - 1);
        this.updateSendButton();
        setTimeout(() => this.scrollToBottom(), 10);
    };

    Drawer.prototype.setMessageStatus = function($message, status, message) {
        var key = "status_" + status;
        var text = message || this.t(key, this.t("status_empty", "")); // TODO: check
        var $status = $message.find(".site-aiimage-message-status").first();
        if (!$status.length) {
            $status = $('<div class="site-aiimage-message-status"></div>').prependTo($message);
        }
        $status
            .removeClass("state-error-hint state-success-hint state-hint")
            .toggleClass("flexbox middle space-8", status !== "failed")
            .addClass(status === "failed" || status === 'interrupted' ? "state-error-hint" : "state-hint");
        if (status === "failed" || status === 'interrupted') {
            $status.html(text);
        } else if (status === "canceled") {
            $status.empty();
        } else {
            const shimmer_url = this.config?.assets?.wand_ai_shimmer;
            $status.empty();
            if (shimmer_url) {
                $("<img class=\"site-aiimage-generating-icon\" alt=\"\">")
                    .attr("src", shimmer_url)
                    .appendTo($status);
            } else {
                $status.append('<i class="fas fa-spinner fa-spin"></i>');
            }
            $status.append($("<span></span>").text(this.t("generating")));
        }
    };

    Drawer.prototype.setStatus = function(status, message) {
        var key = "status_" + status;
        var $status = this.$wrapper.find(".site-aiimage-status");
        $status
            .removeClass("state-error-hint state-success-hint state-hint")
            .addClass(status === "failed" ? "state-error-hint" : (status === "applied" ? "state-success-hint" : "state-hint"))
            .text(message || this.t(key, this.t("status_empty", "")))
            .attr("hidden", false);
    };

    Drawer.prototype.clearStatus = function() {
        this.$wrapper.find(".site-aiimage-status").text("").attr("hidden", true);
    };

    Drawer.prototype.toggleParameters = function(open) {
        var $panel = this.$wrapper.find(".site-aiimage-parameters");
        var is_open = typeof open === "boolean" ? open : $panel.is("[hidden]");
        $panel.attr("hidden", !is_open);
        var $toggle = this.$wrapper.find(".site-aiimage-settings-toggle").toggleClass("selected", is_open);
        this.setSecondaryButtonActive($toggle, is_open);
    };

    Drawer.prototype.setSecondaryButtonActive = function($button, active) {
        $button
            .toggleClass("nobutton text-gray", !active)
            .toggleClass("light-gray", active);
    };

    Drawer.prototype.appendPromptText = function(text) {
        text = $.trim(text || "");
        if (!text) {
            return;
        }

        const $prompt = this.$wrapper.find(".site-aiimage-prompt");
        let current = $.trim($prompt.val());

        if (current && /[,;:]$/.test(current)) {
            current = current.slice(0, -1) + ".";
        }
        const glue = current ? (/[.!?]$/.test(current) ? " " : ". ") : "";
        $prompt.val(current + glue + text).trigger("input").trigger("focus");
    };

    Drawer.prototype.rememberGroupHeadingOption = function(heading_key) {
        const current_group_key = $('.site-aiimage-param-dropdown.is-opened [data-group-key]').data('group-key');
        if (!current_group_key) return;
        const storage_key = `${this.storage_keys.last_selected_option}/${current_group_key}`;
        if (heading_key) {
            localStorage.setItem(storage_key, heading_key);
        } else {
            localStorage.removeItem(storage_key);
        }
    };

    Drawer.prototype.recallGroupHeadingOption = function(current_group_key) {
        if (!current_group_key) return;
        const storage_key = `${this.storage_keys.last_selected_option}/${current_group_key}`;
        return localStorage.getItem(storage_key);
    };

    Drawer.prototype.rememberDraftPrompt = function(text) {
        localStorage.setItem(this.storage_keys.draft_prompt, text);
    };

    Drawer.prototype.recallDraftPrompt = function() {
        return localStorage.getItem(this.storage_keys.draft_prompt) || '';
    };

    Drawer.prototype.rememberGenerationProgressAlertHidden = function() {
        localStorage.setItem(this.storage_keys.generation_progress_alert_hidden, true);
    };

    Drawer.prototype.isGenerationProgressAlertHidden = function() {
        return !!localStorage.getItem(this.storage_keys.generation_progress_alert_hidden);
    };

    Drawer.prototype.hasReference = function() {
        return !!(this.context.current_image_url && (parseInt(this.context.current_file_id) > 0 || this.context.current_image_attachable));
    };

    Drawer.prototype.updateReferenceView = function() {
        var $references = this.$wrapper.find(".site-aiimage-references").empty();
        $.each(this.references, function(index, reference) {
            var $item = $('<div class="site-aiimage-reference"></div>');
            var $thumb = $('<div class="site-aiimage-reference-thumb"></div>');
            $('<img>').attr({
                src: reference.preview_url || "",
                alt: reference.label || ""
            }).appendTo($thumb);
            $thumb.appendTo($item);
            $('<button type="button" class="button black circle smallest opacity-50 site-aiimage-reference-remove"><i class="fas fa-times"></i></button>')
                .attr("data-reference-id", reference.id)
                .appendTo($item);
            $item.appendTo($references);
        });

        $references.toggleClass("hidden", !this.references.length);
        this.$wrapper.find(".site-aiimage-reference-toggle").toggleClass("selected", this.references.length > 0);
        this.updateReferencePickerState();
    };

    Drawer.prototype.clearComposerReferences = function() {
        this.references = [];
        this.updateReferenceView();
    };

    Drawer.prototype.cloneReferences = function(references) {
        var result = [];
        references = references || [];
        for (var i = 0; i < references.length; i++) {
            var reference = references[i] || {};
            var clone = {
                id: reference.id,
                source: reference.source || "",
                preview_url: reference.preview_url || "",
                label: reference.label || ""
            };
            if (reference.file) {
                clone.file = reference.file;
            }
            if (reference.context) {
                clone.context = $.extend(true, {}, reference.context);
            }
            result.push(clone);
        }
        return result;
    };

    Drawer.prototype.createPageReference = function(context) {
        return {
            id: "page-" + (++this.reference_id),
            source: "page",
            preview_url: context.current_image_url || "",
            label: this.t("from_page"),
            context: { ...context }
        };
    };

    Drawer.prototype.addPageReference = function(context) {
        if (this.references.length >= this.max_references) {
            this.setStatus("failed", this.t("max_references"));
            return;
        }
        if (this.hasDuplicateReference(context.current_image_url)) {
            return;
        }

        const reference = this.createComposerReference(this.createPageReference(context));
        if (reference) {
            this.references.push(reference);

            this.clearStatus();
            this.updateReferenceView();
        }
    };

    Drawer.prototype.handlePromptPaste = function(e) {
        var files = this.getClipboardImageFiles(e);
        if (!files.length) {
            return;
        }

        e.preventDefault();
        this.addUploadedReferences(files);
        this.focusPrompt();
    };

    Drawer.prototype.getClipboardImageFiles = function(e) {
        var original_event = e && e.originalEvent ? e.originalEvent : e;
        var clipboard = original_event?.clipboardData;
        var files = [];
        var i;
        var file;

        if (!clipboard) {
            return files;
        }

        var items = clipboard.items || [];
        for (i = 0; i < items.length; i++) {
            var item = items[i];
            if (!item || item.kind !== "file" || !item.getAsFile) {
                continue;
            }

            file = item.getAsFile();
            if (!file || !this.isClipboardImageFile(file, item.type)) {
                continue;
            }
            files.push(this.normalizeClipboardImageFile(file, files.length, item.type));
        }

        if (files.length || !clipboard.files) {
            return files;
        }

        for (i = 0; i < clipboard.files.length; i++) {
            file = clipboard.files[i];
            if (this.isClipboardImageFile(file)) {
                files.push(this.normalizeClipboardImageFile(file, files.length));
            }
        }

        return files;
    };

    Drawer.prototype.isClipboardImageFile = function(file, type_hint) {
        var type = String(type_hint || (file && file.type) || "");
        var name = String(file && file.name || "");
        return /^image\//i.test(type) || /\.(?:gif|jpe?g|png|webp)$/i.test(name);
    };

    Drawer.prototype.getClipboardImageExtension = function(type) {
        var extensions = {
            "image/gif": "gif",
            "image/jpeg": "jpg",
            "image/jpg": "jpg",
            "image/png": "png",
            // "image/svg+xml": "svg",
            "image/webp": "webp"
        };

        return extensions[String(type || "").toLowerCase()] || "image";
    };

    Drawer.prototype.normalizeClipboardImageFile = function(file, index, type_hint) {
        if (!file) {
            return file;
        }

        var type = String(file.type || type_hint || "");
        var name = String(file.name || "");
        if (name && file.type) {
            return file;
        }

        if (!name) {
            var suffix = index > 0 ? "-" + (index + 1) : "";
            name = "clipboard-image" + suffix + "." + this.getClipboardImageExtension(type);
        }

        if (!window.File) {
            try {
                file.name = name;
            } catch (ignore) {}
            return file;
        }

        try {
            return new File([file], name, { type: type });
        } catch (e) {
            try {
                file.name = name;
            } catch (ignore) {}
            return file;
        }
    };

    Drawer.prototype.addUploadedReferences = function(files) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (this.references.length >= this.max_references) {
                this.setStatus("failed", this.t("max_references"));
                break;
            }
            var is_supported = file && (
                /^image\/(?:jpeg|png|gif|webp)$/i.test(file.type || "") ||
                (!(file.type || "") && /\.(?:gif|jpe?g|png|webp)$/i.test(file.name || ""))
            );
            if (!is_supported) {
                this.setStatus("failed", this.t("unsupported_reference"));
                continue;
            }
            if (!this.isReferenceFileSizeAllowed(file)) {
                this.setStatus("failed", this.t("reference_too_large"));
                continue;
            }

            const id = "upload-" + (++this.reference_id);
            const reference = {
                id,
                file,
                preview_url: this.createPreviewUrl(file),
                source: "upload",
                label: file.name || this.t("uploaded_image")
            };
            this.references.push(reference);
        }
        // if (added && !has_error) {
        //     this.setStatus("sending", this.t("reference_uploading"));
        // }
        this.updateReferenceView();
        this.updateSendButton();
    };

    Drawer.prototype.createPreviewUrl = function(file) {
        const preview_url = URL.createObjectURL(file);
        this.object_urls.push(preview_url);
        return preview_url;
    };

    Drawer.prototype.isReferenceFileSizeAllowed = function(file) {
        var max_size = parseInt(this.config.limits && this.config.limits.max_reference_file_size) || 0;
        if (!max_size || !file || !file.size) {
            return true;
        }

        return file.size <= max_size;
    };

    Drawer.prototype.removeReference = function(reference_id) {
        this.references = this.references.filter(function(reference) {
            return String(reference.id) !== String(reference_id);
        });
        this.updateReferenceView();
    };

    Drawer.prototype.hasDuplicateReference = function(url) {
        var expected = this.adapter ? this.adapter.normalizeUrl(url) : url || "";
        return this.references.some(function(reference) {
            var current = reference.preview_url || "";
            if (this.adapter?.normalizeUrl) {
                current = this.adapter.normalizeUrl(current);
            }
            return reference.source === "page" && current === expected;
        }, this);
    };

    Drawer.prototype.updateReferencePickerState = function(context = this.context) {
        if (!this.$wrapper) return;

        const is_available = context?.block_id !== undefined && !!context.current_image_url;
        const $action = this.$wrapper.find(".site-aiimage-reference-page-action");
        $action
            .toggleClass("text-gray", !is_available)
            .attr("title", is_available ? "" : this.t("reference_unavailable"));
        $action.closest("li").toggleClass("disabled", !is_available);
    };

    Drawer.prototype.disposeReferences = function() {
        for (var i = 0; i < this.object_urls.length; i++) {
            URL.revokeObjectURL(this.object_urls[i]);
        }
        this.object_urls = [];
    };

    Drawer.prototype.onClose = function() {
        this.disposeReferences();
        $.site.aiImage.drawer = null;
        if (this.sending_count > 0 && !this.isGenerationProgressAlertHidden() && $.site.showAlert) {
            $.site.showAlert({
                header: this.config.locale.generation_continues_after_drawer_close_header,
                text: this.config.locale.generation_continues_after_drawer_close_text,
                type: 'info',
                timeout: 10000,
                onClose: () => {
                    this.rememberGenerationProgressAlertHidden();
                }
            });
        }
        this.postMessage({ type: 'DRAWER_CLOSED' });
        this.channel.close();
    };

    Drawer.prototype.updateEmptyState = function() {
        var has_prompt = !!$.trim(this.$wrapper.find(".site-aiimage-prompt").val());
        var has_messages = this.$wrapper.find(".site-aiimage-message").length > 0;
        this.$wrapper.find(".site-aiimage-empty").toggleClass("hidden", has_prompt || has_messages);
    };

    Drawer.prototype.scrollToBottom = function() {
        const $chat = this.$wrapper.find(".site-aiimage-chat");
        if ($chat.length) {
            $chat.scrollTop($chat[0].scrollHeight);
        }
    };

    Drawer.prototype.focusPrompt = function() {
        var that = this;
        setTimeout(function() {
            if (that.$wrapper) {
                that.$wrapper.find(".site-aiimage-prompt").trigger("focus");
            }
        }, 120);
    };

    Drawer.prototype.setDraftPrompt = function(text) {
        this.$wrapper.find(".site-aiimage-prompt").val(text).trigger("input");
    };

    Drawer.prototype.autosizePrompt = function(textarea) {
        var styles = window.getComputedStyle(textarea);
        var min_height = parseFloat(styles.minHeight) || 34;
        var max_height = parseFloat(styles.maxHeight) || 160;

        textarea.style.height = "auto";
        var scroll_height = textarea.scrollHeight;
        textarea.style.height = Math.min(Math.max(scroll_height, min_height), max_height) + "px";
        textarea.style.overflowY = scroll_height > max_height ? "auto" : "hidden";
    };

    Drawer.prototype.t = function(key) {
        return this.config.locale[key] || key;
    };

    Drawer.prototype.postMessage = function(message) {
        const channel = new BroadcastChannel('ai-generation-channel');
        channel.postMessage(message);
        channel.close();
    }

    Drawer.prototype.abortGeneration = function() {
        if (!this.controller) {
            return;
        }
        this.controller.abort();
        if (this.serviceWorker) {
            this.serviceWorker.then(registration => {
                registration.active?.postMessage({
                    type: 'REQUEST_ABORT',
                    message_id: this.recentChatStorage.active_message_id ?? 0
                });
            });
        }
    };

    Drawer.prototype.initToggleWidth = function() {
        const that = this;
        const $body = that.$wrapper.find('> .drawer-body');
        const min_width = $body.width();
        const max_width = 720;

        that.$wrapper.on("click", ".js-toggle-width-drawer", function(e) {
            e.preventDefault();
            const $button = $(this);
            const $expand_icon = $button.find('.js-expand');
            const $collapse_icon = $button.find('.js-collapse');
            const expanded = $expand_icon.hasClass('hidden');

            $expand_icon.toggleClass('hidden', !expanded);
            $collapse_icon.toggleClass('hidden', expanded);

            const width = (expanded ? min_width : max_width) + 'px';
            $body.css({ width });
        });
    };

    Drawer.prototype.initServiceWorker = function() {
        if ('ServiceWorker' in window) {
            navigator.serviceWorker.register(this.config.urls.service_worker)
                .then(registration => {
                    const activateWaiting = (state) => {
                        if (!state) return;
                        state.postMessage({ type: 'SKIP_WAITING' });
                    };

                    activateWaiting(registration.waiting);

                    registration.addEventListener('updatefound', () => {
                        activateWaiting(registration.installing);
                    });
                });

            this.serviceWorker = navigator.serviceWorker.ready;
        }
    };

    Drawer.prototype.restoreChat = function(chat, messages) {
        const that = this;
        that.startChat({ reinitialize_store: false });

        if (chat.draft_prompt) {
            that.setDraftPrompt(chat.draft_prompt);
        }

        for (const message of messages) {
            that.renderMessage(message || {});
        }
    };

    Drawer.prototype.renderMessage = function({ id, prompt, status, generated_images, reference_files, error_message }) {
        if (!id || !prompt) return;
        const that = this;

        reference_files = (reference_files || []).map(ref => {
            return { ...ref, preview_url: that.createPreviewUrl(ref.file) };
        });

        let $response = that.$wrapper.find(`.site-aiimage-message-ai[data-message-id="${id}"]`);
        if (!$response.length) {
            $response = that.appendRequest({ message_id: id, prompt, reference_files });
        }

        status = status || (generated_images?.length ? 'success' : 'failed');
        if (status === 'success') {
            that.handleSuccess(id, generated_images, $response);
        } else {
            that.setMessageStatus($response, status, error_message || null);
        }
    };

    Drawer.prototype.startChat = function({ reinitialize_store = true } = {}) {
        const that = this;
        that.downloaded_variants = new Map();
        that.generation_count = 1;
        // that.reference_id = 0;
        // that.abortGeneration();
        that.clearComposerReferences();
        that.disposeReferences();
        that.clearStatus();
        that.finishGenerationSubmit();
        that.updateEmptyState();
        that.focusPrompt();
        that.$wrapper.find(".site-aiimage-chat").empty().html(this.config.templates.welcome || '');

        if (reinitialize_store) {
            that.initRecentChatStorage();
        }
    };

    Drawer.prototype.initChatStorageManager = function(options = {}) {
        const that = this;
        function ChatStorageManager(options) {
            this.storage = options.storage;
            this.onInit = options.onInit;

            this.chats = [];
            this.active_chat_id = null;
            this.active_message_id = null;

            this.initChats();
        };
        ChatStorageManager.prototype.initChats = function() {
            this.storage.getChats().then(chats => {
                this.chats = chats;

                if (typeof this.onInit === 'function') {
                    this.onInit(this);
                }
            });
        };
        ChatStorageManager.prototype.selectChat = function(id) {
            const chat = this.chats.find(chat => chat.id === id);
            this.active_chat_id = chat?.id || null;
            return chat;
        };
        ChatStorageManager.prototype.getMessages = async function() {
            if (!this.active_chat_id) return [];
            return await this.storage.getMessagesByChat(this.active_chat_id);
        };
        ChatStorageManager.prototype.getMessageById = function(id) {
            if (!id) return;
            return this.storage.getMessageById(id);
        };
        ChatStorageManager.prototype.addMessageToChat = async function(prompt, reference_files) {
            if (!prompt) return;
            const { chat_id, message_id } = await this.storage.addMessageToChat({
                chat_id: this.active_chat_id,
                prompt,
                reference_files
            });

            if (this.active_chat_id !== chat_id) {
                this.initChats();
            }

            this.active_chat_id = chat_id;
            this.active_message_id = message_id;

            return { chat_id, message_id };
        };
        ChatStorageManager.prototype.updateChatMessage = async function({
           chat_id = '',
           message_id = '',
           status = '',
           generated_images = [],
           error_message = null
        } = {}) {
            if (!chat_id || !message_id) return;

            const mark_as_unread = that.$wrapper.is(':hidden') || this.active_chat_id !== chat_id;

            const result = await this.storage.updateChatMessage(
                { chat_id, message_id, status, generated_images, error_message },
                mark_as_unread
            );

            if (!mark_as_unread) {
                this.initChats();
            }

            return result;
        };
        ChatStorageManager.prototype.markChatAsRead = function(chat_id) {
            return this.storage.markChatAsRead(chat_id);
        };

        return new ChatStorageManager(options);
    };

    $.site.aiImage.initDrawer = function(config) {
        $.site.aiImage.drawer = new Drawer(config);
    };
})(jQuery);
