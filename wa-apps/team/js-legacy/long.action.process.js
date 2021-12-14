var LongActionProcess = ( function($) {

    var url = '';
    var process_id = '';
    var step_delay = 500;
    var rest_delay = 750;
    var timers_pull = [];
    var post_data = {};
    var instance = null;    // here will be this

    // List of callbacks
    var onCleanup,
        onReady,
        onProgress,
        onError,
        onWarning,
        onStart,
        onStop,
        onAlways;

    var stopped = false;

    var clearAllTimers = function() {
        while (timers_pull.length > 0) {
            var timer_id = timers_pull.shift();
            if (timer_id) {
                clearTimeout(timer_id);
            }
        }
    };

    var cleanup = function () {
        var data = $.extend(true, {}, post_data);
        data.processId = process_id;
        data.cleanup = 1;
        $.post(
            url,
            data,
            function(r) {
                onCleanup && onCleanup(r);
            }).always(function() {
                clearAllTimers();
            });
    };

    var step = function(delay) {
        if (stopped) {
            return;
        }
        delay = delay || step_delay;
        var timer_id = setTimeout(function() {
            var data = $.extend(true, {}, post_data);
            data.processId = process_id;
            $.post(
                url,
                data,
                function(r) {
                    if (!r) {
                        step(rest_delay);
                    } else if (r.ready) {
                        if (onReady) {
                            onReady.call(instance, r);
                        }
                        cleanup();
                    } else if (r.error) {
                        if (onError) {
                            onError.call(instance, r);
                        }
                    } else if (r.progress) {
                        if (onProgress) {
                            onProgress.call(instance, r);
                        }
                        step();
                    } else if (r.warning) {
                        if (onWarning) {
                            onWarning.call(instance, r);
                        }
                        step();
                    } else {
                        step(rest_delay);
                    }
                    if (onAlways) {
                        onAlways.call(instance, r);
                    }
                },
                'json'
            ).error(function() {
                step(rest_delay);
            });
        }, delay);
        timers_pull.push(timer_id);
    };

    var start = function() {
        onStart && onStart();
        var data = $.extend(true, {}, post_data);
        $.post(url, data,
            function(r) {
                if (r && r.processId) {
                    process_id = r.processId;
                    // invoke runner
                    step(100);
                    // invoke messenger
                    step(200);
                } else if (r && r.error) {
                    if (onError) {
                        onError.call(instance, r)
                    }
                } else {
                    if (onError) {
                        onError.call(instance, 'Server error');
                    }
                }
            }, 'json').error(function() {
                if (onError) {
                    onError.call(instance, 'Server error');
                }
            });
    };

    var stop = function() {
        stopped = true;
        if (onStop) {
            onStop.call(instance)
        }
        clearAllTimers();
    };

    var LongActionProcess = function(options) {
        if (!options.url) {
            throw new Error("Url is required");
        }

        url = options.url;
        step_delay = options.step_delay || step_delay;
        rest_delay = options.rest_delay || rest_delay;
        post_data = options.post_data || post_data;

        // init callbacks
        onCleanup = options.onCleanup;
        onReady = options.onReady;
        onProgress = options.onProgress;
        onError = options.onError;
        onWarning = options.onWarning;
        onStart = options.onStart;
        onStop = options.onStop;
        onAlways = options.onAlways;

        instance = this;

    };

    $.extend(LongActionProcess.prototype, {
        start: start,
        stop: stop
    });

    return LongActionProcess;

})(jQuery);