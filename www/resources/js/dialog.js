/* globals $body,Key,$w */
(function ($, undefined) {
	'use strict';
	function $makeDiv(id){ return $.mk('div').attr('id', id) }
	var colors = {
			fail: 'red',
			success: 'green',
			wait: 'blue',
			request: '',
			confirm: 'orange',
			info: 'darkblue'
		},
		noticeClasses = {
			fail: 'fail',
			success: 'success',
			wait: 'info',
			request: 'warn',
			confirm: 'caution',
			info: 'info',
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
		$dialogOverlay = $('#dialogOverlay'),
		$dialogContent = $('#dialogContent'),
		$dialogHeader = $('#dialogHeader'),
		$dialogBox = $('#dialogBox'),
		$dialogButtons = $('#dialogButtons');

	$.Dialog = (function(){
		var _open = $dialogContent.length ? {} : undefined,
			Dialog = {
				isOpen: function(){ return typeof _open === 'object' },
			},
			CloseButton = { 'Bezárás': function(){ Close() } };

		// Dialog defintions
		Dialog.fail = function(title,content,force_new){
			Display('fail',title,content,CloseButton,Boolean(force_new));
		};
		Dialog.success = function(title,content,closeBtn,callback){
			Display('success',title,content, (closeBtn === true ? CloseButton : undefined), callback);
		};
		Dialog.wait = function(title,additional_info,force_new){
			if (typeof additional_info === 'boolean' && force_new === 'undefined'){
				force_new = additional_info;
				additional_info = undefined;
			}
			if (typeof additional_info !== 'string')
				additional_info = 'Sending request';
			Display('wait',title,$.capitalize(additional_info)+'&hellip;',force_new);
		};
		Dialog.request = function(title,content,formid,confirmBtn,callback){
			if (typeof confirmBtn === 'function' && typeof callback === 'undefined'){
				callback = confirmBtn;
				confirmBtn = undefined;
			}
			var buttons = {};
			if (formid)
				buttons[confirmBtn||'Submit'] = {
					'submit': true,
					'form': formid,
				};
			buttons['Mégse'] = function(){ Close() };

			Display('request',title,content,buttons,callback);
		};
		Dialog.confirm = function(title,content,btnTextArray,handlerFunc){
			if (typeof btnTextArray === 'function' && typeof handlerFunc === 'undefined')
				handlerFunc = btnTextArray;

			if (typeof handlerFunc !== 'function')
				handlerFunc = function(){ Close() };
			
			if (!$.isArray(btnTextArray)) btnTextArray = ['Igen','Nem'];
			var buttons = {};
			buttons[btnTextArray[0]] = function(){ handlerFunc(true) };
			buttons[btnTextArray[1]] = function(){ handlerFunc(false); Close.call() };
			Display('confirm',title,content,buttons);
		};
		Dialog.info = function(title,content,callback){
			Display('info',title,content,CloseButton,callback);
		};

		// Storing and restoring focus
		var _$focusedElement;
		Dialog.setFocusedElement = function($el){
			if ($el instanceof jQuery)
				_$focusedElement = $el;
		};
		function _storeFocus(){
			if (typeof _$focusedElement !== 'undefined' && _$focusedElement instanceof jQuery)
				return;
			var $focus = $(':focus');
			_$focusedElement = $focus.length > 0 ? $focus.last() : undefined;
		}
		function _restoreFocus(){
			if (typeof _$focusedElement !== 'undefined' && _$focusedElement instanceof jQuery){
				_$focusedElement.focus();
				_$focusedElement = undefined;
			}
		}
		function _setFocus(){
			var $inputs = $('#dialogContent').find('input,select,textarea').filter(':visible'),
				$actions = $('#dialogButtons').children();
			if ($inputs.length > 0) $inputs.first().focus();
			else if ($actions.length > 0) $actions.first().focus();
		}

		var DISABLE = true,
			ENABLE = false;
		function _controlInputs(action){
			var $inputs = $dialogContent
				.children(':not(#dialogButtons)')
				.last()
				.find('input, select, textarea');

			if (action === DISABLE)
				$inputs.filter(':not(:disabled)').addClass('temp-disable').attr('disabled',DISABLE);
			else $inputs.filter('.temp-disable').removeClass('temp-disable').attr('disabled',ENABLE);
		}

		var _closeTimeout;

		// Displaying dialogs
		function Display(type,title,content,buttons,callback) {
			if (typeof type !== 'string' || typeof colors[type] === 'undefined')
				throw new TypeError('Invalid dialog type: '+typeof type);

			if (typeof _closeTimeout !== 'undefined'){
				clearTimeout(_closeTimeout);
				_closeTimeout = undefined;
			}
			if (typeof buttons === 'function' && typeof callback !== 'function'){
				callback = buttons;
				buttons = undefined;
			}
			var force_new = false;
			if (typeof buttons === 'boolean' && typeof callback === 'undefined'){
				force_new = true;
				buttons = undefined;
			}
			if (typeof callback === 'boolean'){
				force_new = true;
				callback = undefined;
			}

			if (typeof title === 'undefined')
				title = defaultTitles[type];
			else if (title === false)
				title = undefined;
			if (typeof content !== 'string' && !(content instanceof jQuery))
				content = defaultContent[type];
			var params = {
				type: type,
				title: title,
				content: content||defaultContent[type],
				buttons: buttons,
				color: colors[type]
			};

			var append = Boolean(_open),
				$contentAdd = $makeDiv().append(params.content),
				appendingToRequest = append && _open.type === 'request' && ['fail','wait'].includes(params.type),
				$requestContentDiv;

			if (params.color.length)
				$contentAdd.addClass(params.color);
			if (append){
				$dialogOverlay = $('#dialogOverlay').css('opacity', 1);
				$dialogBox = $('#dialogBox').css({ top: 0, opacity: 1 });
				$dialogHeader = $('#dialogHeader');
				if (typeof params.title === 'string')
					$dialogHeader.text(params.title);
				$dialogContent = $('#dialogContent');

				if (appendingToRequest && !force_new){
					$requestContentDiv = $dialogContent.children(':not(#dialogButtons)').last();
					var $ErrorNotice = $requestContentDiv.children('.notice');
					if (!$ErrorNotice.length){
						$ErrorNotice = $.mk('div').append($.mk('p'));
						$requestContentDiv.append($ErrorNotice);
					}
					$ErrorNotice
						.attr('class','notice '+noticeClasses[params.type])
						.children('p').html(params.content);
					_controlInputs(params.type === 'wait' ? DISABLE : ENABLE);
				}
				else {
					_open = params;
					$dialogButtons = $('#dialogButtons').empty();
					_controlInputs(DISABLE);
					$dialogContent.append($contentAdd);

					if (params.buttons){
						if ($dialogButtons.length === 0)
							$dialogButtons = $makeDiv('dialogButtons');
						$dialogButtons.appendTo($dialogContent);
					}
				}
			}
			else {
				_storeFocus();
				_open = params;

				$dialogOverlay = $makeDiv('dialogOverlay').css('opacity', 0);
				$dialogHeader = $makeDiv('dialogHeader').text(params.title||defaultTitles[type]);
				$dialogContent = $makeDiv('dialogContent');
				$dialogBox = $makeDiv('dialogBox').css({ top: '-10%', opacity: 0 });

				$dialogContent.append($contentAdd);
				$dialogButtons = $makeDiv('dialogButtons').appendTo($dialogContent);
				$dialogBox.append($dialogHeader).append($dialogContent);
				$dialogOverlay.append($dialogBox).appendTo($body);

				setTimeout(function(){
					$dialogOverlay.add($dialogBox).css('opacity', 1);
					$dialogBox.css('top', 0);
				},10);

				$body.addClass('dialog-open');
			}

			if (!appendingToRequest){
				$dialogHeader.attr('class',params.color+'-bg');
				$dialogContent.attr('class',params.color+'-border');
			}

			if (!appendingToRequest && params.buttons) $.each(params.buttons, function (name, obj) {
				var $button = $.mk('input').attr({
					'type': 'button',
					'class': params.color+'-bg'
				});
				if (typeof obj === 'function')
					obj = {action: obj};
				else if (obj.form){
					$requestContentDiv = $('#'+obj.form);
					if ($requestContentDiv.length === 1){
						$button.on('click', function(){
							$requestContentDiv.find('input[type=submit]').trigger('click');
						});
						$requestContentDiv.prepend($.mk('input').attr('type','submit').hide());
					}
				}
				$button.val(name).on('keydown', function (e) {
					if ([Key.Enter, Key.Space].indexOf(e.keyCode) !== -1){
						e.preventDefault();

						$button.trigger('click');
					}
					else if ([Key.Tab, Key.LeftArrow, Key.RightArrow].includes(e.keyCode)){
						e.preventDefault();

						var $dBc = $dialogButtons.children(),
							$focused = $dBc.filter(':focus'),
							$inputs = $dialogContent.find(':input');

						if ($.isKey(Key.LeftArrow, e))
							e.shiftKey = true;

						if ($focused.length){
							if (!e.shiftKey){
								if ($focused.next().length) $focused.next().focus();
								else if ($.isKey(Key.Tab, e)) $inputs.add($dBc).filter(':visible').first().focus();
							}
							else {
								if ($focused.prev().length) $focused.prev().focus();
								else if ($.isKey(Key.Tab, e)) ($inputs.length > 0 ? $inputs : $dBc).filter(':visible').last().focus();
							}
						}
						else $inputs.add($dBc)[!e.shiftKey ? 'first' : 'last']().focus();
					}
				}).on('click', function (e) {
					e.preventDefault();

					$.callCallback(obj.action, [e]);
				});
				$dialogButtons.append($button);
			});
			_setFocus();

			$.callCallback(callback, [$requestContentDiv]);
		}

		// Close dialog
		function Close(callback) {
			if (!Dialog.isOpen())
				return $.callCallback(callback, false);

			$dialogOverlay.add($dialogBox).css('opacity', 0);
			$dialogBox.css('top', '20%');

			_closeTimeout = setTimeout(function(){
				_open = undefined;
				$dialogOverlay.remove();
				_restoreFocus();
				$.callCallback(callback);

				$body.removeClass('dialog-open');
				_closeTimeout = undefined;
			}, 300);
		}
		Dialog.close = function(){ Close.apply(Dialog, arguments) };
		return Dialog;
	})();

	$body.on('keydown',function(e){
		if (!$.Dialog.isOpen() || e.keyCode !== Key.Tab)
			return true;

		var $inputs = $dialogContent.find(':input'),
			$focused = $inputs.filter(e.target),
			idx = $inputs.index($focused);

		if ($focused.length === 0){
			e.preventDefault();
			$inputs.first().focus();
		}
		else if (e.shiftKey){
			if (idx === 0){
				e.preventDefault();
				$dialogButtons.find(':last').focus();
			}
			else {
				var $parent = $focused.parent();
				if (!$parent.is($dialogButtons))
					return true;
				if ($parent.children().first().is($focused)){
					e.preventDefault();
					$inputs.eq($inputs.index($focused)-1).focus();
				}
			}
		}
	});
})(jQuery);
