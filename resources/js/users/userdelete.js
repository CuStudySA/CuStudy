$(function(){
	$('.userdelete').on('click',function(e){
		e.preventDefault();

		var title = 'Felhasználó törlése';
		$.Dialog.confirm(title,'Biztosan törölni szeretnéd a felhasználót? A művelet nem visszavonható!',['Felh. törlése','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);

			$.ajax({
				method: "POST",
				url: "/users/delete",
				data: {'id':id},
				success: function(data){
					if (data.status){
						$.Dialog.success(title,data.message);
						window.location.href = '/users';
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	});
});
