$(function(){
	var $Form = $('form'),
		title = 'Regisztráció a rendszerbe meghívóval';

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
		var $inner = $('#inner');

		$.Dialog.close();

		$inner
			.width($inner.width()+1)
			.height($inner.height()+1)
			.addClass('animate');

		$.get('/fooldal?no-header-js',function(data){ setTimeout(function(){
			var $data = $(data),
				$scripts = $data.filter('script[src]'),
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
				history.pushState('', {}, '/');
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
					error: function(){ window.location.href = '/'; }
				});
			}

			(function loadCSS(i){
				if (typeof load.css[i] === 'undefined')
					return done();
				$.ajax({
					url: load.css[i],
					success: function(data){
						if (typeof data !== 'string') return window.location.href = '/';
						$head.append($(document.createElement('style')).text(data));
						loadCSS(i+1);
					},
					error: function(){ window.location.href = '/'; }
				});
			})(0);
		},500) });
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
								if (data.status){
									goToMainpage();
								}
								else $.Dialog.fail(title,data.message);
							}
						});
					});

					$.Dialog.close();
				}
				else if (data.message != 'nogroup') $.Dialog.fail(title,data.message);
				else goToMainpage();
			}
		});
	});
});
