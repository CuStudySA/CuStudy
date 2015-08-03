$(function(){
	// Hibák kiíratása
	function parse(val) {
	    var result = "Not found",
	        tmp = [];
	    location.search
	    //.replace ( "?", "" )
	    // this is better, there might be a question mark inside
	    .substr(1)
	        .split("&")
	        .forEach(function (item) {
	        tmp = item.split("=");
	        if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
	    });
	    return result;
	}
	function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.substring(1);
	}

	var errortype = parse('errtype');

	if (errortype != 'Not found'){
		var capitalizedProv = '',
			errmessage = parse('err'),
			prov = parse('prov');

		if (errmessage == 'Not found')
			errmessage = 'a távoli szolgáltatónál ismeretlen hiba történt';

		if (prov == 'Not found' || prov == '')
			capitalizedProv = 'távoli';
		else
			capitalizedProv = capitalizeFirstLetter(prov);

		$.Dialog.fail('Sikertelen bejelentkezés távoli szolgáltató segítségével','Nem sikerült bejelentkezni a(z) ' + capitalizedProv + ' szolgáltató segítségével, mert ' + errmessage + '!');
	}

	var $tabItems = $('[tabindex]');
	$tabItems.sort(function(a,b){ return a.tabIndex - b.tabIndex }).last().on('keydown',function(e){
		if (e.keyCode === 9){
			e.preventDefault();
			$tabItems.first().focus();
		}
	});
	var $inner = $('#inner');

	$('#loginform').on('submit',function(e){
		e.preventDefault();
		$inner
			.width($inner.width()+1)
			.height($inner.height()+1)
			.addClass('animate');
		var $form = $(this), title = "Bejelentkezés";

		/* var tempdata = $form.serializeArray(), data = {};
		$.each(tempdata,function(i,el){
			data[el.name] = el.value;
		}); */
		var isIE = navigator.userAgent.toLowerCase().indexOf('trident') !== -1 || navigator.userAgent.toLowerCase().indexOf('msie') !== -1;

		$.ajax({
			method: "POST",
			data: $form.serialize(),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					if (isIE) return window.location.reload();
					$.get('?no-header-js',function(data){ setTimeout(function(){
						var $data = $(data),
							$scripts = $data.filter('script'),
							$styles = $data.filter('link[rel=stylesheet]'),
							$body = $(document.body), $head = $(document.head),
							load = {css: [], js: []};

						$styles.each(function(){
							var a = document.createElement('a');
							a.href = this.href;
							if ($head.children('style[data-href="'+a.pathname+'"]').length === 0)
								load.css.push(a.pathname);
						});

						$scripts.each(function(){
							var a = document.createElement('a');
							a.href = this.src;
							if ($body.children('script[src="'+a.pathname+'"]').length === 0)
								load.js.push(a.pathname);
						});

						function done(){
							$data.filter('#sidebar').prependTo($body);
							$body.addClass('sidebar-slide');
							$('title').text($data.filter('title').text());
							$('main').prepend($data.filter('main').html());
							$('#main').fadeOut(500,function(){
								$(this).remove();
							});
							loadJS(0);
						}

						function loadJS(i){
							if (typeof load.js[i] === 'undefined')
								return;
							$.ajax({
								url: load.js[i],
								dataType: "script",
								success: function(){
									//JS auto. lefut
									loadJS(i+1);
								},
								error: function(){ window.location.reload() }
							});
						}

						(function loadCSS(i){
							if (typeof load.css[i] === 'undefined')
								return done();
							$.ajax({
								url: load.css[i],
								success: function(data){
									if (typeof data !== 'string') return window.location.reload();
									$head.append($(document.createElement('style')).text(data));
									loadCSS(i+1);
								},
								error: function(){ window.location.reload() }
							});
						})(0);
					},500) });
				}
				else {
					$.Dialog.fail(title,data.message);
					$('#inner').removeClass('animate')
				}
			}
		});
	});
});