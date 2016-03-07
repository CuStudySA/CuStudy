(function ($, undefined) {
	function paramMake(c,d,o,cb){return{color:c,draggable:typeof d==="undefined"?false:d,overlay: typeof o==="undefined"?true:o,closeButton:typeof cb==="undefined"?false:cb}};
	function $makeDiv(){ return $(document.createElement('div')) }
	var $html = $('html'),
		globalparams = {
			fail: paramMake('lightRed'),
			success: paramMake('darkGreen'),
			wait: paramMake('lightBlue'),
			request: paramMake('lightOrange',true),
			confirm: paramMake('darkOrange'),
			info: paramMake('darkBlue')
		},
		defaultTitles = {
			fail: 'Hiba',
			success: 'Siker',
			wait: 'Kommunikáció a szerverrel',
			request: 'Információkérés',
			confirm: 'Megerősítés',
			info: 'Tájékoztatás',
		},
		defaultContent = {
			fail: 'Hiba történt a kérés feldolgozása közben.',
			success: 'Akármit is csináltál, sikerült.',
			request: 'A kéréshez nem tartozott adat.',
			confirm: 'Biztosan ezt szeretnéd?',
			info: 'Nincs megadva üzenet.',
		},
		xtraCSS = {
			fail: { color: '#E11' },
			success: { color: '#128023' },
			wait: { color: '#4390df' },
			request: { color: '#c29008' },
			confirm: { color: '#bf5a15' },
			info: { color: '#16499a' },
		},
		$w = $(window), $dialogOverlay, $dialogContent, $dialogHeader, $dialogBox, $dialogButtons;
	
	$.Dialog = {
		open: undefined,
		fail: function(title,content,callback,closeBtn){
			$.Dialog.display('fail',title,content,(closeBtn !== false ? {
				'Bezárás': {
					'action': function(){
						$.Dialog.close();
					}
				}
			} : undefined),callback);
		},
		success: function(title,content,closeBtn,callback){
			$.Dialog.display('success',title,content,(closeBtn === true ? {
				'Bezárás': {
					'action': function(){
						$.Dialog.close();
					}
				}
			} : undefined), callback);
		},
		wait: function(title,additional_info,callback){
			if (typeof additional_info === 'function' && callback === 'undefined'){
				callback = additional_info;
			}
			if (typeof additional_info !== 'string' || additional_info.length < 2) additional_info = 'Kérés küldése...';
			$.Dialog.display('wait',title,additional_info[0].toUpperCase()+additional_info.substring(1),callback);
		},
		request: function(title,content,formid,caption,callback){
			if (typeof caption === 'function' && typeof callback === 'undefined'){
				callback = caption;
				caption = 'Elküld';
			}
			var obj = {};
			obj[caption] = {
				'submit': true,
				'form': formid,
			};
			obj['Mégse'] = {
				'action': function(){
					$.Dialog.close();
				},
			};

			$.Dialog.display('request',title,content,obj, callback);
		},
		confirm: function(title,content,btnTextArray,handlerFunc){
			if (typeof btnTextArray === 'function' && typeof handlerFunc === 'undefined')
				handlerFunc = btnTextArray;
			
			if (typeof handlerFunc !== 'function') handlerFunc = new Function();
			
			if (!$.isArray(btnTextArray)) btnTextArray = ['Igen','Nem'];
			var buttonsObj = {};
			buttonsObj[btnTextArray[0]] = {'action': function(){ handlerFunc(true) }};
			buttonsObj[btnTextArray[1]] = {'action': function(){ handlerFunc(false); $.Dialog.close() }};
			$.Dialog.display('confirm',title,content,buttonsObj);
			//displayFunc();
		},
		info: function(title,content,callback){
			$.Dialog.display('info',title,content,{
				'Bezárás': {
					'action': function(){
						$.Dialog.close();
					}
				}
			},callback);
		},
		display: function (type,title,content,buttons,params,callback) {
			if (typeof type !== 'string' || typeof globalparams[type] === 'undefined') throw new TypeError('Invalid dialog type: '+typeof type);
			
			function setFocus(){
				if ($(':focus').length > 0) window._focusedElement = $(':focus').last();
				else window._focusedElement = undefined;
				var $inputs = $('#dialogContent').find('input,select,textarea').filter(':visible'),
					$actions = $('#dialogButtons').children();
				if ($inputs.length > 0) $inputs.first().focus();
				else if ($actions.length > 0) $actions.first().focus();
			}
			
			function run(norender){
				if (norender !== true){
					$dialogOverlay = $makeDiv();
					$dialogContent = $makeDiv();
					$dialogHeader = $makeDiv();
					$dialogBox = $makeDiv();
					$dialogButtons = $makeDiv();
					
					$dialogOverlay.attr('id','dialogOverlay');
					$dialogHeader.attr('id','dialogHeader').text(params.title||defaultTitles[type]);
					$dialogContent.attr('id','dialogContent').addClass('input-control');
					var $contentAdd = $makeDiv().html(params.content);
					if (typeof xtraCSS[type] === 'object') $contentAdd.css(xtraCSS[type]);
					$dialogContent.append($contentAdd);
					$dialogBox.attr({
						'id':'dialogBox',
						'class':'metrouicss',
					}).append($dialogHeader).append($dialogContent);
					if (params.buttons) $dialogButtons = $makeDiv().attr({
						'id':'dialogButtons',
						'class':'metrouicss',
					}).insertAfter($dialogContent);
					$dialogOverlay.appendTo(document.body).css('opacity', .5).show();
					$dialogBox.appendTo($dialogOverlay).css('opacity', 0).css({
						top: $w.height()/3,
						left: ($w.width() - $dialogBox.outerWidth()) / 2,
					});

					setTimeout(function(){
						$dialogBox.css({
							left: ($w.width() - $dialogBox.outerWidth()) / 2,
							top: $w.height()/2 - $dialogBox.outerHeight(),
						}).animate({
							top: ($w.height() - $dialogBox.outerHeight()) / 2,
							opacity: 1,
						}, 350, setFocus);
						$dialogOverlay.fadeTo(350, 1);
					}, 100);

					var hOf = $html.css('overflow');
					$html.attr('data-overflow',hOf).css('overflow','hidden');
				}
				else {
					$dialogOverlay = $('#dialogOverlay');
					$dialogBox = $('#dialogBox');
					$dialogHeader = $('#dialogHeader');
					$dialogContent = $('#dialogContent');
					$dialogButtons = $('#dialogButtons');
					$dialogHeader.text(params.title);
					var $contentAdd = $makeDiv().css({
						'padding-top':'5px',
						'margin-top': '4px',
						'border-top': '1px solid darkgrey',
					});
					if (typeof xtraCSS[type] === 'object') $contentAdd.css(xtraCSS[type]);
					$dialogContent.append($contentAdd.html(params.content));
					if (params.buttons && $dialogButtons.length === 0) $dialogButtons = $makeDiv().attr({
						id:'dialogButtons',
						'class':'metrouicss',
					}).insertAfter($dialogContent);
					else if (!params.buttons && $dialogButtons.length !== 0) $dialogButtons.remove();
					else $dialogButtons.empty();

					$dialogBox.stop().css({
						left: ($w.width() - $dialogBox.outerWidth()) / 2,
						top: ($w.height() - $dialogBox.outerHeight()) / 2,
						opacity: 1,
					});
				}
				
				$dialogHeader.attr('class','bg-color-' + params.color);
				
				if (params.buttons) $.each(params.buttons, function (name, obj) {
					var $button = $(document.createElement('input'));
					$button.attr('type','button');
					$button.attr('class','fg-color-white bg-color-' + params.color);
					if (obj.form){
						var $form = $('#'+obj.form);
						if ($form.length == 1){
							var $submitbtn = $(document.createElement('button')).hide().appendTo($form);
							$button.on('click',function(){
								$submitbtn.trigger('click');
							});
						}
					}
					$button.val(name).on('keydown', function (e) {
						if ([13, 32].indexOf(e.keyCode) !== -1){
							e.preventDefault();
							e.stopPropagation();
							
							$button.trigger('click');
						}
						else if (e.keyCode === 9){
							e.preventDefault();
							e.stopPropagation();
							
							var $dBc = $dialogButtons.children(),
								$focused = $dBc.filter(':focus'),
								$inputs = $dialogContent.find(':input');
								
							if ($focused.length){
								if (!e.shiftKey){
									if ($focused.next().length) $focused.next().focus();
									else $inputs.add($dBc).first().focus();
								}
								else {
									if ($focused.prev().length) $focused.prev().focus();
									else ($inputs.length > 0 ? $inputs : $dBc).last().focus();
								}
							}
							else $inputs.add($dBc)[!e.shiftKey ? 'first' : 'last']().focus();
						}
					}).on('click', function (e) {
						e.preventDefault();
						e.stopPropagation();
						
						if (typeof obj.action === 'function') obj.action(e);

						if (obj.type === 'close') $.Dialog.close(typeof obj.callback === 'function' ? obj.callback : undefined);
					});
					$dialogButtons.append($button);
				});

				setTimeout(function(){
					$dialogBox.css({
						top: ($w.height() - $dialogBox.outerHeight()) / 2,
						left: ($w.width() - $dialogBox.outerWidth()) / 2,
					});
				},100);
				
				if (params.draggable){
					$dialogHeader.css('cursor', 'move').on({
						mousedown: function (e) {
							e.preventDefault();
							e.stopPropagation();
							
							var z_idx = $dialogBox.css('z-index'),
								drg_h = $dialogBox.outerHeight(),
								drg_w = $dialogBox.outerWidth(),
								pos_y = $dialogBox.offset().top + drg_h - e.pageY,
								pos_x = $dialogBox.offset().left + drg_w - e.pageX;

							$dialogOverlay.on("mousemove", function (e) {
								var t = (e.pageY > 0) ? (e.pageY + pos_y - drg_h) : (0);
								var l = (e.pageX > 0) ? (e.pageX + pos_x - drg_w) : (0);

								if (t >= 0 && t <= window.innerHeight + e.pageY) {
									$dialogBox.offset({
										top: t
									});
								}
								if (l >= 0 && l <= window.innerWidth + e.pageY) {
									$dialogBox.offset({
										left: l
									});
								}
							});
						},
						click: function(e){
							e.preventDefault();
							e.stopPropagation();
							
							$dialogOverlay.off("mousemove");
						}
					});
				}
				/* else {
					$dialogHeader.css('cursor','').off('mousedown');
					$dialogOverlay.off("mousemove");
				} */

				setFocus();

				if (typeof callback === 'function') callback();
			}
			
			if (typeof buttons == "function" && typeof params == "undefined" && typeof callback == 'undefined'){
				callback = buttons;
				delete buttons;
			}
			else if (typeof buttons == "object" && typeof params == "function" && typeof callback == 'undefined'){
				callback = params;
				delete params;
			}
			if (typeof params == "undefined") params = {};
			
			if (typeof title === 'undefined') title = defaultTitles[type];
			else if (title === false) title = undefined;
			if (typeof content === 'undefined') content =defaultContent[type];
			params = $.extend(true, {
				type: type,
				title: title,
				content: content,
				buttons: buttons,
			}, globalparams[type], params);

			if (typeof $.Dialog.open == "undefined"){
				$.Dialog.open = params;
				run();
			}
			else run(!!$.Dialog.open);
		},
		close: function (callback) {
			if (typeof $.Dialog.open === "undefined") return (typeof callback == 'function' ? callback() : false);

			$('#dialogOverlay').fadeOut(300, function(){
				$.Dialog.open = void(0);
				$(this).remove();
				if (window._focusedElement instanceof jQuery) window._focusedElement.focus();
				if (typeof callback == 'function') callback();
			});
			$('#dialogBox').animate({
				top: ($w.height()/2)*1.5 - $dialogBox.outerHeight(),
				opacity: 0,
			}, 290);
			var hOf = $html.attr('data-overflow');
			if (typeof hOf !== 'undefined'){
				$html.css('overflow',hOf);
				$html.removeAttr('data-overflow');
			}
		}
	};

	$w.on('resize',function(){
		if (typeof $.Dialog.open !== 'undefined') {
			$dialogBox.css("top", ($w.height() - $dialogBox.outerHeight()) / 2);
			$dialogBox.css("left", ($w.width() - $dialogBox.outerWidth()) / 2);
		}
	}).on('ajaxerror',function(){
		$.Dialog.fail(false,'A kérés küldése közben hiba történt. További információt a fejlesztői konzolban találsz.');
	});
	$(document.body).on('keydown',function(e){
		if (e.keyCode === 9 && typeof $.Dialog.open !== 'undefined'){
			var $this = $(e.target),
				$inputs = $('#dialogContent :input'),
				idx = $this.index('#dialogContent :input');

			if (e.shiftKey && idx === 0){
				e.preventDefault();
				$('#dialogButtons :last').focus();
			}
			else if ($inputs.filter(':focus').length !== 1){
				e.preventDefault();
				$inputs.first().focus();
			}
		}
	});
})(jQuery);