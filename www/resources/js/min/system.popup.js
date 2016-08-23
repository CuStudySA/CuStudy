"use strict";$(function(){$("#selectUsers").on("click",function(e){e.preventDefault();var t=[];$("input:checked").each(function(e,n){var a=$(n),i=a.parent().parent();t.push({id:i.find("[data-type=id]").text(),name:i.find("[data-type=name]").text(),email:i.find("[data-type=email]").text()})}),window.opener.response(t)})});
//# sourceMappingURL=system.popup.js.map
