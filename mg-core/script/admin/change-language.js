/**
 * Модуль для смены языка админки
 */
var changeLang = (function () {
  return {

    init: function() {
      $('.language-list-wrapper a').click(function(){
        var locale = $(this).attr('class');
        changeLang.changeLanguage(locale);

      });

    },
    changeLanguage: function(language) {
      admin.ajaxRequest({
        mguniqueurl: "action/changeLanguage",
        language: language
      },
      function(response) {
        window.location = admin.SITE+'/mg-admin/';
      }
     )
    }
  }
})();

// инициализация модуля при подключении
changeLang.init();