// jshint ignore: start
var cl = console.log,
	chalk = require('chalk');
console.log = console.writeLine = function () {
	var args = [].slice.call(arguments), match;
	if (args.length){
		if (/^(\[\d{2}:\d{2}:\d{2}]|Using|Finished)/.test(args[0]))
			return;
		else if (args[0] == 'Starting' && (match = args[1].match(/^'.*((?:dist-)?js|scss|default|md).*'...$/))){
			args = ['[' + chalk.green('gulp') + '] ' + match[1] + ': ' + chalk.magenta('start')];
		}
	}
	return cl.apply(console, args);
};
var stdoutw = process.stdout.write;
process.stdout.write = console.write = function(str){
	var out = [].slice.call(arguments).join(' ');
	if (/\[.*?\d{2}.*?:.*?]/.test(out))
		return;
	stdoutw.call(process.stdout, out);
};

var toRun = process.argv.slice(2).slice(-1)[0] || 'default'; // Works only if task name is the last param
console.writeLine('Starting Gulp task "'+toRun+'"');
var require_list = ['gulp'];
if (['js','scss','default'].indexOf(toRun) !== -1){
	require_list.push.apply(require_list, [
		'gulp-plumber',
		'gulp-duration',
		'gulp-sourcemaps',
		'gulp-rename',
	]);

	if (toRun === 'scss' || toRun === 'default')
		require_list.push.apply(require_list, [
			'gulp-sass',
			'gulp-autoprefixer',
			'gulp-minify-css',
		]);
	if (toRun === 'js' || toRun === 'default')
		require_list.push.apply(require_list, [
			'gulp-uglify',
			'gulp-babel',
			'gulp-cached'
		]);
}
console.write('(');
for (var i= 0,l=require_list.length; i<l; i++){
	var v = require_list[i];
	global[v.replace(/^gulp-([a-z]+).*$/, '$1')] = require(v);
	console.write(' '+v);
}
console.writeLine(" )\n");

var workingDir = __dirname;

function Logger(prompt){
	var $p = '['+chalk.blue(prompt)+'] ';
	this.log = function(message){
		console.writeLine($p+message);
	};
	this.error = function(message){
		if (typeof message === 'string'){
			message = message.trim()
				.replace(/[\/\\]?www/,'');
			console.error($p+'Hiba: '+message);
		}
		else console.log(JSON.stringify(message,null,'4'));
	};
	return this;
}

var SASSL = new Logger('scss');
gulp.task('scss', function() {
	gulp.src('www/resources/sass/*.scss')
		.pipe(plumber(function(err){
			SASSL.error('A(z) '+err.relativePath+'\n'+' fájl '+err.line+'. sorában: '+err.messageOriginal);
			this.emit('end');
		}))
		.pipe(sourcemaps.init())
			.pipe(sass({
				outputStyle: 'expanded',
				errLogToConsole: true,
			}))
			.pipe(autoprefixer('last 2 version'))
			.pipe(rename({suffix: '.min' }))
			.pipe(minify({
				processImport: false,
				compatibility: '-units.pc,-units.pt'
			}))
		.pipe(sourcemaps.write('.', {
			includeContent: false,
			sourceRoot: '/resources/sass',
		}))
		.pipe(duration('scss'))
		.pipe(gulp.dest('www/resources/css'));
});

var JSL = new Logger('js'),
	JSWatch = [
		'www/resources/js/*.js', '!www/resources/js/*.min.js',
		'www/resources/js/*/*.js', '!www/resources/js/*/*.min.js',
	];
gulp.task('js', function(){
	gulp.src(JSWatch)
		.pipe(duration('js'))
		.pipe(cached('js', { optimizeMemory: true }))
		.pipe(plumber(function(err){
			err =
				err.fileName
				? 'A(z) '+err.fileName.replace(workingDir,'')+'\n  fájl '+(
					err._babel === true
					? err.loc.line
					: err.lineNumber
				)+'. sorában: '+err.message.replace(/^[\/\\]/,'')
				                  .replace(err.fileName.replace(/\\/g,'/')+': ','')
				                  .replace(/\(\d+(:\d+)?\)$/, '')
				: err;
			JSL.error(err);
			this.emit('end');
		}))
		.pipe(sourcemaps.init())
			.pipe(babel({
				presets: ['es2015']
			}))
			.pipe(rename({suffix: '.min' }))
			.pipe(uglify({
				preserveComments: function(_, comment){ return /^!/m.test(comment.value) },
			}))
		.pipe(sourcemaps.write('.', {
			includeContent: false,
			sourceRoot: '/resources/js',
		}))
		.pipe(gulp.dest('www/resources/js'));
});

gulp.task('default', ['js', 'scss'], function(){
	gulp.watch(JSWatch, {debounceDelay: 2000}, ['js']);
	JSL.log("Fáljfigyelő aktív");
	gulp.watch('www/resources/sass/*.scss', {debounceDelay: 2000}, ['scss']);
	SASSL.log("Fáljfigyelő aktív");
});
