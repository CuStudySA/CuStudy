/* Copyright 2014-2015. BetonSoft (Mészáros Bálint) */

var title = "Óra hozzáadása";
/* Hozzáadás gomb eseménye */
$('.addlesson').click(function(){
	var $lessoname = $('.a_l_name').val(),
		$lessoncolor = $('#colorpicker').val(),
		$nameregex = new RegExp("^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$"),
		$ul_list = $('.l_l_utag'),
		nesreturn = false;

	//Formátum ellenörzése
	if (!$nameregex.test($lessoname)){
		$.Dialog.fail(title,"A tantárgy formátuma nem megfelelő! A tantárgy csak a magyar ABC kis-, és nagybetűit tartalmazhatja!");
		return;
	}

	//Létezik-e már ilyen elem?
	$.each($ul_list.children(),function(i,entry){
		var lesname = $(entry).attr('data-name');

		if (lesname == $lessoname){
			$.Dialog.fail(title,"Már létezik ilyen nevű tantárgy, válassz másik nevet!");
			nesreturn = true;
			return;
		}
	});
	if (nesreturn) return;

	//Hozzáadás a listához
	$('.l_l_utag').append('<li data-color="'+ $lessoncolor +'" data-name="'+ $lessoname +'"><span style="color: '+ $lessoncolor +'" class="l_l_litag">'+ $lessoname +'</span><span class="typcn typcn-times l_l_deleteopt"></span></li>');

	//Beviteli mezők alaphelyzetbe állítása és üres jelzés eltávolítása
	$('.l_l_empty').remove();
	$('.a_l_name').val('');

	/* Törlés eseménye */
	$('.l_l_deleteopt').on('click',function(){
		var $li = $(this).parent(),
			$ul = $('.l_l_utag');

		$li.remove();

		if ($ul.children().size() == 0)
			$ul.append('<li class="l_l_empty">(nincs)</li>');
	});
});

var lessons = [];
/* Elküldés gomb eseménye */
$('.sendform').click(function(){
	var $ul = $('.l_l_utag'),
		$inputs = $('.teacher_info').find('input'),
		name, short;

	//Tantárgyak listájának előkészítése
	$.each($ul.children(),function(i,entry){
		if ($(entry).hasClass('l_l_empty')) return;
		var name = $(entry).attr('data-name'),
			color = $(entry).attr('data-color');

		lessons.push({'name': name, 'color': color});
	});

	//Tanár adatainak előkészítése
	$.each($inputs,function(i,entry){
		switch ($(entry).attr('name')){
			case 'name':
				name = $(entry).val();
			break;
			case 'short':
				short = $(entry).val();
			break;
			default:
				return;
		}
	});

	$.Dialog.wait(title);

	//Kommunikáció a szerverrel
	$.ajax({
			method: 'POST',
			data: {'name': name, 'short': short, 'lessons': lessons},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/teachers';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
	});
});