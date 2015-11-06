$(function(){
	var $uploadForm = $("\
		<div class='uploadContainer'>\
			<input type='file' class='uploadField' name='uploadField'>\
			<div class='infoContainer' style='display: none;'>\
				<input type='text' name='fileTitle' placeholder='Dokumentum címe' required autofocus='true'>\
				<textarea name='fileDesc' placeholder='Dokumentum tartalma, leírása' required></textarea>\
			</div>\
		</div>"),
		$uploadFileForm = $('.uploadFileForm').clone();

	var $fileForm = $('.uploadFileForm'),
		$formContainer = $('.fileFormContainer'),
		files = [];

	var ifExistInList = function(file){
		var $fileInputs = $('.uploadContainer').find('input[type=file]');
		var exist = 0;

		$.each($fileInputs,function(key,value){
			var input = value.files[0];
			if (typeof input != 'undefined'){
				if (file.name == input.name && input.size == file.size)
					exist++;
			}
		});
		return exist > 1;
	};
	var removeFileFromList = function(list,file){
		var newList = [];

		$.each(list,function(key,value){
			if (!(value.name == file.name && value.size == file.size))
				newList.push(value);
		});

		return newList;
	};

	var e_inputChange = function(e){
		var file = e.target.files[0],
			$infoCont = $(e.currentTarget).parent().children().filter('.infoContainer'),
			$formContainer = $('.fileFormContainer');

		if (typeof file != 'undefined'){
			$infoCont.show();

			if (ifExistInList(file))
				$(e.currentTarget).parent().remove();

			if (typeof $(e.currentTarget).parent().prop('prevFiles') != 'undefined')
				return;

			$(e.currentTarget).parent().prop('prevFiles',e.target.files);

			$formContainer.append($uploadForm.clone());
			//$('.uploadContainer').last().find('[name=fileTitle]').focus();
			$('.uploadField').on('change',e_inputChange);
		}
		else
			$(e.currentTarget).parent().remove();
	};
	$('.uploadField').on('change',e_inputChange);

	var e_file_add = function(e){
		e.preventDefault();

		var $fileForm = $('.uploadFileForm');

		$fileForm.show();
		$(document.body).animate({scrollTop: $fileForm.offset().top - 10 }, 500);
	};
	$('.js_file_add').on('click',e_file_add);

	var e_upload_files = function(e){
		e.preventDefault();

		var data = new FormData();
		var $fileInputs = $('.uploadContainer').find('input[type=file]'),
			title = 'Fájl(ok) feltöltése';

		$.Dialog.wait(title,"A fájlok feltöltése folyamatban van... Kérjük, ne zárja be ezt az ablakot!");

		$.each($fileInputs,function(key,value){
			var input = value.files[0];
			if (typeof input != 'undefined')
				data.append(key,input);
		});

		var $infoConts = $('.uploadContainer').find('.infoContainer');
		$.each($infoConts,function(key,value){
			var $elem = $(value);

			if ($elem.css('display') != 'none'){
				var fileTitle = $elem.find('[name=fileTitle]');
				var fileDesc = $elem.find('[name=fileDesc]');

				data.append(key + '.' + 'title',fileTitle.val());
				data.append(key + '.' + 'desc',fileDesc.val());
			}
		});

		data.append('JSSESSID',getToken());

		$.ajax({
			method: "POST",
			url: '/files/uploadFiles',

			// For file uploading
			cache: false,
			processData: false,
			contentType: false,

			data: data,
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/files';
					},2500);
				}
				else {
					$('.uploadFileForm').remove();
					$('main').append($uploadFileForm);

					$('.uploadField').on('change',e_inputChange);
					$('.js_uploadFiles').on('click',e_upload_files);
					$('.js_file_add').on('click',e_file_add);

					$.Dialog.fail(title,data.message);
				}
			},
			error: function(){
				$.Dialog.fail(title,'A fájlok szerverre történő továbbítása sikertelenül zárult! Kérjük, próbálja újra!');
			},
		});
	};
	$('.js_uploadFiles').on('click',e_upload_files);

	var $UsedSpaceIndicator = $('#storage-use').find('.indicator'),
		$USIFill = $UsedSpaceIndicator.children('.used');
	var e_delete = function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Dokumentum törlése';

		$.Dialog.confirm(title,'Arra készül, hogy törli a kiválasztott dokumentumot a szerverről. A művelet nem visszavonható! Folytatja?',['Végleges törlés','Visszalépés'],
			function(sure){
				if (!sure) return;

				$.Dialog.wait(title);

				$.ajax({
					method: 'POST',
					url: '/files/delete',
					data: pushToken({'id': id}),
					success: function(data){
						if (typeof data !== 'object'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}

						if (data.status){
							$(e.currentTarget).parent().parent().remove();
							$.Dialog.close();

							if (typeof data.storage !== 'undefined'){
								var storage = data.storage,
									usedperc = data.storage['Used%'];
								if (!isNaN(usedperc) && usedperc > 0){
									if ($USIFill.length === 0)
										$USIFill = $.mk('div').appendTo($UsedSpaceIndicator);
									$USIFill
										.css('width', usedperc + '%')
										.attr('class', 'used '+(usedperc > 75 ? 'high' : 'low'));
								}
								else $USIFill.fadeOut(500,function(){
									$USIFill.remove();
								});

								$UsedSpaceIndicator.prev().text(storage.Used+' ('+usedperc+'%) felhasználva az osztály számára elérhető '+storage.Available+'-ból.')
							}
						}

						else $.Dialog.fail(title,data.message);
					}
				});
			});
	};
	$('.js_delete').on('click',e_delete);

	var e_getFileInfo = function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Dokumentum információk lekérése';

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/files/getFileInfo',
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status)
					$.Dialog.info(title,data.html);

				else $.Dialog.fail(title,data.message);
			}
		});
	};
	$('.js_more_info').on('click',e_getFileInfo);

});
