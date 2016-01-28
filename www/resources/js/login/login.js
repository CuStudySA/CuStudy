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

	function urldecode(str) {
		return decodeURIComponent((str+'').replace(/\+/g, '%20'));
	}

	function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.substring(1);
	}

	var error = urldecode(parse('error'));
	if (error != 'Not found')
		$.Dialog.fail('Bejelentkezés távoli szolgáltatóval',error);

	// <!-- Végleges eltávolításra jelölve -->
	//if (errortype != 'Not found'){
	//	var capitalizedProv = '',
	//		errmessage = parse('err'),
	//		prov = parse('prov');
//
	//	if (errmessage == 'Not found')
	//		errmessage = 'a távoli szolgáltatónál ismeretlen hiba történt';
//
	//	if (prov == 'Not found' || prov == '')
	//		capitalizedProv = 'távoli';
	//	else
	//		capitalizedProv = capitalizeFirstLetter(prov);
//
	//	$.Dialog.fail('Sikertelen bejelentkezés távoli szolgáltató segítségével','Nem sikerült bejelentkezni a(z) ' + capitalizedProv + ' szolgáltató segítségével, mert ' + errmessage + '!');
	//}

	var $tabItems = $('[tabindex]');
	$tabItems.sort(function(a,b){ return a.tabIndex - b.tabIndex }).last().on('keydown',function(e){
		if (e.keyCode === 9){
			e.preventDefault();
			$tabItems.first().focus();
		}
	});
	var $inner = $('#inner'),
		$loginForm = $('#loginform');


	try {
		var savedUsername = localStorage.getItem('username'),
			$loginInput = $loginForm.find('input[name=username]');

		if (savedUsername){
			$loginInput.val(savedUsername);
			$loginForm.find('input[name=password]').focus();
		}
		else
			$loginInput.focus();
	}
	catch(e){}

	$loginForm.on('submit',function(e){
		e.preventDefault();
		$inner
			.width($inner.width()+1)
			.height($inner.height()+1)
			.addClass('animate');

		var $links = $('#links'),
			$linkLocation = $links.parent();
		$links.detach();

		var $form = $(this), title = "Bejelentkezés";

		var isIE = navigator.userAgent.toLowerCase().indexOf('trident') !== -1 || navigator.userAgent.toLowerCase().indexOf('msie') !== -1,
			formData = $form.serializeForm();

		$.ajax({
			method: "POST",
			data: formData,
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					if ($('[name=remember]').prop('checked')){
						try {
							localStorage.setItem('username', formData.username);
						}
						catch(e){}
					}

					if (isIE) return formData.r ? window.location.href = r : window.location.reload();
					$.ajax({
						url: (formData.r||'')+'?via-js',
						dataType: 'json',
						success: function(data){
							var $body = $(document.body),
								$head = $(document.head),
								load = {css: [], js: []};

							$.each(data.css,function(_,el){
								var a = document.createElement('a');
								a.href = el;
								if ($head.children('style[data-href="'+a.pathname+'"]').length === 0)
									load.css.push(a.pathname);
							});

							$.each(data.js, function(_,el){
								var a = document.createElement('a');
								a.href = el;
								if ($body.children('script[src="'+a.pathname+'"]').length === 0)
									load.js.push(a.pathname);
							});

							function done(){
								$body.prepend(data.sidebar);
								$body.addClass('sidebar-slide');
								$('title').text(data.title);
								if (formData.r) history.replaceState({},'',formData.r);
								$('main').children(':not(#main)').remove()
									.end().prepend(data.main);
								// Amber flag start
								$('link[href*=amber]').remove();
								// Amber flag end
								var $main = $('#main').addClass('loaded');
								setTimeout(function(){ $main.remove() }, 400);
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
									error: function(){ throw new Error('JS #'+i+' - '+load.js[i]) }
								});
							}

							(function loadCSS(i){
								if (typeof load.css[i] === 'undefined')
									return done();
								$.ajax({
									url: load.css[i],
									success: function(data){
										if (typeof data !== 'string')
											return formData.r ? window.location.href = r : window.location.reload();
										data = data.replace(/url\((['"])?\.\.\//g,'url($1/resources/');
										$head.append($(document.createElement('style')).text(data));
										loadCSS(i+1);
									},
									error: function(){ throw new Error('CSS #'+i+' - '+load.css[i]) }
								});
							})(0);
						}
					});
				}
				else {
					$.Dialog.fail(title,data.message);
					$('#inner').removeClass('animate');
					$links.appendTo($linkLocation);
				}
			}
		});
	});

	$('#pw-forgot').on('click',function(e){
		e.preventDefault();

		var title = 'Jelszóvisszaállítás';

		$.Dialog.request(title,"<form id='pw-reset'><p><p>Kérjük adja meg e-mail címét, és küldünk önnek egy<br>linket, melyre kattintva vissza tudja állítani jelszavát.</p><input type='email' name='email' placeholder='E-mail cím' required></label></form>",'pw-reset',function(){
			$('#pw-reset').on('submit',function(e){
				e.preventDefault();

				$.Dialog.wait(title, 'Üzenet küldése');

				$.post('/pw-reset/send',$(this).serializeForm(),function(data){
					if (typeof data !== 'object'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					$.Dialog[data.status?'success':'fail'](title, data.message, true);
				});
			})
		});
	})
});
