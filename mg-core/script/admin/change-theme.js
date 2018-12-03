/**
 * Модуль для смены визуального оформления админки.
 * Сохранение выбранных параметров происходит не в
 * момент клика на цвет, а когда меню сворачивается, дабы
 * не перегружать запросами сервер.
 */
var changeTheme = (function () {
  return {

    color: $('#color-theme').text(),
    background: $('#bg-theme').text(),
    bufercolor: $('#color-theme').text(),
    buferbackground: $('#bg-theme').text(),
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function() {

      changeTheme.applyTheme(changeTheme.color);
      $('.admin-wrapper .admin-top-menu').css('display','block');

      // смена цвета меню
      $('body').on('click', '.color-settings .color-list li', function(){
       changeTheme.color = changeTheme.applyTheme($(this).attr('class'));
       $('input[name="themeColor"]').val(changeTheme.color);
      });

      // смена фона
      $('body').on('click', '.background-settings .color-list li', function(){
        if ($(this).hasClass('customBackground')) {
          $('body, html').css({'backgroundImage':'url("'+$(this).attr('img')+'")'});
          $('input[name="themeBackground"]').val('customBackground');
        }
        else{
          changeTheme.background = changeTheme.applybackground($(this).attr('class'));
          $('input[name="themeBackground"]').val(changeTheme.background);
        }
      });

    },

    applyTheme: function(theme) {


      switch (theme) {
        case 'red-theme':{
          return changeTheme.redtheme();
          break;
        }
        case 'blue-theme':{
          return changeTheme.bluetheme();
          break;
        }
        case 'light-blue-theme':{
          return changeTheme.lightbluetheme();
          break;
        }
        case 'green-theme':{
          return changeTheme.greentheme();
          break;
        }
        case 'yellow-theme':{
          return changeTheme.yellowtheme();
          break;
        }
        case 'purple-theme':{
          return changeTheme.purpletheme();
          break;
        }
        case 'pink-theme':{
          return changeTheme.pinktheme();
          break;
        }
        default:{
          return changeTheme.redtheme();
          break;
        }
      }
    },

    applybackground: function(bg) {
      	$('body, html').css({
      'backgroundImage':'url('+admin.SITE+'/mg-admin/design/images/bg_textures/'+bg+'.png)'
      });
      return bg;
    },

    redtheme: function() {
        $('.admin-wrapper .admin-top-menu').css({
      'backgroundColor':'#BA0A0A',
      'borderBottom':'2px solid #FC5858',
      'borderTop':'1px solid #FC5858'
      });

      return 'red-theme';
    },

    bluetheme: function() {
      $('.admin-wrapper .admin-top-menu').css({
      'backgroundColor':'#1A86B2',
      'borderBottom':'2px solid #4EB4DE',
      'borderTop':'1px solid #4EB4DE'
      });

      return "blue-theme";
   },

    lightbluetheme: function() {
      $('.admin-wrapper .admin-top-menu').css({
      'backgroundColor':'#039DE4',
      'borderBottom':'2px solid #03AFFF',
      'borderTop':'1px solid #03AFFF'
      });

      return "blue-theme";
   },
/*
   greentheme: function() {
    $('.admin-wrapper .admin-top-menu').css({
		'backgroundColor':'#2DBE60',
		'borderBottom':'2px solid #4FDA45',
		'borderTop':'1px solid #4FDA45'
		});

   return "green-theme";
  },
*/
   greentheme: function() {
    $('.admin-wrapper .admin-top-menu').css({
    'backgroundColor':'rgb(48, 148, 83)',
    'borderBottom':'2px solid rgb(109, 179, 134)',
    'borderTop':'1px solid rgb(109, 179, 134)'
    });

   return "green-theme";
  },


  yellowtheme: function() {
   $('.admin-wrapper .admin-top-menu').css({
		'backgroundColor':'#C1A700',
		'borderBottom':'2px solid #FBE13A',
		'borderTop':'1px solid #FBE13A'
		});

   return "yellow-theme";
  },

  pinktheme: function() {
   $('.admin-wrapper .admin-top-menu').css({
		'backgroundColor':'#E91E63',
		'borderBottom':'2px solid #FF216B',
		'borderTop':'1px solid #FF216B'
		});

   return "pink-theme";
  },

  purpletheme: function() {
   $('.admin-wrapper .admin-top-menu').css({
		'backgroundColor':'#9C27B0',
		'borderBottom':'2px solid #DC38F9',
		'borderTop':'1px solid #DC38F9'
		});

   return "purple-theme";
  }

  }
})();

// инициализация модуля при подключении
changeTheme.init();