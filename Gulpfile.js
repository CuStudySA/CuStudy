var cl = console.log;
console.log = console.writeLine = function () {
	var args = [].slice.call(arguments);
	if (args.length && /^(\[\d{2}:\d{2}:\d{2}]|Using|Starting|Finished)/.test(args[0]))
		return;
	return cl.apply(console, args);
};
var stdoutw = process.stdout.write;
process.stdout.write = console.write = function(str){
	var out = [].slice.call(arguments).join(' ');
	if (/\[.*\d.*]/g.test(out)) return;
	stdoutw.call(process.stdout, out);
};

var _sep = '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';
console.writeLine('Gulp modulok betöltése...');
var stuff = [
	'gulp',
	'gulp-sourcemaps',
	'gulp-autoprefixer',
	'gulp-minify-css',
	'gulp-rename',
	'gulp-sass',
	'gulp-uglify',
	'gulp-plumber',
	'gulp-util',
];
console.write('> ');
for (var i= 0,l=stuff.length;i<l;i++){
	var v = stuff[i];
	global[v.replace(/^gulp-([a-z]+).*$/, '$1')] = require(v);
	console.write(' '+v);
}
console.writeLine("\n> OK\n"+_sep);

var workingDir = __dirname;

function Personality(prompt, onerror){
	if (typeof onerror !== 'object' || typeof onerror.length !== 'number' )
		onerror = false;
	var $p = '['+prompt+'] ';
	this.log = function(message){
		console.writeLine($p+message);
	};
	var getErrorMessage = function(){
		return onerror[Math.floor(Math.random()*onerror.length)];
	};
	this.error = function(message){
		if (typeof message === 'string') message = message.trim();
		else console.log(message);
		console.error((onerror?$p+getErrorMessage()+'\n':'')+$p+message);
	};
	return this;
}

var SCSSP = new Personality(
	'sass',
	['HIBA']
);
gulp.task('sass', function() {
	gulp.src('www/resources/sass/*.scss')
		.pipe(plumber(function(err){
			SCSSP.error(err.messageFormatted || err);
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
		.pipe(gulp.dest('www/resources/css'));
});

var JSP = new Personality(
	'js',
	['HIBA']
), JSWatch = [
	'www/resources/js/*.js', '!www/resources/js/*.min.js',
	'www/resources/js/*/*.js', '!www/resources/js/*/*.min.js',
];
gulp.task('js', function(){
	gulp.src(JSWatch)
		.pipe(plumber(function(err){
			err =
				err.fileName
					? err.fileName.replace(workingDir,'')+'\n  line '+err.lineNumber+': '+err.message.replace(/^[\/\\]/,'').replace(err.fileName+': ','')
					: err;
			JSP.error(err);
			this.emit('end');
		}))
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(rename({suffix: '.min' }))
		.pipe(sourcemaps.write('.', {
			includeContent: false,
			sourceRoot: '/resources/js',
		}))
		.pipe(gulp.dest('www/resources/js'));
});

gulp.task('default', ['js', 'sass'], function(){
	gulp.watch(JSWatch, {debounceDelay: 2000}, ['js']);
	JSP.log("Fáljfigyelő aktív");
	gulp.watch('www/resources/sass/*.scss', {debounceDelay: 2000}, ['sass']);
	SCSSP.log("Fáljfigyelő aktív");
});
