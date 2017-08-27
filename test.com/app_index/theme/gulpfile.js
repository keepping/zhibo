//npm install gulp
//npm install gulp-less gulp-minify-css gulp-concat gulp-notify gulp-livereload gulp-rename --save-dev   gulp 实现less转化监听合并和压缩
//npm install --save-dev gulp del vinyl-paths
//npm install gulp-sourcemaps --save-dev 生成less对应地图
// 引入 gulp
var gulp = require('gulp');
 
// 引入组件
var 
    less = require('gulp-less'),//less 文件转换
    minifycss = require('gulp-minify-css'),//css压缩
    concat = require('gulp-concat'),//文件合并
    rename = require('gulp-rename'),//文件更名
    notify = require('gulp-notify'),//提示信息
    livereload = require('gulp-livereload'),//网页自动刷新
    uglify = require('gulp-uglify'),//js压缩
    clean = require('gulp-clean'),
    imagemin = require('gulp-imagemin'),//图片压缩
    pngcrush = require('imagemin-pngcrush');

 gulp.task('clean', function() {  
  return gulp.src(['dist/assets/css', 'dist/assets/js', 'dist/assets/img'], {read: false})
    .pipe(clean());
});


// 压缩图片
gulp.task('img', function() {
  return gulp.src('src/images/*')
    .pipe(imagemin({
        progressive: true,
        svgoPlugins: [{removeViewBox: false}]
    }))
    .pipe(gulp.dest('dist/statics/images/'))
    .pipe(notify({ message: 'img task ok' }));
});

//转换less文件   
gulp.task('less', function() {
    return gulp.src('src/less/*.less')//该任务针对的文件
        .pipe(less())//该任务调用的模块
        .pipe(concat('main.css'))
        .pipe(gulp.dest('dist/statics/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(minifycss())
        .pipe(gulp.dest('dist/statics/css'))
        .pipe(notify({ message: 'css task ok' }));
});

//转换less文件   
gulp.task('css', function() {
    return gulp.src('src/css/*.css')//该任务针对的文件
        .pipe(concat('main.css'))
        .pipe(gulp.dest('dist/statics/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(minifycss())
        .pipe(gulp.dest('dist/statics/css'))
        .pipe(notify({ message: 'css task ok' }));
});



// 检查js
/*gulp.task('lint', function() {
  return gulp.src('src/js/*.js')
    .pipe(jshint())
    .pipe(jshint.reporter('default'))
    .pipe(notify({ message: 'lint task ok' }));
});*/

//压缩，合并 模块js
gulp.task('js', function() {
    return gulp.src('src/js/modules/*.js')      //需要操作的文件
        .pipe(concat('main.js'))    //合并所有js到main.js
        .pipe(gulp.dest('dist/statics/js'))       //输出到文件夹
        .pipe(rename({suffix: '.min'}))   //rename压缩后的文件名
        .pipe(uglify())    //压缩
        .pipe(gulp.dest('dist/statics/js'))  //输出
        .pipe(notify({ message: 'js task ok' }));
});
//压缩，合并 公用js
gulp.task('public_js', function() {
    return gulp.src('src/js/public/*.js')      //需要操作的文件
        .pipe(concat('public.js'))    //合并所有js到main.js
        .pipe(gulp.dest('dist/statics/js'))       //输出到文件夹
        .pipe(rename({suffix: '.min'}))   //rename压缩后的文件名
        .pipe(uglify())    //压缩
        .pipe(gulp.dest('dist/statics/js'))  //输出
        .pipe(notify({ message: 'js task ok' }));
});

// 第三方框架移植
gulp.task('frames_js',function() {
    return gulp.src(['src/frames/**/*'])// 该任务针对的文件
        .pipe(gulp.dest('dist/frames'))
        .pipe( notify({
            message: 'frames_js task ok'
        }));
});

// 组件样式
gulp.task('components_less', function() {
    return gulp.src('src/components/**/*.less')//该任务针对的文件
        .pipe(less())
        .pipe(minifycss())
        .pipe(gulp.dest('dist/components/'))
    .pipe(notify({ message: 'components_less task ok' }));
});
// 组件脚本
gulp.task('components_js', function() {
    return gulp.src('src/components/**/*.js')//该任务针对的文件
        // .pipe(uglify())
        .pipe(gulp.dest('dist/components/'))
        .pipe(notify({ message: 'components_js task ok' }));
});
// 组件HTML
gulp.task('components_html', function() {
    return gulp.src('src/components/**/*.html')//该任务针对的文件
        .pipe(gulp.dest('dist/components/'))
        .pipe(notify({ message: 'components_html task ok' }));
});

// 默认任务
gulp.task('default', function(){
    gulp.run('img', 'less', 'css', 'components_less', 'components_js', 'components_html', 'js', 'public_js', 'frames_js');
	// livereload.listen();
    gulp.watch('src/less/*.less', ['less']);
    gulp.watch('src/components/**/*.less', ['components_less']);
    gulp.watch('src/components/**/*.js', ['components_js']);
    gulp.watch('src/components/**/*.html', ['components_html']);
    gulp.watch('src/js/modules/*.js', ['js']);
    gulp.watch('src/js/public/*.js', ['public_js']);
    gulp.watch('src/frames/**/*', ['frames_js']);
	gulp.watch('src/css/*.css', ['css']);

});

// gulp.task('default', ['clean'], function(){
//     gulp.start('less', 'js', 'watch');
// });