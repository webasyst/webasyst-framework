const {
    src,
    dest,
    parallel,
    watch
} = require('gulp');

// Load plugins

const uglify = require('gulp-uglify-es').default;
const rename = require('gulp-rename');
const stylus = require('gulp-stylus');
const autoprefixer = require('gulp-autoprefixer');
const concat = require('gulp-concat');
const nib = require('nib');
const babel = require('gulp-babel');
const cssnano = require('gulp-cssnano');

// options for uglify `compress`
const compressOptions = {
    drop_debugger: !process.argv.reduce(function (result, arg) {
        return result || (arg === '--debugger');
    }, false)
};

const js_sources = [
    ['js/compiled/site.min.js', [

        'js/editor/vue/vue.global.js',
        'js/editor/vue/vue.i18n.js',
        'js/site.js',
        'js/map/personal/settings.js',
        'js/map/personal/settingsFormConstructor.js',
        'js/editor/SiteEditor.js',
        'js/editor/form/FormConstructor.js',
        'js/editor/BlockSettingsDrawer.js',
        'js/editor/BlockStorage.js',
        'js/editor/UndoRedoQueue.js',
        'js/editor/form/vue.data.js',
        'js/editor/form/vue.templates.js',
        'js/editor/form/components/vue.components.js',
        'js/editor/form/vue.translation.js',

    ]],

    ['js/compiled/site.editor.defer.min.js', [
        'js/editor/SiteEditorInsideIframe.js'
    ]],
];

// JS function

function js(source, output) {

    var out_parts = output.split('/');
    var basename = out_parts.slice(-1)[0];
    var outdir = out_parts.slice(0, -1).join('/') + '/';
    return js;

    function js() {
        return src(source, { allowEmpty: true })
            .pipe(concat(basename.replace('.min.js', '.js')))
            .pipe(uglify({
                compress: compressOptions
            }))
            .pipe(rename({
                extname: '.min.js'
            }))
            .pipe(dest(outdir));
    };
}

function allJs() {
    var jobs = js_sources.map((p) => {
        return js(p[1], p[0]);
    });
    return parallel.apply(this, jobs);
}

// CSS function

function css() {
    const source = 'css/styl/site.styl';

    return src(source)
        .pipe(stylus({
            use: nib(),
            compress: true
        }))
        .pipe(autoprefixer({
            overrideBrowserslist: ['last 2 versions'],
            cascade: false
        }))
        .pipe(rename({
            extname: '.min.css'
        }))
        .pipe(dest('./css/'));
}

// Watch files

function watchFiles() {
    watch('css/styl/**/*.styl', css);
    
    js_sources.forEach((p) => {
        watchJs(p[1], p[0]);
    });

    function watchJs(files, output) {
        watch(files, js(files, output));
    }
}


exports.watch = watchFiles;
exports.default = parallel(allJs(), css);