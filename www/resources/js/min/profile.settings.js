"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol?"symbol":typeof t};$(function(){$(".settingsForm").on("submit",function(t){t.preventDefault();var o="Felhasználói beállításaim módosítása",e=$(this).serialize();$.Dialog.wait(o),$.ajax({method:"POST",url:"/profile/settings",data:e,success:function(t){return"object"!==("undefined"==typeof t?"undefined":_typeof(t))?(console.log(t),$(window).trigger("ajaxerror"),!1):void(t.status?$.Dialog.success(o,t.message,!0):$.Dialog.fail(o,t.message))}})})});
//# sourceMappingURL=profile.settings.js.map