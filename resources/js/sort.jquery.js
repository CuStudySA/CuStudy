$.fn.sortChildren = function(selector, reverse){
    var $elem = $(this),
        $ch = $elem.children();
    $ch.sort(function(a,b){
        return reverse ? $(b).find(selector).text().localeCompare($(a).find(selector).text()) : $(a).find(selector).text().localeCompare($(b).find(selector).text());
    }).appendTo($elem);
    return $elem;
};
