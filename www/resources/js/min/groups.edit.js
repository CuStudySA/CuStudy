"use strict";var $grpList=$("#member"),$classList=$("#class");0==$grpList.children().length&&$grpList.append("<option value='empty'>(üres lista)</option>"),0==$classList.children().length&&$classList.append("<option value='empty'>(üres lista)</option>"),$("#1to2").on("click",function(){var e=$grpList.find(":selected");"empty"==$classList.children().eq(0).attr("value")&&$classList.children().eq(0).remove(),e.each(function(e,t){$grpList.find(t).remove();var s=$(t);s.removeAttr("selected"),"empty"!=s.attr("value")&&$classList.append(s)}),0==$grpList.children().length&&$grpList.append("<option value='empty'>(üres lista)</option>")}),$("#2to1").on("click",function(){var e=$classList.find(":selected");"empty"==$grpList.children().eq(0).attr("value")&&$grpList.children().eq(0).remove(),e.each(function(e,t){$classList.find(t).remove();var s=$(t);s.removeAttr("selected"),"empty"!=s.attr("value")&&$grpList.append(s)}),0==$classList.children().length&&$classList.append("<option value='empty'>(üres lista)</option>")}),$("#sendform").on("click",function(){var e="",t="";$grpList.children().each(function(t,s){var a=$(s);"empty"!=a.attr("value")&&(e+=a.attr("value")+",")}),0!=e.length&&(e=e.substr(0,e.length-1)),$classList.children().each(function(e,s){var a=$(s);"empty"!=a.attr("value")&&(t+=a.attr("value")+",")}),0!=t.length&&(t=t.substr(0,t.length-1));var s={name:$("#name").val(),theme:$("#theme").val(),group_members:e,class_members:t},a="Módosítások végrehajtása";$.Dialog.wait(a),$.ajax({method:"POST",data:s,success:function(e){return"string"==typeof e?console.log(e)===$(window).trigger("ajaxerror"):void(e.status?($.Dialog.success(a,e.message),setTimeout(function(){window.location.href="/groups"},2500)):$.Dialog.fail(a,e.message))}})});
//# sourceMappingURL=groups.edit.js.map