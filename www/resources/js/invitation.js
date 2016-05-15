$(function(){
	var $Form = $('form'),
		title = 'Regisztráció a rendszerbe meghívóval',
		$loginMain = $('#main').appendTo('body'),
		isIE = navigator.userAgent.toLowerCase().indexOf('trident') !== -1 || navigator.userAgent.toLowerCase().indexOf('msie') !== -1;

	// Patternek hozzácsatolása az űrlapelemekhez
	if (typeof Patterns != undefined){
		$.each(Patterns,function(key,value){
			if (key != 'message' && key != 'status'){
				var $patternInput = $Form.find('[name=' + key + ']');

				if ($Form.length)
					$patternInput.attr('pattern',value);
			}
		});
	}

	function goToMainpage(){
		$.Dialog.close();

		if (isIE) return window.location.href = '/';
		$.ajax({
			url: '/?via-js',
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
					history.replaceState({},'','/');
					var $main = $('main');
					$main.children(':not(#main)').remove();
					$main.append(data.main);
					$loginMain.addClass('loaded');
					setTimeout(function(){ $loginMain.remove() }, 410);
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
								return window.location.href = '';
							data = data.replace(/url\((['"])?\.\.\//g,'url($1/resources/');
							$head.append($.mk('style').text(data));
							loadCSS(i+1);
						},
						error: function(){ throw new Error('CSS #'+i+' - '+load.css[i]) }
					});
				})(0);
			}
		});
	}

	// baseDataForm elküldése esetén...
	$Form.on('submit',function(e){
		e.preventDefault();

		if ($Form.find('[name=password]').val() != $Form.find('[name=verpasswd]').val()){
			$Form.find('[name=password]').val('');
			$Form.find('[name=verpasswd]').val('');

			return $.Dialog.fail(title,'A megadott jelszavak nem egyeznek meg. Kérjük gépelje be őket újra!');
		}

		var data = $Form.serializeForm();
		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/invitation/registration',
			data: data,
			success: function(data){
				if (data.status){
					$('#contentDiv').html(data.html);

					$Form = $('form');
					$Form.on('submit',function(e){
						e.preventDefault();

						var data = $Form.serializeForm();
						$.Dialog.wait(title);

						$.ajax({
							method: 'POST',
							url: '/invitation/setGroupMembers',
							data: data,
							success: function(data){
								if (!data.status) return $.Dialog.fail(title,data.message);

								goToMainpage();
							}
						});
					});

					$.Dialog.close();
				}
				else if (data.nogroup !== true) $.Dialog.fail(title,data.message);
				else goToMainpage();
			}
		});
	});
});
