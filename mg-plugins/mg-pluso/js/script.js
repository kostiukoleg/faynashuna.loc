 /* 
 * Модуль  blankEntityModule, подключается на странице настроек плагина.
 */

var mgPluso = (function() {
  
  return { 
    init: function() {      
      
      // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-mg-pluso .base-setting-save', function() {
   
        var obj = '{';
        $('.list-option input[type="text"], .list-option input[type="checkbox"], .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '"theme":"' + $('.list-option input[type="radio"]:checked').val() + '"';
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");
        
        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'mg-pluso', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);      
        }

        );
        
      }); 
      
      $('.admin-center').on('click', '.section-mg-pluso .showTheme', function(){
        var oldText = $(this).text();
        
        if($('.section-mg-pluso .pluso-select').is(':visible')){
          $('.section-mg-pluso .pluso-select').slideUp();
          $(this).text($(this).data('alt-text'));
        }else{
          $('.section-mg-pluso .pluso-select').slideDown();
          $(this).text($(this).data('alt-text'));
        }
        
        $(this).data('alt-text', oldText);
      });
      
      $('.admin-center').on('click', '.section-mg-pluso .unwrap', function(){
        var oldText = $(this).text();
        
        if($('.section-mg-pluso select[name="services"]').attr("size") == 5){
          $('.section-mg-pluso select[name="services"]').attr("size", 20);
          $(this).text($(this).data('alt-text'));
        }else{
          $('.section-mg-pluso select[name="services"]').attr("size", 5);
          $(this).text($(this).data('alt-text'));
        }
        
        $(this).data('alt-text', oldText);
      });
      
      var oldChecked = '';
      $('.admin-center').on('click', '.section-mg-pluso .pluso-select label input', function(){
        
        if(oldChecked == ''){
          oldChecked = $('.section-mg-pluso .pluso .exampleTheme input[name="curTheme"]').val();
        }
        
        var checked = $(this).attr('class');
        
        if(checked == 't14' || oldChecked == 't14'){
          var inner = $(this).parents("div").html();
          $('.section-mg-pluso .pluso .exampleTheme').html(inner);
        }
        
        $('.section-mg-pluso .pluso .exampleTheme').removeClass(oldChecked).addClass(checked);
        oldChecked = checked;
      });
      
      $('.admin-center').on('change', '.section-mg-pluso input[name="use-background"]', function(){
        if($(this).is(':checked')){
          $('.section-mg-pluso input[name="background"]').removeAttr("disabled");
        }else{
          $('.section-mg-pluso input[name="background"]').attr("disabled","disabled");
        }
      });
      
    },
  }
})();

mgPluso.init();