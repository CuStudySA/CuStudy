$(function(){
	var $uploadForm = $("\
		<div class='uploadContainer'>\
			<input type='file' class='uploadField' name='uploadField'>\
			<div class='infoContainer' style='display: none;'>\
				<p class='fileTitle'><input type='text' name='fileTitle' placeholder='Dokumentum címe' required></p>\
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
			$infoCont = $(e.currentTarget).parent().children().filter('.infoContainer');

		if (typeof file != 'undefined'){
			$infoCont.show();

			if (ifExistInList(file))
				$(e.currentTarget).parent().remove();

			if (typeof $(e.currentTarget).parent().prop('prevFiles') != 'undefined')
				return;

			$(e.currentTarget).parent().prop('prevFiles',e.target.files);

			$formContainer.append($uploadForm.clone());
			$('.uploadField').on('change',e_inputChange);
		}
		else
			$(e.currentTarget).parent().remove();
	};
	$('.uploadField').on('change',e_inputChange);

	$('.js_file_add').on('click',function(e){
		e.preventDefault();

		$fileForm.show();
		$(document.body).animate({scrollTop: $fileForm.offset().top - 10 }, 500);
	});

	$('.js_uploadFiles').on('click',function(e){
		e.preventDefault();

		var data = new FormData();
		var $fileInputs = $('.uploadContainer').find('input[type=file]'),
			title = 'Fájl(ok) feltöltése';

		$.each($fileInputs,function(key,value){
			var input = value.files[0];
			if (typeof input != 'undefined')
				data.append(key,input);
		});
		data.append('JSSESSID',getToken());

		$.ajax({
			method: "POST",
			url: '/files/uploadFiles',

			// For file uploading
			cache: false,
			dataType: 'json',
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
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});