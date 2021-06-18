/**
 * Shop Sources
 */

var gulp = require('gulp'),
    //css
    stylus = require('gulp-stylus'),
    nib = require('nib'),
    //js
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps');

var app_id = "photos",
    task_name = app_id + "-sources";

var css_sources = {
    "core": {
        "directory": "./wa-apps/photos/css/",
        "sources": "./wa-apps/photos/css/styl/*",
        "result": "./wa-apps/photos/css/styl/photos.styl"
    }
};

var js_sources = {
    // "front-checkout-cart": {
    //     "directory": "./wa-apps/shop/js/frontend/order/",
    //     "sources": "./wa-apps/shop/js/frontend/order/cart.js",
    //     "result_name": "cart.min.js"
    // }
};

gulp.task(task_name, function () {
    // CSS
    for (var css_source_id in css_sources) {
        if (css_sources.hasOwnProperty(css_source_id)) {
            var css_source = css_sources[css_source_id];
            setCSSWatcher(css_source.directory, css_source.sources, css_source.result, app_id + "-" + css_source_id + "-css");
        }
    }

    function setCSSWatcher(directory, sources, result_file, task_name) {
        gulp.watch(sources, [task_name]);
        gulp.task(task_name, function() {
            //process.stdout.write(source_file);
            gulp.src(result_file)
                .pipe(stylus({
                    use: nib(),
                    compress: true
                }))
                .pipe(gulp.dest(directory));
        });
    }

    // JS
    for (var js_source_id in js_sources) {
        if (js_sources.hasOwnProperty(js_source_id)) {
            var js_source = js_sources[js_source_id];
            setJSWatcher(js_source.directory, js_source.sources, js_source.result_name, app_id + "-" + js_source_id + "-js");
        }
    }

    function setJSWatcher(directory, sources, result_name, task_name) {
        gulp.watch(sources, [task_name]);
        gulp.task(task_name, function() {
            gulp.src(sources)
                .pipe(sourcemaps.init())
                .pipe(concat(directory + result_name))
                .pipe(uglify())
                .pipe(sourcemaps.write("./", {
                    includeContent: false,
                    sourceRoot: directory
                }))
                .pipe(gulp.dest("./"));
        });
    }
});

module.exports = {
    "task_name": task_name
};