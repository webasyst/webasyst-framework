$.ajaxSetup({ cache: false });

// Show Menu on Hover
( function($) {

    var enter, leave;

    var storage = {
        activeClass: "submenu-is-shown",
        activeShadowClass: "is-shadow-shown",
        showTime: 200,
        $last_li: false,
        $pagesClone: null,
        $departments: null,
    };

    var bindEvents = function() {
        var $selector = $(".flyout-nav > li"),
            links = $selector.find("> a");

        $selector.on("mouseenter", function() {
            showSubMenu( $(this) );
        });

        $selector.on("mouseleave", function() {
            hideSubMenu( $(this) );
        });

        links.on("click", function() {
            onClick( $(this).closest("li") );
        });

        links.each( function() {
            var link = this,
                $li = $(link).closest("li"),
                has_sub_menu = ( $li.find(".flyout").length );

            if (has_sub_menu) {
                link.addEventListener("touchstart", function(event) {
                    onTouchStart(event, $li );
                }, false);
            }
        });

        $("body").get(0).addEventListener("touchstart", function(event) {
            onBodyClick(event, $(this));
        }, false);

    };

    var onBodyClick = function(event) {
        var activeBodyClass = storage.activeShadowClass,
            is_click_on_shadow = ( $(event.target).hasClass(activeBodyClass) );

        if (is_click_on_shadow) {
            var $active_li = $(".flyout-nav > li." + storage.activeClass).first();

            if ($active_li.length) {
                hideSubMenu( $active_li );
            }
        }
    };

    var onClick = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass);

        if (is_active) {
            var href = $li.find("> a").attr("href");
            if ( href && (href !== "javascript:void(0);") ) {
                hideSubMenu( $li );
            }

        } else {
            showSubMenu( $li );
        }
    };

    var onTouchStart = function(event, $li) {
        event.preventDefault();

        var is_active = $li.hasClass(storage.activeClass);

        if (is_active) {
            hideSubMenu( $li );
        } else {
            var $last_li = $(".flyout-nav > li." +storage.activeClass);
            if ($last_li.length) {
                storage.$last_li = $last_li;
            }
            showSubMenu( $li );
        }
    };

    var showSubMenu = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass),
            has_sub_menu = ( $li.find(".flyout").length ),
            hasPagesClone = 0;

            if (MatchMedia("only screen and (max-width: 768px)")) {
                hasPagesClone = ($li.find(".is-pages").length);

                if(!storage.$departments) {
                    storage.$departments = $li.find(".departments");
                }

                if(!storage.$pagesClone) {
                    storage.$pagesClone = $(".is-pages").clone(true);
                }
            }

        if (is_active) {
            clearTimeout( leave );

        } else {
            if (has_sub_menu) {

                enter = setTimeout( function() {

                    if (storage.$last_li && storage.$last_li.length) {
                        clearTimeout( leave );
                        storage.$last_li.removeClass(storage.activeClass);
                    }

                    $li.addClass(storage.activeClass);
                    toggleMainOrnament(true);
                    if (MatchMedia("only screen and (max-width: 768px)")) {
                        if (!hasPagesClone) {
                            storage.$departments.prepend(storage.$pagesClone);
                        }
                    }
                }, storage.showTime);
            }
        }
    };

    var hideSubMenu = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass);

        if (!is_active) {
            clearTimeout( enter );

        } else {
            storage.$last_li = $li;

            leave = setTimeout(function () {
                $li.removeClass(storage.activeClass);
                toggleMainOrnament(false);
            }, storage.showTime * 2);
        }
    };

    var toggleMainOrnament = function($toggle) {
        var $body = $("body"),
            activeClass = storage.activeShadowClass;

        if ($toggle) {
            $body.addClass(activeClass);
        } else {
            $body.removeClass(activeClass);
        }
    };

    $(document).ready( function() {
        bindEvents();
    });

})(jQuery);

var MatchMedia = function( media_query ) {
    var matchMedia = window.matchMedia,
        is_supported = (typeof matchMedia === "function");
    if (is_supported && media_query) {
        return matchMedia(media_query).matches
    } else {
        return false;
    }
};

$(document).ready(function() {
    const $body = $('body');
    // MOBILE nav slide-out menu
    const $mobileToggle = $('#mobile-nav-toggle');
    const $headerContainer = $('#header-container');
    $mobileToggle.click( function(){
        if (!$('.nav-negative').length) {
            $('.mobile-nav').prepend($('header .apps').clone().removeClass('apps').addClass('nav-negative'));
            $('.mobile-nav').prepend($('header .auth').clone().addClass('nav-negative'));
            $('.mobile-nav').prepend($('header .offline').clone().addClass('nav-negative'));
            $('.mobile-nav').toggleClass('opened').hide().slideToggle(200);
        } else {
            $('.mobile-nav').toggleClass('opened').slideToggle(200);
        }

        if ($headerContainer.hasClass('search-active')) {
            $headerContainer.removeClass('search-active');
        }

        $(this).toggleClass('opened');

        $("html, body").animate({ scrollTop: 0 }, 200);
        return false;
    });

    $(document).on('click', function(e) {
        if (!$mobileToggle.is(e.target) && $mobileToggle.hasClass('opened')) {
            $mobileToggle.removeClass('opened');
            $('.mobile-nav').removeClass('opened').slideUp(200);
        }
    });

    const debounce = (callback, delay = 100) => {
        let timeoutId = null;

        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => callback.apply(null, args), delay);
        };
    };

    const initAppsOverflowMenu = () => {
        const nav = document.querySelector('#globalnav .globalnav-bar nav');
        const navList = nav.querySelector('.apps');

        if (!navList || navList.querySelector('.apps-overflow')) {
            return;
        }

        nav.classList.remove('overflow-hidden');

        const overflowItem = document.createElement('li');
        overflowItem.className = 'apps-overflow';

        const overflowToggle = document.createElement('a');
        overflowToggle.href = '#';
        overflowToggle.className = 'apps-overflow-toggle chevron down';
        overflowToggle.textContent = navList.dataset.moreText;
        overflowToggle.setAttribute('aria-haspopup', 'true');
        overflowToggle.setAttribute('aria-expanded', 'false');

        const overflowMenu = document.createElement('ul');
        overflowMenu.className = 'apps-overflow-menu';

        overflowItem.appendChild(overflowToggle);
        overflowItem.appendChild(overflowMenu);
        navList.appendChild(overflowItem);

        const moveBackToNav = () => {
            while (overflowMenu.firstChild) {
                navList.insertBefore(overflowMenu.firstChild, overflowItem);
            }
        };

        const closeMenu = () => {
            overflowItem.classList.remove('open');
            overflowToggle.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', handleDocumentClick);
            document.removeEventListener('keydown', handleKeyDown);
        };

        const openMenu = () => {
            if (!overflowMenu.children.length) {
                return;
            }

            overflowItem.classList.add('open');
            overflowToggle.setAttribute('aria-expanded', 'true');
            document.addEventListener('click', handleDocumentClick);
            document.addEventListener('keydown', handleKeyDown);

            const firstLink = overflowMenu.querySelector('a, button, [tabindex]');
            if (firstLink) {
                firstLink.focus({ preventScroll: true });
            }
        };

        const handleDocumentClick = (event) => {
            if (!overflowItem.contains(event.target)) {
                closeMenu();
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        };

        overflowToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (overflowItem.classList.contains('open')) {
                closeMenu();
            } else {
                document.querySelectorAll('.apps-overflow.open').forEach((item) => {
                    item.classList.remove('open');
                    item.querySelector('.apps-overflow-toggle')?.setAttribute('aria-expanded', 'false');
                });

                openMenu();
            }
        });

        const updateOverflow = () => {
            closeMenu();
            moveBackToNav();

            if (!navList.contains(overflowItem)) {
                navList.appendChild(overflowItem);
            }

            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            const availableWidth = navList.parentElement ? navList.parentElement.clientWidth : navList.clientWidth;

            overflowItem.style.display = 'none';

            if (isMobile || !availableWidth) {
                return;
            }

            overflowItem.style.display = '';

            const navItems = Array.from(navList.children).filter((li) => li !== overflowItem);

            // пока список шире контейнера — переносим крайний пункт в выпадающее меню
            while (navList.scrollWidth > availableWidth && navItems.length) {
                const item = navItems.pop();
                if (item) {
                    overflowMenu.prepend(item);
                }
            }

            if (!overflowMenu.children.length) {
                overflowItem.style.display = 'none';
            }
        };

        const debouncedUpdate = debounce(updateOverflow, 120);

        const parentForObserve = navList.parentElement || navList;
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(debouncedUpdate);
            resizeObserver.observe(parentForObserve);
        }

        window.addEventListener('resize', debouncedUpdate);
        updateOverflow();
    };

    initAppsOverflowMenu();

    // STICKY CART for non-mobile
    (() => {
        let observer = null;

        const init = () => {
            const cartElement = document.getElementById('cart');
            const headerElement = document.querySelector('.globalheader');

            if (!cartElement || !headerElement) return;

            if (document.querySelector('.cart-summary-page')) return;

            const mediaQuery = window.matchMedia("only screen and (max-width: 768px)");

            const setupObserver = (isMobile) => {
                if (observer) {
                    observer.disconnect();
                    observer = null;
                }

                if (isMobile) {
                    cartElement.classList.remove('fixed');
                    return;
                }

                observer = new IntersectionObserver(
                    (entries) => {
                        const [entry] = entries;

                        if (!entry.isIntersecting && !cartElement.classList.contains('empty')) {
                            cartElement.classList.add('fixed');
                        } else {
                            cartElement.classList.remove('fixed');
                        }
                    },
                    {
                        root: null,
                        threshold: 0,
                        rootMargin: '0px'
                    }
                );

                observer.observe(headerElement);
            };

            setupObserver(mediaQuery.matches);

            mediaQuery.addEventListener('change', (e) => {
                setupObserver(e.matches);
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
});

// MAILER app email subscribe form
var SubscribeSection = ( function($) {

    SubscribeSection = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find("form");
        that.$emailField = that.$wrapper.find(".js-email-field");
        that.$submitButton = that.$wrapper.find(".js-submit-button");

        // VARS
        that.request_uri = options["request_uri"];
        that.locales = options["locales"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    SubscribeSection.prototype.initClass = function() {
        var that = this;

        if (that.request_uri.substr(0,4) === "http") {
            that.request_uri = that.request_uri.replace("http:", "").replace("https:", "");
        }

        var $invisible_captcha = that.$form.find(".wa-invisible-recaptcha");
        if (!$invisible_captcha.length) {
            that.initView();
        }

        that.initSubmit();
    };

    SubscribeSection.prototype.initView = function() {
        var that = this;

        that.$emailField.on("focus", function() {
            toggleView(true);
        });

        $(document).on("click", watcher);

        function watcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                var is_target = $.contains(that.$wrapper[0], event.target);
                if (!is_target) {
                    toggleView(false);
                }
            } else {
                $(document).off("click", watcher);
            }
        }

        function toggleView(show) {
            var active_class = "is-extended";
            if (show) {
                that.$wrapper.addClass(active_class);
            } else {
                var email_value = that.$emailField.val();
                if (!email_value.length) {
                    that.$wrapper.removeClass(active_class);
                } else {

                }
            }
        }
    };

    SubscribeSection.prototype.initSubmit = function() {
        var that = this,
            $form = that.$form,
            $errorsPlace = that.$wrapper.find(".js-errors-place"),
            is_locked = false;

        $form.on("submit", onSubmit);

        function onSubmit(event) {
            event.preventDefault();

            var formData = getData();

            if (formData.errors.length) {
                renderErrors(formData.errors);
            } else {
                request(formData.data);
            }
        }

        /**
         * @return {Object}
         * */
        function getData() {
            var result = {
                    data: [],
                    errors: []
                },
                data = $form.serializeArray();

            $.each(data, function(index, item) {
                if (item.value) {
                    result.data.push(item);
                } else {
                    result.errors.push({
                        name: item.name
                    });
                }
            });

            return result;
        }

        /**
         * @param {Array} data
         * */
        function request(data) {
            if (!is_locked) {
                is_locked = true;

                var href = that.request_uri;

                $.post(href, data, "jsonp")
                    .always( function() {
                        is_locked = false;
                    })
                    .done( function(response) {
                        if (response.status === "ok") {
                            renderSuccess();

                        } else if (response.errors) {
                            var errors = formatErrors(response.errors);
                            renderErrors(errors);
                        }
                    });
            }

            /**
             * @param {Object} errors
             * @result {Array}
             * */
            function formatErrors(errors) {
                var result = [];

                $.each(errors, function(text, item) {
                    var name = item[0];

                    if (name === "subscriber[email]") { name = "email"; }

                    result.push({
                        name: name,
                        value: text
                    });
                });

                return result;
            }
        }

        /**
         * @param {Array} errors
         * */
        function renderErrors(errors) {
            var error_class = "error";

            if (!errors || !errors[0]) {
                errors = [];
            }

            $.each(errors, function(index, item) {
                var name = item.name,
                    text = item.value;

                var $field = that.$wrapper.find("[name=\"" + name + "\"]"),
                    $text = $("<span class='c-error' />").addClass("error");

                if ($field.length && !$field.hasClass(error_class)) {
                    if (text) {
                        $field.parent().append($text.text(text));
                    }

                    $field
                        .addClass(error_class)
                        .one("focus click change", function() {
                            $field.removeClass(error_class);
                            $text.remove();
                        });
                } else {
                    $errorsPlace.append($text);

                    $form.one("submit", function() {
                        $text.remove();
                    });
                }
            });
        }

        function renderSuccess() {
            var $text = that.$wrapper.find(".js-success-message");
            $form.hide();
            $text.show();
        }
    };

    return SubscribeSection;

})(jQuery);
