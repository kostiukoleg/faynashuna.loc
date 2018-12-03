 /* 
 * Модуль  previewPhoto, подключается на странице настроек плагина.
 */

var previewPhoto = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      

      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'preview-photo'
        },
        function(response) {
          previewPhoto.lang = response.data;        
        }
      );        
        
    
      // Сброс фильтров.
      $('.admin-center').on('click', '.section-preview-photo .refreshFilter', function(){
        admin.show('preview-photo',"plugin","refreshFilter=1",previewPhoto.callbackBackRing);
        return false;
      });      
     
      // Выбор картинки
      $('.admin-center').on('click', '.section-preview-photo .start-processs ', function() {
        $('.log').text('');
         previewPhoto.start();
      });     
      
       // сбор изображений
      $('.admin-center').on('click', '.section-preview-photo .processs-get-photo ', function() {   
         previewPhoto.getOriginalPhoto();
      });
      
           
        // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-preview-photo .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {      
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
      // Сохраняет базовые настроки
      $('.admin-center').on('click', '.section-preview-photo .base-setting-save', function() {
       
        var obj = '{';
        $('.section-preview-photo .list-option input, .section-preview-photo .list-option textarea, .section-preview-photo .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");
       
      
        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'preview-photo', // плагин для обработки запроса
          data: data 
        },

        function(response) {
          admin.indication(response.status, response.msg);     
         
        }

        );
        
      });    
      
    },
    
     /*
     * процесс сбора фоток
     */
    getOriginalPhoto:function(nextItem, total_count) {           
      admin.ajaxRequest({
        mguniqueurl:"action/getOriginalPhoto",
        pluginHandler: 'preview-photo', // плагин для обработки запроса   
        nextItem: nextItem,
        total_count: total_count
      },
      function(response) {
        admin.indication(response.status, response.msg);
        $('.log').text($('.log').text()+response.data.log);
        $('.log').text($('.log').text()+response.msg);
        $('.progress').text('Выполнено '+((response.data.percent>100)?100:response.data.percent)+'%');
        
        if(response.data.percent<100){
          previewPhoto.getOriginalPhoto(response.data.nextItem, response.data.total_count);        
        }else{
          $('.generation').hide();
          $('.progress').text('Выполнено 0%');
          $('.generation').hide();
          $('.start-processs ').css('color','black');
        }
      });
    },
    
  
    /*
     * процесс преобразования изображений
     */
    start:function(nextItem, total_count, imgCount) {
      $('.generation').show();
      $('.progress').show();      
      $('.start-processs ').css('color','rgb(213, 210, 210)');
      
      admin.ajaxRequest({
        mguniqueurl:"action/start",
        pluginHandler: 'preview-photo', // плагин для обработки запроса
        nextItem: nextItem,
        total_count: total_count,
        imgCount: imgCount
      },
      function(response) {
        admin.indication(response.status, response.msg);    
        $('.log').text($('.log').text()+response.data.log);
        $('.log').text($('.log').text()+response.msg);
        $('.progress').text('Выполнено '+((response.data.percent>100)?100:response.data.percent)+'%');
        
        if(response.data.percent<100){
          previewPhoto.start(response.data.nextItem, response.data.total_count, response.data.imgCount);        
        }else{
          $('.generation').hide();
          $('.progress').text('Выполнено 0%');
          $('.generation').hide();
          $('.start-processs ').css('color','black');
        }
      } 
      );
    },
    
    
  }
})();

previewPhoto.init();
