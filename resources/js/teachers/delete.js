$(function(){
	$('.delteacher').on('click',function(e){
		e.preventDefault();

		var title = 'Tanár törlése';
		$.Dialog.confirm(title,'Biztosan szeretnéd törölni a kiválasztott tanárt? Ha tanít valamilyen tantárgyat, a tanárgy is törlődni fog.',['Tanár törlése','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);
			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/teachers/delete",
				data: {'id':id},
				success: function(data){
					if (data.status){
						$.Dialog.success(title,data.message);
						window.location.href = '/teachers';
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	});
});
