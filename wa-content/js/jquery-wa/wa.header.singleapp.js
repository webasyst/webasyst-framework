if (typeof WaHeaderSingleApp === 'undefined') {

window.WaHeaderSingleApp = (function() {

class WaHeaderSingleApp {
    constructor() {
        // Variables
        let that = this
        that.is_idle = true

        // update counts immediately if there are no cached counts; otherwise, update later

        $(document).on("mousemove keyup scroll", function() {
            that.is_idle = false;
        });

        document.addEventListener("touchmove", function () {
            that.is_idle = false;
        }, false);

        setInterval(this.updateCount.bind(this), 60000);
    }

    /**
     * @description Update Apps action counter value
     */
    updateCount() {
        let that = this;

        const data = {
            background_process: 1
        };

        if (that.is_idle) {
            data.idle = "true";
        } else {
            that.is_idle = true;
        }

        return $.ajax({
            url: backend_url + "?action=count",
            data,
            success(response) {
                if (response && response.status == 'ok') {
                    $(document).trigger('wa.appcount', response.data);
                }
            },
            error(response) {
                console.error(response);
            },
            dataType: "json",
            async: true
        });
    }
}

return WaHeaderSingleApp;

}());

}