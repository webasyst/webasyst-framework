/**
 * Webasyst Content Source Watcher (CSS)
 */

var gulp = require('gulp'),
    stylus = require('gulp-stylus'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps'),
    nib = require('nib');

var compress_options = {
    drop_debugger: !process.argv.reduce(function(result, arg) {
        return result || (arg === '--debugger');
    }, false)
};

var task_name = "wa-content";

var config = {
    'login-backend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractLoginForm.js',
                './wa-content/js/login/non-minified/backendLogin.js'
            ],
            'target': './wa-content/js/login/'
        },
        // This css file for all backend login actions
        'styl': {
            "directory": "./wa-content/css/login/backend/",
            "sources": "./wa-content/css/login/backend/login-page/*.styl",
            "target": "./wa-content/css/login/backend/login-page/login-page.styl"
        }
    },
    'login-frontend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractLoginForm.js',
                './wa-content/js/login/non-minified/frontendLogin.js'
            ],
            'target': './wa-content/js/login/'
        },
        'styl': {
            "directory": "./wa-content/css/login/frontend/",
            "sources": "./wa-content/css/login/frontend/styl/*.styl",
            "target": "./wa-content/css/login/frontend/styl/login.styl"
        }
    },
    'forgotpassword-backend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractForgotPasswordForm.js',
                './wa-content/js/login/non-minified/backendForgotPassword.js'
            ],
            'target': './wa-content/js/login/'
        }
    },
    'forgotpassword-frontend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractForgotPasswordForm.js',
                './wa-content/js/login/non-minified/frontendForgotPassword.js'
            ],
            'target': './wa-content/js/login/'
        },
        'styl': {
            "directory": "./wa-content/css/login/frontend/",
            "sources": "./wa-content/css/login/frontend/styl/*.styl",
            "target": "./wa-content/css/login/frontend/styl/forgot-password.styl"
        }
    },
    'setpassword-backend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractSetPasswordForm.js',
                './wa-content/js/login/non-minified/backendSetPassword.js'
            ],
            'target': './wa-content/js/login/'
        }
    },
    'setpassword-frontend-form': {
        'js': {
            'sources': [
                './wa-content/js/login/non-minified/abstractForm.js',
                './wa-content/js/login/non-minified/abstractSetPasswordForm.js',
                './wa-content/js/login/non-minified/frontendSetPassword.js'
            ],
            'target': './wa-content/js/login/'
        },
        'styl': {
            "directory": "./wa-content/css/login/frontend/",
            "sources": "./wa-content/css/login/frontend/styl/*.styl",
            "target": "./wa-content/css/login/frontend/styl/set-password.styl"
        }
    },
    'signup-frontend-form': {
        'styl': {
            "directory": "./wa-content/css/signup/",
            "sources": "./wa-content/css/signup/styl/*.styl",
            "target": "./wa-content/css/signup/styl/signup.styl"
        }
    },
    'wa-dropdown': {
        'styl': {
            "directory": "./wa-content/js/dropdown/",
            "sources": "./wa-content/js/dropdown/styl/*.styl",
            "target": "./wa-content/js/dropdown/styl/dropdown.styl"
        }
    },
    'waid': {
        'styl': {
            "directory": "./wa-content/css/wa/waid/",
            "sources": "./wa-content/css/wa/waid/styl/*.styl",
            "target": "./wa-content/css/wa/waid/styl/waid.styl"
        }
    }
};

gulp.task(task_name, function () {
    for (var module_id in config) {
        if (config.hasOwnProperty(module_id)) ( function(source) {
            if ('styl' in source) {
                setStylWatcher(source.styl.directory, source.styl.sources, source.styl.target, module_id, task_name + "-" + module_id + "-css");
            }
            if ('js' in source) {
                setJsWatcher(source.js.sources, source.js.target, module_id, task_name + '-' + module_id + '-js');
            }
        })( config[module_id] );
    }

    function setStylWatcher(directory, sources, target, module_id, task_name) {
        gulp.watch(sources, [ task_name]);
        gulp.task(task_name, function() {
            gulp.src(target)
                .pipe(stylus({
                    use: nib()
                }))
                .pipe(gulp.dest(directory));
        });
    }

    function setJsWatcher(sources, target, module_id, task_name) {
        gulp.watch(sources, [task_name]);
        gulp.task(task_name, function() {
            gulp.src(sources, {base: "./"})
                .pipe(sourcemaps.init())
                .pipe(concat(target + module_id + '.min.js'))
                .pipe(uglify({
                    compress: compress_options
                }))
                .pipe(sourcemaps.write('./', {
                    includeContent: false,
                    sourceRoot: '../../../'
                }))
                .pipe(gulp.dest('./'));
        });
    }
});

module.exports = {
    "task_name": task_name
};