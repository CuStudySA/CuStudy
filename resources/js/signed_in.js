$(function(){
	$('#logout').on('click',function(e){
		e.preventDefault();

		var title = 'Kilépés a rendszerből';
		$.Dialog.confirm(title,'Biztosan ki szeretnél jelentkezni?',['Kijelentkezek','Belépve maradok'],function(sure){
			if (!sure) return;

			$.ajax({
				method: "POST",
				url: "/logout",
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data.status){
						$.Dialog.success(title,'Sikeresen kijelentkezett, átirányítjuk...');//php
						window.location.href = '/';
					}
					else $.Dialog.fail(title,'Kijelentkezés nem sikerült, próbálja meg később, vagy törölje a böngésző sütijeit!');
				}
			})
		});
	});
});