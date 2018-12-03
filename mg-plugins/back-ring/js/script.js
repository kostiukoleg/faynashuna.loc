 /* 
 * Модуль  backRingModule, подключается на странице настроек плагина.
 */

var backRingModule = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'back-ring'
        },
        function(response) {
          backRingModule.lang = response.data;        
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-back-ring .add-new-button', function() {    
        backRingModule.showModal('add');    
      });
      
      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-back-ring .edit-row', function() {       
        var id = $(this).data('id');
        backRingModule.showModal('edit', id);            
      });
      
       // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-back-ring .b-modal .save-button', function() { 
        var id = $(this).data('id');    
        backRingModule.saveField(id);        
      });
      
      // Сброс фильтров.
      $('.admin-center').on('click', '.section-back-ring .refreshFilter', function(){
        admin.show('back-ring',"plugin","refreshFilter=1",backRingModule.callbackBackRing);
        return false;
      });
      
       // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-back-ring .countPrintRowsEntity', function(){
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsEnity",
          pluginHandler: 'back-ring',
          option: 'countPrintRowsBackRing',
          count: count
        },
        function(response) {         
          admin.refreshPanel();
        }
        );

      });

      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-back-ring .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');
        if($(this).hasClass('active')) { 
          backRingModule.visibleEntity(id, 1); 
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          backRingModule.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-back-ring .delete-row', function() {
        var id = $(this).data('id');
        backRingModule.deleteEntity(id);
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-back-ring .base-setting-save', function() {
       
        var obj = '{';
        $('.section-back-ring .list-option input, .section-back-ring .list-option textarea, .section-back-ring .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'back-ring', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);     
          admin.refreshPanel();
        }

        );
        
      });      
      
      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-back-ring .filter-now', function() {
        backRingModule.getProductByFilter();
        return false;
      });
      
      // Выбор картинки
      $('.admin-center').on('click', '.section-back-ring .browseImage', function() {
        admin.openUploader('backRingModule.getFile');
      });     
      
      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-back-ring .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.filter-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
            
      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-back-ring  .show-filters', function() {
        $('.filter-container').slideToggle(function() {
          $('.property-order-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
    },
    
    /* открывает модальное окно 
     * @param {type} type -тип окна, для редактирования или для добавления
     * @param {type} id - номер записи, которая открыта на редактирование
     * @returns {undefined}
     */
    showModal: function(type, id) {
      switch (type) {
        case 'add':
          {
            backRingModule.clearField();           
            break;
          }
        case 'edit':
          {
            backRingModule.clearField();
            backRingModule.fillField(id);
            break;
          }
        default:
          {
            break;
          }
      }

      admin.openModal($('.b-modal'));      
      
    },
                 
   /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {      
      $('.section-back-ring .b-modal  input[name="src"]').val(file.url);
    },      
            
   /**
    * Очистка модального окна
    */         
    clearField: function() {        
      $('.section-back-ring .b-modal .fields-calback input[name="name"]').val('');  
      $('.section-back-ring .b-modal .fields-calback input[name="phone"]').val('');
      $('.section-back-ring .b-modal .fields-calback input[name="city_id"]').val('');  
      $('.section-back-ring .b-modal .fields-calback input[name="date_callback"]').val('');
      $('.section-back-ring .b-modal .fields-calback input[name="time_callback"]').val('');  
      $('.section-back-ring .b-modal .fields-calback textarea[name=comment]').val('');
      $('.section-back-ring .b-modal .save-button').data('id','');   
      $('.section-back-ring .b-modal .ui-helper-hidden-accessible').remove();
    },
            
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
     */        
    fillField: function(id) {

      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id // id записи
      },
         
      
      function(response) {                  
	      $('.section-back-ring .b-modal .fields-calback input[name="name"]').val(response.data.name);	       
        $('.section-back-ring .b-modal .fields-calback input[name="phone"]').val(response.data.phone);	   
        $('.section-back-ring .b-modal .fields-calback input[name="city_id"]').val(response.data.city_id);	   
        $('.section-back-ring .b-modal .fields-calback select[name="mission"]').val(response.data.mission);	   
        $('.section-back-ring .b-modal .fields-calback input[name="date_callback"]').val(response.data.date_callback);	
        $('.section-back-ring .b-modal .fields-calback input[name="time_callback"]').val(response.data.time_callback);	 
        response.data.time_callback = (response.data.time_callback)?response.data.time_callback:'00 00';
        var hours = response.data.time_callback.match( /\d+/g );     
        $('.section-back-ring .b-modal .fields-calback select[name="from"] ').val(hours[0]);	
        $('.section-back-ring .b-modal .fields-calback select[name="to"] ').val(hours[1]);	
        $('.section-back-ring .b-modal .fields-calback select[name="status_id"] ').val(response.data.status_id);	        
        $('.section-back-ring .b-modal .fields-calback textarea[name=comment]').val(response.data.comment);
        $('.section-back-ring .b-modal .save-button').data('id',response.data.id);     
      },
              
      $('.b-modal .widget-table-body') // вывод лоадера в контейнер окна, пока идет загрузка данных
      
      );

    },
    
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
     */        
    saveField: function(id) {
	    
      var invisible = '0';     
      if($('.entity-table-tbody tr[data-id='+id+'] .visible').hasClass('active')){   
        invisible = '1' ;
      }     
      var time_callback = "c " + $('.section-back-ring .b-modal .fields-calback select[name="from"] ').val() + " до " + $('.section-back-ring .b-modal .fields-calback select[name="to"] ').val();	
              
      admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id,
        name: $('.section-back-ring .b-modal .fields-calback input[name="name"]').val(),
        phone: $('.section-back-ring .b-modal .fields-calback input[name="phone"]').val(),
		    city_id: $('.section-back-ring .b-modal .fields-calback input[name="city_id"]').val(),   
        date_callback: $('.section-back-ring .b-modal .fields-calback input[name="date_callback"]').val(),
        time_callback:  time_callback,
        mission: $('.section-back-ring .b-modal .fields-calback select[name="mission"]').val(),
        status_id: $('.section-back-ring .b-modal .fields-calback select[name=status_id]').val(), 
        comment: $('.section-back-ring .b-modal .fields-calback textarea[name=comment]').val(),  
        add_datetime : $('.entity-table-tbody tr[data-id='+id+'] .add_datetime').text(),
        invisible: invisible,  
      },
      
      function(response) {
        admin.indication(response.status, response.msg);
        if(id){
          var replaceTr = $('.entity-table-tbody tr[data-id='+id+']');
          backRingModule.drawRow(response.data.row,replaceTr); // перерисовка строки новыми данными
        } else{
          backRingModule.drawRow(response.data.row); // добавление новой записи         
        }             
        admin.closeModal($('.b-modal'));        
        backRingModule.clearField();
      },
              
      $('.b-modal .widget-table-body') // на месте кнопки
      
      );

    },
    
    
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */        
    drawRow: function(data, replaceTr) {
      
      var invisible = data.invisible==='1'?'active':'';        
      var titleInvisible = data.invisible?lang.ACT_V_ENTITY:lang.ACT_UNV_ENTITY;  
      var status = ['Ожидает', 'Не дозвониться', 'Завершен'];
      
     
      var statusId = data.status_id;
      var $class = 'get-paid';
      if(statusId == 1){        
       $class = 'get-paid';
      }
      if(statusId == 2){        
       $class = 'dont-paid';
      }
      if(statusId == 3){        
       $class = 'activity-product-true';
      }
      var status_id = " <span class='"+$class+"'> "+status[data.status_id-1]+"</span>";   
      
      var tr = '\
       <tr data-id="'+data.id+'">\
        <td>'+data.id+'</td>\
        <td class="add_datetime">'+data.add_datetime+'</td>\
        <td class="name">'+data.name+'</td>\
        <td class="phone">'+data.phone+'</td>\
        <td class="city_id">'+data.city_id+'</td>\
        <td class="mission">'+data.mission+'</td>\
        <td class="date_callback">'+data.date_callback+'</td>\
        <td class="time_callback">'+data.time_callback+'</td>\
        <td class="status_id">'+status_id+'</td>\
         <td class="actions">\
           <ul class="action-list">\
             <li class="edit-row" data-id="'+data.id+'" data-type="'+data.type+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
             <li class="visible tool-tip-bottom '+invisible+'" data-id="'+data.id+'" title="'+titleInvisible+'"><a href="javascript:void(0);"></a></li>\
             <li class="delete-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
           </ul>\
         </td>\
      </tr>';
 
      if(!replaceTr){
       
        if($('.entity-table-tbody tr').length>0){
          $('.entity-table-tbody tr:first').before(tr);
        } else{
          $('.entity-table-tbody').append(tr);
        }
        $('.entity-table-tbody .no-results').remove();
         
      }else{
        replaceTr.replaceWith(tr);
      }
    },
       
       
    /**    
     * Удаляет  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */           
    deleteEntity: function(id) {
      if(!confirm(lang.DELETE+'?')){
        return false;
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id               
      },
      
      function(response) {
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id='+id+']').remove();
        if($(".entity-table-tbody tr").length==0){
          var html ='<tr class="no-results">\
            <td colspan="3" align="center">'+backRingModule.lang['ENTITY_NONE']+'</td>\
          </tr>';
          $(".entity-table-tbody").append(html);
        };
      }
      
      );
    },    


    /*
     * Переключатель активности
     */
    visibleEntity:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/visibleEntity",
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id,
        invisible: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
    getProductByFilter: function() {
      var request = $("form[name=filter]").formSerialize(); 
      admin.show("back-ring", "plugin", request + '&applyFilter=1',backRingModule.callbackBackRing);
      return false;
    },
            
    /**
     *Пакет выполняемых действий после загрузки раздела товаров
     */
    callbackBackRing:function() { 
    admin.AJAXCALLBACK = [      
      {callback:'admin.sortable', param:['.entity-table-tbody','back-ring']},       
    ]; 
    },
    
  }
})();

backRingModule.init();
admin.sortable('.entity-table-tbody', 'back-ring');