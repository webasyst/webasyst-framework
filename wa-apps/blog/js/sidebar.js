( function($) {

    var Sidebar = ( function($) {

        Sidebar = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // CONST

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        Sidebar.prototype.init = function() {
            var that = this;

            that.initCollapse();

            that.initDrafts();
        };

        Sidebar.prototype.initCollapse = function() {
            const that = this;

            const storage_name = "blog/sidebar/collapsed_sections";

            const $sections = that.$wrapper.find("details");

            $sections.each( function() {
                const $section = $(this);
                const section_id = $section.data("id");
                const $toggle = $section.find("summary > span");

                if ($toggle.length) {

                    $toggle.on("click", () => toggle($section));

                    if (section_id) {
                        const is_hidden = isSectionHidden(section_id);
                        $section.attr("open", !is_hidden);
                    }
                }
            });

            function isSectionHidden(section_id) {
                const storage = getStorage();
                return !!(storage[section_id]);
            }

            function toggle($section) {
                const section_id = $section.data("id");
                const is_hidden = !$section.is('[open]');

                const storage = getStorage();

                if (section_id) {
                    if (is_hidden) {
                        delete storage[section_id];
                    } else {
                        storage[section_id] = true;
                    }
                }

                setStorage(storage);
            }

            function setStorage(storage) {
                localStorage.setItem(storage_name , JSON.stringify(storage));
            }

            function getStorage() {
                const storage = localStorage.getItem(storage_name);
                return (storage ? JSON.parse(storage) : {});
            }
        };

        Sidebar.prototype.initDrafts = function() {
            const $section = this.$wrapper.find(".b-drafts-section");

            if ($section.length) {
                $section.on("click", ".js-show-my-drafts", () => $section.toggleClass("is-my", true));

                $section.on("click", ".js-show-all-drafts", () => $section.toggleClass("is-my", false));
            }
        };

        return Sidebar;

    })($);

    $.wa_blog.init.initSidebar = function(options) {
        return new Sidebar(options);
    };

})(jQuery);
