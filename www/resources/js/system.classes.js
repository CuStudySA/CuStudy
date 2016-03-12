$(function(){
	$('#js_hideShowFilter').on('click',function(){
		var $btn = $(this),
			$filterForm = $('#filterFormContainer');

		$btn.toggleClass('typcn-arrow-up-thick typcn-arrow-down-thick hide show');

		$filterForm[$btn.hasClass('hide') ? 'show' : 'hide']();
		$btn.text($btn.hasClass('hide') ? 'Szűrőpanel összecsukása' : 'Szűrőpanel kinyitása');

		$btn.blur();
	});

	$('#filterForm').on('submit',function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: "/system.classes/filter",
			data: $('#filterForm').serializeForm(),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$('#resultContainer').empty().append(data.html);
					$.Dialog.close();
					$('.js_filterClasses').blur();
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});

	var $editForm = $("<form id='js_form'>\
						<p>Osztály iskolai azonosítója: <input type='text' name='classid' placeholder='10.X' required></p>\
						<input type='hidden' name='id' value=''>\
					</form>");

	$('#js_editBasicInfos').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Osztály alapadatainak szerkesztése';

		$.Dialog.wait(title,'Alapadatok lekérdezése...');

		$.ajax({
			method: "POST",
			url: "/system.classes/get/basicInfos",
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					var $form = $editForm.clone();

					$form.find('[name=classid]').attr('value',data.classid);
					$form.find('[name=id]').attr('value',id);

					$.Dialog.request(title,$form,'js_form','Mentés',function(){
						var $urlap = $('#js_form');

						$urlap.on('submit',function(e){
							e.preventDefault();

							$.Dialog.wait(title);

							$.ajax({
								method: "POST",
								url: "/system.classes/editBasicInfos",
								data: $urlap.serializeForm(),
								success: function(data2){
									if (typeof data2 === 'string'){
										console.log(data2);
										$(window).trigger('ajaxerror');
										return false;
									}
									if (data2.status){
										$.Dialog.success(title,data2.message);
										setTimeout(function(){
											window.location.reload();
										},1500)
									}

									else $.Dialog.fail(title,data2.message);
								}
							});
						});
					});
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});
});