/**
 * UI App
 */

var gulp = require('gulp'),
    stylus = require('gulp-stylus'),
    nib = require('nib'),
    task_name = "ui-app-watch";

var css_sources = {
    "core": {
        "css_dir": "./wa-apps/ui/css/",
        "styl_files": "./wa-apps/ui/css/styl/*.styl",
        "source_file": "./wa-apps/ui/css/styl/ui.styl"
    }
};

gulp.task(task_name, function () {
    for (var source_id in css_sources) {
        if (css_sources.hasOwnProperty(source_id)) ( function( source ) {
            setWatcher(source.css_dir, source.styl_files, source.source_file, "ui" + "-" + source_id + "-css");
        })( css_sources[source_id] );
    }

    function setWatcher(css_dir, styl_files, source_file, task_name) {
        gulp.watch(styl_files, [task_name]);
        gulp.task(task_name, function() {
            //process.stdout.write(source_file);
            gulp.src(source_file)
                .pipe(stylus({
                    use: nib(),
                    compress: true
                }))
                .pipe(gulp.dest(css_dir));
        });
    }
});

module.exports = {
    "task_name": task_name
};