var $grpList = $("#member"),
	$classList = $("#class");

if($classList.children().length == 0)
	$classList.append("<option value='empty'>(üres lista)</option>");

$('#1to2').on('click',function(){
	var $selGrpUsers = $grpList.find(":selected");

	if ($classList.children().eq(0).attr('value') == 'empty')
		$classList.children().eq(0).remove();

	$selGrpUsers.each(function(index,element){
		$grpList.find(element).remove();
		var $elem = $(element);
		$elem.removeAttr("selected");
		if ($elem.attr('value') != 'empty')
			$classList.append($elem).children().sort(function(a,b){
				return $(a).text().localeCompare($(b).text());
			}).appendTo($classList);
	});

	if($grpList.children().length == 0)
		$grpList.append("<option value='empty'>(üres lista)</option>");
});

$('#2to1').on('click',function(){
	var $selGrpUsers = $classList.find(":selected");

	if ($grpList.children().eq(0).attr('value') == 'empty')
		$grpList.children().eq(0).remove();

	$selGrpUsers.each(function(index,element){
		$classList.find(element).remove();
		var $elem = $(element);
		$elem.removeAttr("selected");
		if ($elem.attr('value') != 'empty')
			$grpList.append($elem).children().sort(function(a,b){
				return $(a).text().localeCompare($(b).text());
			}).appendTo($grpList);
	});

	if($classList.children().length == 0)
		$classList.append("<option value='empty'>(üres lista)</option>");
});

$('#sendform').on('click',function(){
	var grpE = "";

	$grpList.children().each(function(index,element){
		var $el = $(element);
		if ($el.attr('value') != 'empty'){
			grpE += $el.attr('value') + ',';
		}
	});
	if (grpE.length != 0)
		grpE = grpE.substr(0,grpE.length-1);

	var json = {
		'name': $('#name').val(),
		'theme': $('#theme').val(),
		'group_members': grpE,
	};

	var title = 'Csoport hozzáadása';

	$.Dialog.wait(title);
	$.ajax({
		method: 'POST',
		data: json,
		success: function(data){
			if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

			if (data.status){
				$.Dialog.success(title,data.message);
				setTimeout(function(){
					window.location.href = '/groups';
				},2500);
			}

			else {
				$.Dialog.fail(title,data.message);
			}
		}
	});
});
