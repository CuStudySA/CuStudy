"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol?"symbol":typeof e};$(function(){function e(e){$.ajax({method:"POST",url:"/timetables/showTimetable/"+(e?"my":"all"),data:{dispDays:s},success:function(t){l=e?0:1,s=t.dispDays,e||r.removeClass("single"),r.html(t.timetable),c.attr("disabled",t.lockBack),y.attr("disabled",i=!1),n&&r.addClass("single"),f[e?"removeClass":"addClass"]("typcn-user")[e?"addClass":"removeClass"]("typcn-group").html(e?'Teljes<span class="desktop-only"> nézet</span>':'Saját<span class="desktop-only"> órarend</span>'),$.Dialog.close()}})}$(".timet").addClass("notAdmin");var t,a="Órarend léptetése",s="object"!==("undefined"==typeof _dispDays?"undefined":_typeof(_dispDays))?"":_dispDays,o=s,l=0,n=!1,i=!0,c=$(".backWeek"),d=$(".nextWeek"),r=$(".timet"),u=$("#startDatePicker"),p=u.val(),m=function(e){return"back"==e&&c.is(":disabled")?$.Dialog.fail(a,"A jelenlegi héttől nem lehetséges visszább léptetni!"):($.Dialog.wait(a),void $.ajax({method:"POST",url:"/homeworks/getTimetable/nextBack",data:{move:e,dispDays:s,showAllGroups:l},success:function(e){return e.status?(o=s,s=e.dispDays,r.html(e.timetable),c.attr("disabled",e.lockBack).blur(),u.val(new Date(s[0]).toISOString().substring(0,10)),d.blur(),void $.Dialog.close()):$.Dialog.fail(a,e.message)}}))};c.on("click",function(){m("back")}),d.on("click",function(){m("next")}),u.on("change",function(e){e.preventDefault();var n=$(this).val();return n===p||(isNaN(Date.parse(n))?$.Dialog.fail("Léptetés","Érvénytelen dátum!"):("undefined"!=typeof t&&(t.abort(),t=void 0),$.Dialog.open&&"wait"===$.Dialog.open.type||$.Dialog.wait(a),void(t=$.ajax({method:"POST",url:"/homeworks/getTimetable/date",data:{date:new Date($(this).val()).toISOString(),days:5,showAllGroups:l},success:function(e){o=s,s=e.dispDays,r.html(e.timetable),c.attr("disabled",e.lockBack),p=new Date(s[0]).toISOString().substring(0,10),u.val(p),$.Dialog.close(),t=void 0}}))))});var y=$("#js_switchView"),f=$(".js_fullPersonalToggle");f.on("click",function(t){t.preventDefault(),$.Dialog.wait(),e(this.className.indexOf("typcn-group")===-1)});var g=function(){n=!n,$(".timet")[(n?"add":"remove")+"Class"]("single"),y.toggleClass("typcn-eye typcn-eye-outline").html((n?"Hagyományos":"Kompakt")+'<span class="desktop-only"> nézet</span>')};y.on("click",g),"compact"==getUserSetting("timetable.defaultViewMode")&&g()});
//# sourceMappingURL=tt-view.js.map
