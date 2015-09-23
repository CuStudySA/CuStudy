$(function(){
	$('.delgroup').on('click',function(e){
		e.preventDefault();

		var title = 'Csoport törlése';
		$.Dialog.confirm(title,'Biztosan törlöd a csoportot? Ha a csoportnak vannak tagjai, a rendszer automatikusan kilépteti őket.',['Csop. törlése','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);

			$.ajax({
				method: "POST",
				url: "/groups/delete",
				data: pushToken({'id':id}),
				success: function(data){
					if (data.status){
						$.Dialog.success(title,data.message);
						window.location.href = '/groups';
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	});
});
