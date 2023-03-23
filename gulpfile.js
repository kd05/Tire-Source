
var gulp = require('gulp');
var concat = require('gulp-concat');
var minify = require('gulp-minify');
// var sourcemaps = require('gulp-sourcemaps');

gulp.task('build', function(){
    return gulp.src('core/js/*.js')
        // .pipe(sourcemaps.init())
        .pipe(concat('main.js'))
        .pipe(minify({
            ext:{
                src:'-dev.js',
                min:'.min.js'
            },
        }))
        // .pipe(sourcemaps.write())
        .pipe(gulp.dest('build'))
});

gulp.task('watch', function(){
    gulp.watch('core/js/*.js', gulp.series('build'));
});



