$(function(){
	// Hibák kiíratása
	displayError('Bejelentkezés távoli szolgáltatóval');

	var $tabItems = $('[tabindex]');
	$tabItems.sort(function(a,b){ return a.tabIndex - b.tabIndex }).last().on('keydown',function(e){
		if (e.keyCode === 9){
			e.preventDefault();
			$tabItems.first().focus();
		}
	});
	var $loginInner = $('#inner'),
		$loginMain = $('#main'),
		$loginForm = $('#loginform');

	try {
		var savedUsername = localStorage.getItem('username'),
			$loginInput = $loginForm.find('input[name=username]');

		if (savedUsername){
			$loginInput.val(savedUsername);
			$loginForm.find('input[name=password]').focus();
		}
		else $loginInput.focus();
	}
	catch(e){}

	$('#heading').find('.help-link').on('click',function(){
		var $links = $('#links').children().clone().addClass('btn');
		$.Dialog.info('Segítség', $.mk('div').addClass('align-center').append($links.eq(0),' ',$links.eq(1)));
	});

	var isIE = navigator.userAgent.toLowerCase().indexOf('trident') !== -1 || navigator.userAgent.toLowerCase().indexOf('msie') !== -1;
	$loginForm.on('submit',function(e){
		e.preventDefault();
		$loginInner
			.width($loginInner.width()+1)
			.height($loginInner.height()+1)
			.addClass('animate');

		var $links = $('#links'),
			$linkLocation = $links.parent();
		$links.detach();

		var $form = $(this),
			title = "Bejelentkezés",
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

					successfulLogin(formData);
				}
				else {
					$('#inner').removeClass('animate');
					$links.appendTo($linkLocation);

					if (data.twofa){
						let $2FALogin = $.mk('form').attr('id','twofa-login').append(
							`<p>A fiókodban engedélyezve van a kétfaktoros azonosítás. Kérlek, add meg az alkalmazás által generált kódot (vagy egy tartalék kódot).</p>
							<label>
								<input type="text" name="code" minlength="6" maxlength="8" pattern="^(\\d{6}|[${data.twofa}]{8})$" title="6 számjegyú azonosító kód vagy 8 karakteres tartalék kód" required autocomplete="off">
							</label>
							<p>Ha nem tudsz többé hozzáférni a hordozható eszközhöz és kifogytál a tartalék kódokból, vedd fel a kapcsolatot az ügyfélszolgálattal.</p>`
						);
						$.Dialog.request(title,$2FALogin,'twofa-login','Elküldés',function($form){
							$form.on('submit',function(e){
								e.preventDefault();

								let twofa = $form.serializeForm();
								formData.code = twofa.code;
								$.Dialog.wait(false, 'Komunikáció a szerverrel, kis türelmet');

								$.ajax({
									method: "POST",
									data: formData,
									success: function(data){
										if (typeof data === 'string'){
											console.log(data);
											$(window).trigger('ajaxerror');
											return false;
										}

										if (!data.status){
											$form.find('input').val('');
											return $.Dialog.fail(false, data.message);
										}

										$.Dialog.close();
										$('#inner').addClass('animate');
										$links.detach();
										successfulLogin(formData);
									}
								});
							});
						});
					}
					else {
						$.Dialog.fail(title,data.message);
						$('input[name=password]').val('');
					}
				}
			}
		});
	});
	function successfulLogin(formData){
		if (isIE){
			try{ console.log('Internet Explorer érzékelve, átirányítás...'); }catch(_){}
			return formData.r ? window.location.href = formData.r : window.location.reload();
		}
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
					$body.prepend(data.sidebar).addClass('sidebar-slide');
					$('title').text(data.title);
					history.replaceState({},'',formData.r ? formData.r : '/');
					$('main').children(':not(#main)').remove();
					$.mk('main').append(data.main).appendTo($body);
					$loginMain.addClass('loaded');
					if (data.mobile_header)
						$(data.mobile_header).insertBefore('#sidebar');
					else $('#mobile-header').remove();
					setTimeout(function(){
						$body.children('div:not(#sidebar):not(#main):not(#mobile-header):not(#heading), #underDevelopment').remove();
						$loginMain.remove();
					}, 410);
					setTimeout(function(){
						$('link[href*="login.css"], #heading').remove();
						$body.removeClass('sidebar-slide');
					},2000);
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
						dataType: "text",
						success: function(data){
							if (typeof data !== 'string')
								return formData.r ? window.location.href = r : window.location.reload();
							data = data.replace(/url\((['"])?\.\.\/\.\.\//g,'url($1/resources/');
							$head.append($.mk('style').text(data));
							loadCSS(i+1);
						},
						error: function(){ throw new Error('CSS #'+i+' - '+load.css[i]) }
					});
				})(0);
			}
		});
	}

	$('#pw-forgot').on('click',function(e){
		e.preventDefault();

		var title = 'Jelszóvisszaállítás';

		$.Dialog.request(title,"<form id='pw-reset'><p><p>Kérjük adja meg e-mail címét, és küldünk önnek egy linket, melyre kattintva vissza tudja állítani jelszavát.</p><input type='email' name='email' placeholder='E-mail cím' required></label></form>",'pw-reset',function($urlap){
			$urlap.on('submit',function(e){
				e.preventDefault();

				var data = $urlap.serializeForm();
				$.Dialog.wait(title, 'Üzenet küldése');

				$.post('/pw-reset/send',data,function(data){
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
