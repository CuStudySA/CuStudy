$(function(){
	var $uploadForm = $("\
		<div class='uploadContainer'>\
			<input type='file' class='uploadField' name='uploadField'>\
			<div class='infoContainer' style='display: none;'>\
				<input type='text' name='fileTitle' placeholder='Dokumentum címe' required autofocus='true'>\
				<textarea name='fileDesc' placeholder='Dokumentum tartalma, leírása' required></textarea>\
			</div>\
		</div>"),
		$fileForm = $('.uploadFileForm').on('click','.js_uploadFiles',e_upload_files),
		$uploadFileForm = $fileForm.clone(true,true),
		$fileList = $('ul.files').on('click','.js_file_add',e_file_add),
		files = [];
	$('.uploadField').add($uploadForm.find('.uploadField')).on('change', e_inputChange);

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

	function e_inputChange(e){
		var $this = $(this),
			file = this.files[0],
			$container = $this.parent(),
			$infoCont = $this.next(),
			$formContainer = $('.fileFormContainer');

		if (typeof file != 'undefined'){
			// Beviteli mezők megjelenítése
			$infoCont.show();

			if (ifExistInList(file))
				$container.remove();

			if (typeof $this.parent().prop('prevFiles') != 'undefined')
				return;

			$infoCont
				// Fájlnév beálltása Dokumentum névnek
				.find('input[name="fileTitle"]').val(e.target.value.split(/(\\|\/)/g).pop())
				// Leírás mező fókuszálása
				.next().focus();

			$(e.currentTarget).parent().prop('prevFiles',e.target.files);

			$formContainer.append($uploadForm.clone(true,true));
		}
		else
			$container.remove();
	}

	function e_file_add(e){
		e.preventDefault();

		var $fileForm = $('.uploadFileForm');

		$fileForm.show();
		$(document.body).animate({scrollTop: $fileForm.offset().top - 10 }, 500);
	}

	function e_upload_files(e){
		e.preventDefault();

		var data = new FormData();
		var $fileInputs = $('.uploadContainer').find('input[type=file]'),
			title = 'Feltöltés folyamatban';

		$.Dialog.wait(title,"Kérjük, ne zárja be ezt az ablakot, a fájlok feltöltése folyamatban van");

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

				$('.uploadFileForm').replaceWith($uploadFileForm);

				if (data.status){
					$fileList.html(data.filelist);
					$.Dialog.close();
					if (typeof data.storage !== 'undefined')
						updateStorage(data.storage);
				}
				else {

					$.Dialog.fail(title,data.message);
				}
			},
			error: function(){
				$.Dialog.fail(title,'A fájlok szerverre történő továbbítása sikertelenül zárult! Kérjük, próbálja újra!');
			},
		});
	};

	var $UsedSpaceIndicator = $('#storage-use').find('.indicator'),
		$USIFill = $UsedSpaceIndicator.children('.used');

	$fileList
		.on('click','.js_delete',e_delete)
		.on('click','.js_more_info',e_getFileInfo);
	function e_delete(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Dokumentum törlése';

		$.Dialog.confirm(title,'Arra készül, hogy törli a kiválasztott dokumentumot a szerverről. A művelet nem visszavonható! Folytatja?',['Végleges törlés','Mégse'],
			function(sure){
				if (!sure) return;

				$.Dialog.wait(title);

				$.ajax({
					method: 'POST',
					url: '/files/delete',
					data: {'id': id},
					success: function(data){
						if (typeof data !== 'object'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}

						if (data.status){
							$(e.currentTarget).parent().parent().remove();
							$.Dialog.close();
							if (typeof data.storage !== 'undefined')
								updateStorage(data.storage);
						}
						else $.Dialog.fail(title,data.message);
					}
				});
			});
	}
	function e_getFileInfo(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Dokumentum információk lekérése';

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/files/getFileInfo',
			data: {'id': id},
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
	}
	function updateStorage(storage){
		var usedperc = storage['Used%'];
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

		$UsedSpaceIndicator.prev().html(function(){
			return this.innerHTML.replace(/^.*<br>/,storage.Used+' ('+usedperc+'%) felhasználva az osztály számára elérhető '+storage.Available+'-ból.<br>');
		});
	}
	$fileList.on('click','.js_open_external_viewer',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Fájl megnyitása külső nézegetőben';

		$.Dialog.wait(title,'Hozzáférési azonosító lérehozása, várjunk...');

		$.ajax({
			method: 'POST',
			url: '/files/openExternalViewer',
			data: {'id': id},
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					window.location.href = data.url;
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});
});
