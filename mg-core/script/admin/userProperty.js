/**
 * Модуль работы с пользовательскими полями в админке
 */
var userProperty = (function() {

  var savedDataRow = {}; // данные редактируемой строки
  var tmpCount = 0; // для характеристик для товаров, которые еще не записаны в базу
  var cansel = false; // использовать возврат значений при отмене
 
  return {
    delimetr: "|",
    listCategoryConnect:[],// список уже привязанных категорий, заполняется при открытии связей характеристик
    init: function() {
      // открытия фильтров
      $('body').on('click', '.section-settings #tab-userField-settings .mg-panel-toggle', function() {
        $('.filters').slideToggle(function () {   
        });		
      });

      // смена языка
      $('.admin-center').on('change','.section-settings #tab-userField-settings #user-property-edit .select-lang', function() {
        userProperty.fillFields($('#user-property-edit .save-button').attr('id'));     
      });

      // обработчик клика на кнопку сохранить в модальном окне редактирования характеристики
      $('body').on('click', '.section-settings #tab-userField-settings #user-property-edit .save-button', function() {
        userProperty.saveFields();
      });
	  
	  // открытия настроек групп
      $('body').on('click', '.section-settings #tab-userField-settings .mg-panel-group-toggle', function() {
  		  admin.openModal($('#edit-property-group'));
  		  userProperty.getTablePropertyGroup();
      });
	  
	  // Добавление группы характеристик
      $('body').on('click', '.section-settings #tab-userField-settings .add-property-group', function() {
		 admin.ajaxRequest({
            mguniqueurl: "action/addPropertyGroup",
			name: $('#edit-property-group input[name=name-group]').val()
          },
          function(response) {
			userProperty.getTablePropertyGroup();
			$('#edit-property-group input[name=name-group]').val('');
		  });
	  });

      $('body').on('click', '.section-settings #tab-userField-settings #user-property-edit .add-property', function() {
        $('#user-property-edit .new-added-properties input').val('');
        // берем тип поля
        type = $('#user-property-edit [name=type]').val();
        switch (type) {
          case 'color':
            $('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_1);
            $('#user-property-edit .mayby-hide').hide();
            break;
          case 'size':
            $('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_2);
            $('#user-property-edit .mayby-hide').hide();
            break;
          default:
            $('#user-property-edit [name=margin-name]').attr('placeholder', lang.EXAMPLE_3);
            $('#user-property-edit .mayby-hide').show();
            break;
        }
        $('#user-property-edit .new-added-properties').show();
      });

      $('body').on('click', '.section-settings #tab-userField-settings #user-property-edit .cancel-new-prop', function() {
        $('#user-property-edit .new-added-properties').hide();
      });

      $('body').on('click', '.section-settings #tab-userField-settings #user-property-edit .apply-new-prop', function() {
        userProperty.addPropertyMargin();
      });

      $('body').on('change', '.section-settings #tab-userField-settings #user-property-edit [name=type]', function() {
        $('#user-property-edit .new-added-properties').hide();
        userProperty.showPropertyMargin($(this).val());
      });
 
      $('body').on('click', '.section-settings #tab-userField-settings #user-property-edit .fa-trash', function() {
        if(confirm('Удалить значение?')) {
          userProperty.deletePropertyMargin($(this).parents('tr').data('id'));
        }
      });

      // редактирования строки свойства
      $('body').on('click', '.userPropertyTable .edit-row', function() {
        var id = $(this).parents('tr').attr('id');
        userProperty.clearFields();
        userProperty.fillFields(id);
        admin.openModal('#user-property-edit');
      });

      // применение класса selected для строки, которой ставят галочку выделения
      $('body').on('click' ,'tbody .checkbox label', function() {
        var id = $(this).parents('tr').data('id');
        $(this).parents('tr').toggleClass('selected');
      });
     
      // Показывает все доступные значения характеристик.
      $('body').on('click', '.userPropertyTable .show-all-prop', function() {   
        userProperty.showOptions($(this));
      });

      // 
      $('body').on('click', '#tab-userField-settings .close-category-edit', function() {   
        admin.closeModal('#edit-category');
      });
	  
      // 
      $('body').on('click', '#tab-userField-settings .close-edit-property-group', function() {
        admin.closeModal('#edit-property-group');
      });
	  
      // 
      $('body').on('click', '#tab-userField-settings .delete-property', function() {
        if (confirm(lang.WARNINF_MESSAGE_1)) { 
    	    $(this).parents('tr').remove();		
    	    userProperty.deletePropertyGroup($(this).parents('tr').data('id'));
    		}
      });
	  
	    // 
      $('body').on('click', '#tab-userField-settings .save-group-prop', function() {
        userProperty.savePropertyGroup();
      });
	  
	   // смена языка в модалке с группами характеристик
      $('body').on('change', '#tab-userField-settings #edit-property-group .select-lang', function() {
        userProperty.getTablePropertyGroup();
      });	  

      // открыть модалку с привязками к категориям
      $('body').on('click', '.userPropertyTable .see-order', function() {
        var id = $(this).parents('tr').attr('id');
        userProperty.openModalWindow('edit', id);
      });

      // удалить характеристику
      $('body').on('click', '.userPropertyTable .delete-property', function() {
        var id = $(this).parents('tr').attr('id');
        userProperty.deleteRow(id);
      });

      // добавить характеристику
      $('body').on('click', '.addProperty', function() {
        userProperty.addRow();
      });

      //обработчик применения установленных наценок в редактировании продукта
      $('body').on('click', '.userField .apply-margin', function() {
        var property = $(this).parents('.price-settings');
        userProperty.applyMargin(property);
        property.find('.setup-margin-product').show();
        property.find('.panelMargin').remove();        
      });

      //обработчик нажатия на ссылку: установить наценки
      $('body').on('click', '.userField .setup-margin-product', function() {
        var select = $(this).parents('.price-settings').find('select');
        $(this).after(userProperty.panelMargin(select));
        admin.initToolTip();
      });

      //обработчик нажатия на ссылки: установить тип вывода
      $('body').on('click', '.userField .setup-type', function() {      
        var option = $(this).parents('.price-settings');
        option.find('.setup-type').removeClass('selected');
        option.find('.setup-type').removeClass('active');
        $(this).addClass('selected');        
        $(this).addClass('active');        
      });      

      //установка значений по умолчанию
      $('body').on('click', '.setDefaultVal', function() {

        var type = $(this).parents('tr').find('td[class=type] select').val();
        // при выбранном типе - список с одним значением
        if (type == 'select') {
          $(this).parents('tr').find('td[class=data] .itemData').removeClass('is-defaultVal');
          $(this).parents('.itemData').addClass('is-defaultVal');
          userProperty.setDefVal($(this).parents('tr').attr('id'), $(this).data('value'));
        }

        // при выбранном типе - набор опций
        if (type == 'assortmentCheckBox' || type == 'assortment') {

          if ($(this).parents('.itemData').hasClass('is-defaultVal')) {
            $(this).parents('.itemData').removeClass('is-defaultVal');
          } else {
            $(this).parents('.itemData').addClass('is-defaultVal');
          }

          var newdefval = '';
          $(this).parents('tr').find('.is-defaultVal .prop').each(function() {           
            newdefval += $(this).find('.propertyDataName').text()+'#'+$(this).find('input').val()+'#'+userProperty.delimetr;         
          });
          newdefval = newdefval.slice(0, -1);
    
          userProperty.setDefVal($(this).parents('tr').attr('id'), newdefval);
        }
      });

      // Сохранение привязки к категориям.
      $('body').on('click', '#edit-category .save-button', function() {
        userProperty.savePropertyCat($(this).attr('id'));
      });

      // Нажатие на кнопку - выводить/Не выводить в карточке товара
      $('.admin-center').on('click', '.userPropertyTable .visible', function() {
        $(this).find('a').toggleClass('active');  
        var id = $(this).data('id');

        if($(this).find('a').hasClass('active')) {
          userProperty.visibleProperty(id, 1); 
          $(this).attr('title', lang.ACT_V_PROP);
        }
        else {       
          userProperty.visibleProperty(id, 0); 
          $(this).find('a').attr('title', lang.ACT_UNV_PROP);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Нажатие на кнопку - выводить/не выводить в фильтрах
      $('.admin-center').on('click', '.userPropertyTable .filter-prop-row', function() {
        var id = $(this).parents('tr').data('id');
        if ($('.userPropertyTable tr[id=' + id + '] td.type span select').length) {
          var type = $('.userPropertyTable tr[id=' + id + '] td.type span select').val();
        } else {
          var type = $('.userPropertyTable tr[id=' + id + '] td.type span').attr('value');          
        } 
        if (type=='textarea') {
          $(this).find('a').removeClass('active')
          admin.indication('error', lang.ERROR_MESSAGE_21);
          return false;
        }
        $(this).find('a').toggleClass('active');  
              
        if($(this).find('a').hasClass('active')) {
          userProperty.filterVisibleProperty(id, 1);  
          $(this).attr('title', lang.ACT_FILTER_PROP);
        }
        else {       
          userProperty.filterVisibleProperty(id, 0); 
          $(this).attr('title', lang.ACT_UNFILTER_PROP);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Выделить все характеристики.
      $('.admin-center').on('click', '.userField-settings-list .checkbox-cell input[name=property-check]', function() {
        if($(this).val()!='true') {
          $('.userPropertyTable input[name=property-check]').prop('checked','checked');
          $('.userPropertyTable input[name=property-check]').val('true');
        }else{
          $('.userPropertyTable input[name=property-check]').prop('checked', false);
          $('.userPropertyTable input[name=property-check]').val('false');
        }
      }); 
      
      // Выполнение выбранной операции с характеристиками
      $('.admin-center').on('click', '#tab-userField-settings .run-operation', function() {
        if ($('#tab-userField-settings .property-operation').val() == 'fulldelete') {
          admin.openModal('#prop-remove-modal');
        }
        else{
          userProperty.runOperation($('#tab-userField-settings .property-operation').val());
        }
      });
      //Проверка для массового удаления
      $('.admin-center').on('click', '#prop-remove-modal .confirmDrop', function () {
        if ($('#prop-remove-modal input').val() === $('#prop-remove-modal input').attr('tpl')) {
          $('#prop-remove-modal input').removeClass('error-input');
          admin.closeModal('#prop-remove-modal');
          userProperty.runOperation($('#tab-userField-settings .property-operation').val(),true);
        }
        else{
          $('#prop-remove-modal input').addClass('error-input');
        }
      });
      // Устанавливает количество выводимых записей в этом разделе.
      $('.admin-center').on('change', '#tab-userField-settings .countPrintRowsProperty', function () {
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/countPrintRowsProperty",
          count: count
        },
        function (response) {
          admin.refreshPanel();
        }
        );
      });
      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '#tab-userField-settings .show-filters', function () {
        $('.filter-container').slideToggle(function () {
          $('.property-order-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      // Сброс фильтров.
      $('.admin-center').on('click', '#tab-userField-settings  .refreshFilter', function () {
        admin.refreshPanel();
        return false;
      });

      $('.admin-center').on('click', '.section-catalog  .aply-size-map', function () {
        if($(this).data('active') != 'disabled') {
          userProperty.sizeMapCreatedProcess = true;
          $(this).data('active', 'disabled');
          $('.section-catalog  .aply-size-map').html(lang.PROCESSING_WAIT);
          setTimeout(function() {
            $('.variant-table').html(catalog.saveVarTable);
            userProperty.createdSizeVariant();
            userProperty.createdSizeVariant();
            catalog.saveVarTable = $('.variant-table .variant-row, .variant-table .text-left').clone();
            if($('.variant-table .variant-row').length > 1) catalog.buildGroupVarTable();
            $('.section-catalog  .aply-size-map').data('active', 'active');
            $('.section-catalog  .aply-size-map').html('<i class="fa fa-check" aria-hidden="true"></i>'+lang.APPLY+'</a>');
            userProperty.sizeMapCreatedProcess = false;
          },1);
        }
      });

      $('.admin-center').on('click', '.section-catalog  .aply-size-map-check-all', function () {
        $('.size-map .checkbox input').each(function() {   
          $(this).prop('checked', true);
        });
      });

      $('.admin-center').on('click', '.section-catalog  .aply-size-map-uncheck-all', function () {
        $('.size-map .checkbox input').each(function() {   
          $(this).prop('checked', false);
        });
      });

      $('body').on('change', '.imageFormToProp [name=propImg]', function() {
        userProperty.addImageToProp($(this).parents('tr').data('id'), $(this).parents(".imageFormToProp"));
      });

      $('body').on('click', '.deleteImg', function() {
        var id = $(this).parents('tr').data('id');
        userProperty.deleteImgMargin(id);
      });
    },

    createdSizeVariant: function() {
      // более умная проверка на удаление строк
      var variantRowA = [];
      var rowDataToDel = [];
      $('.variant-row').each(function() {   
        variantRowA.push('sizeCheck-'+$(this).find('input[name=color]').val()+'-'+$(this).find('input[name=size]').val());
        rowDataToDel.push($(this).find('.del-variant'));
      });
      var checked = [];
      $('.size-map .checkbox input').each(function() {   
        if($(this).prop('checked')) {
          checked.push($(this).attr('id'));
        }
      });

      // запоминаем первую строку, чтобы потом заполнить данные по шаблону
      var price = $('.variant-table .variant-row:eq(0) input[name=price]').val();
      var old_price = $('.variant-table .variant-row:eq(0) input[name=old_price]').val();
      var count = $('.variant-table .variant-row:eq(0) input[name=count]').val();
      var weight = $('.variant-table .variant-row:eq(0) input[name=weight]').val();
      var code = $('.variant-table .variant-row:eq(0) input[name=code]').val();

      // проверяем строки на счет удаления
      for(row = 0; row < variantRowA.length; row++) {
        toDel = true;
        for(checVar = 0; checVar < checked.length; checVar++) {
          if(variantRowA[row] == checked[checVar]) {
            toDel = false; 
          }
        }
        if(toDel) {
          rowDataToDel[row].click();
        }
      }

      currentVariantLength = $('.variant-row').length;
      // для добавления новых строк
      for(i = 0; i < checked.length - currentVariantLength; i++) {
        $('.add-position').click();
      }

      $('.variant-table .variant-row:eq(0) input[name=price]').val(price);
      $('.variant-table .variant-row:eq(0) input[name=old_price]').val(old_price);
      $('.variant-table .variant-row:eq(0) input[name=count]').val(count);
      $('.variant-table .variant-row:eq(0) input[name=weight]').val(weight);
      $('.variant-table .variant-row:eq(0) input[name=code]').val(code);

      var checked = [];
      $('.size-map .checkbox input').each(function() {   
        if($(this).prop('checked')) {
          var add = true;
          for(i = 0; i < $('.variant-row').length; i++) {
            if(($('.variant-row:eq('+i+') input[name=color]').val() == $(this).data('color'))&&
              ($('.variant-row:eq('+i+') input[name=size]').val() == $(this).data('size'))) {
              add = false;
            }
          }
          if(add) {
            checked.push($(this).attr('id'));
          }
        }
      });

      // автозаполнение новых строк вариантов
      var price = '';
      var old_price = '';
      var count = '';
      var weight = '';
      $('.variant-row').each(function() {   
        // для цены
        if(price == '') {
          price = $(this).find('input[name=price]').val();
        } else {
          if($(this).find('input[name=price]').val() == '') $(this).find('input[name=price]').val(price);
        }
        // для старой цены
        if(old_price == '') {
          old_price = $(this).find('input[name=old_price]').val();
        } else {
          if($(this).find('input[name=old_price]').val() == '') $(this).find('input[name=old_price]').val(old_price);
        }
        // для количества
        if(count == '') {
          count = $(this).find('input[name=count]').val();
        } else {
          if($(this).find('input[name=count]').val() == '') $(this).find('input[name=count]').val(count);
        }
        // для веса
        if(weight == '') {
          weight = $(this).find('input[name=weight]').val();
        } else {
          if($(this).find('input[name=weight]').val() == '') $(this).find('input[name=weight]').val(weight);
        }
        // для названия
        if(($(this).find('input[name=color]').val() == '')&&($(this).find('input[name=size]').val() == '')) {
          $(this).find('input[name=color]').val($('#'+checked[0]).data('color'));
          $(this).find('input[name=size]').val($('#'+checked[0]).data('size'));
          // 
          space = $('#'+checked[0]).parents('td').find('.size').val()?' ':'';
          $(this).find('input[name=title_variant]').val($('#'+checked[0]).parents('td').find('.size').val() + space + $('#'+checked[0]).parents('td').find('.color').val());
          checked.shift();
        }
      });
    },

    createSizeMap: function(data) {
      var color = '';
      var size = '';
      for(i = 0; i < data.length; i++) {
        if(data[i].type == 'color') {
          color = data[i];
        }
        if(data[i].type == 'size') {
          size = data[i];
        }
      }

      table = '';

      // полная сетка
      if((color != '')&&(size != '')) {
        for(i = -1; i < color.data.length; i++) {
          table += '<tr>';
          for(j = -1; j < size.data.length; j++) {
            var sizeD = '';
            if((j >= 0)&&(i == -1)) {
              sizeD = size.data[j].name;
            }
            var colorD = '';
            if((i >= 0)&&(j == -1)) {
              colorD = color.data[i].name;
            }
            if((i == -1)&&(j == -1)) {
              sizeText = '<span class="fl-right">Размеры:</span>';
            } else {
              sizeText = '';
            }
            var checkbox = '';
            if((i >= 0)&&(j >= 0)) {
              checkbox = '\
                <input class="color" value="'+color.data[i].name+'" style="display:none;">\
                <input class="size size-'+size.data[j].id+'" value="'+size.data[j].name+'" style="display:none;">\
                <input class="color-'+color.data[i].id+'" value="'+color.data[i].color+'" style="display:none;">\
                <div style="margin-left: calc(50% - 9px);"><div class="checkbox tip">\
                  <input type="checkbox" id="sizeCheck-'+color.data[i].id+'-'+size.data[j].id+'"\
                    data-color="'+color.data[i].id+'" data-size="'+size.data[j].id+'" name="size-map-checkbox">\
                  <label for="sizeCheck-'+color.data[i].id+'-'+size.data[j].id+'"></label>\
                </div></div>';
            }
            if((i >= 0)&&(j < 0)) {
              table += '<td class="color" style="padding:2px;"><div class="nowrap" style="padding:3px;border-right: 27px solid '+color.data[i].color+';\
                border-image:url('+admin.SITE+'/'+color.data[i].img+') 50;">'+color.data[i].name+'</div></td>';
            } else {
              table += '<td>'+sizeText+sizeD+colorD+checkbox+'</td>';
            }
          }
          table += '</tr>';
        }
        $('.size-map tbody').html(table);
        $('.set-size-map').show();
        $('.size-map').data('type', 'only-all');
        return;
      }

      // сетка только размерная
      if(size != '') {
        for(i = -1; i < 1; i++) {
          table += '<tr>';
          for(j = 0; j < size.data.length; j++) {
            var sizeD = '';
            if((j >= 0)&&(i == -1)) {
              sizeD = size.data[j].name;
            }
            var checkbox = '';
            if((i >= 0)&&(j >= 0)) {
              checkbox = '\
                <input class="color" value="" style="display:none;">\
                <input class="size size-'+size.data[i].id+'" value="'+size.data[j].name+'" style="display:none;">\
                <div class="checkbox tip">\
                  <input type="checkbox" id="sizeCheck--'+size.data[j].id+'"\
                    data-color="" data-size="'+size.data[j].id+'" name="size-map-checkbox">\
                  <label for="sizeCheck--'+size.data[j].id+'"></label>\
                </div>';
            }
            table += '<td>'+sizeD+checkbox+'</td>';
          }
          table += '</tr>';
        }
        $('.size-map tbody').html(table);
        $('.set-size-map').show();
        $('.size-map').data('type', 'only-size');
        return;
      }

      // сетка цветов
      if(color != '') {
        for(i = 0; i < 1; i++) {
          table += '<tr>';
          for(j = 0; j < color.data.length; j++) {
            var colorD = '';
            if((i >= 0)&&(j == -1)) {
              colorD = color.data[i].name;
            }
            var checkbox = '';
            if((i >= 0)&&(j >= 0)) {
              checkbox = '\
                <input class="color" value="'+color.data[j].name+'" style="display:none;">\
                <input class="size" value="" style="display:none;">\
                <input class="color-'+color.data[j].id+'" value="'+color.data[j].color+'" style="display:none;">\
                <div class="checkbox tip">\
                  <input type="checkbox" id="sizeCheck-'+color.data[j].id+'-"\
                    data-color="'+color.data[j].id+'" data-size="" name="size-map-checkbox">\
                  <label for="sizeCheck-'+color.data[j].id+'-"></label>\
                </div>';
              checkbox += '<label class="bigLabel" for="sizeCheck-'+color.data[j].id+'-"></label>';
            }
            table += '<td class="color" style="background:'+color.data[j].color+';border:1px solid #e6e6e6;" title="'+color.data[j].name+'">\
            '+colorD+checkbox+'</td><td style="border:0!important;width:10px;background:#f5f5f5;"></td>';
          }
          table += '</tr>';
        }
        $('.size-map tbody').html(table);
        $('.set-size-map').show();
        $('.size-map').data('type', 'only-color');
        return;
      }

      $('.set-size-map').hide();
      $('.size-map').hide();
    },

    /**
     * Выполняет выбранную операцию со всеми отмеченными характеристиками
     * operation - тип операции.
     */
    runOperation: function(operation, skipConfirm) {
      if(typeof skipConfirm === "undefined" || skipConfirm === null){skipConfirm = false;}
      var property_id = [];
      $('#tab-userField-settings .main-table tbody tr').each(function() {              
        if($(this).find('input[name=property-check]').prop('checked')) {  
          property_id.push($(this).attr('id'));
        }
      });           
    
      if (skipConfirm || confirm(lang.RUN_CONFIRM)) {        
        admin.ajaxRequest({
          mguniqueurl: "action/operationProperty",
          operation: operation,
          property_id: property_id,
        },
        function(response) { 
          admin.refreshPanel();  
        });
      }
    },   

  	getTablePropertyGroup: function() {
  	  admin.ajaxRequest({
        mguniqueurl: "action/getTablePropertyGroup",
  			lang: $('#edit-property-group .select-lang').val()
      },
      function(response) {
				var html = '<thead class="yellow-bg">\
					<tr><th colspan=3></th></tr></thead><tbody class="table-group-property">';
		
				function buildRowsUserField(element, index, array) {
				  html += '<tr id=' + element.id + ' data-id=' + element.id + '>\
					  <td class="mover"><i class="fa fa-arrows" aria-hidden="true"></i></td>\
					  <td class="name"><input type="text" value="'+element.name+'"></td>\
					  <td class="actions">\
						<ul class="action-list text-right">\
						 <li class="delete-property"><a class="fa fa-trash tip " href="javascript:void(0);" aria-hidden="true" title="' + lang.DELETE + '"></a></li>\
						</ul>\
					  </td>\
					</tr>';
				};
					
				if (response.data.length != 0) {
				  response.data.forEach(buildRowsUserField);
				} 
				
				html += '</tbody>';
						
				$('.userPropertyGroupTable').html(html);
				admin.sortable('.table-group-property','property_group');			
      });	
  	},
	
  	deletePropertyGroup: function(id) {
  	  admin.ajaxRequest({
        mguniqueurl: "action/deletePropertyGroup",
  			 id:id
        },
        function(response) {});	
  	 },
	
	
  	savePropertyGroup: function() {	
      var fields = [];
      $('.userPropertyGroupTable input').each(function(index,element) {          
        fields.push({'id':$(this).parents('tr').data('id'), 'val':$(this).val()});
      });

      admin.ajaxRequest({
        mguniqueurl: "action/savePropertyGroup",   
        lang: $('#edit-property-group .select-lang').val(),      
        fields: fields    
      },
      function(response) {
        admin.indication(response.status, 'Сохранено');
        admin.closeModal('#edit-property-group');
      })   
  	},
	
	
    /**
     *  Сохранение привязки к категориям.
     */
    savePropertyCat: function(id) {
      var toCompare = []; 
      var category = '';
      $('#edit-category select[name=listCat] option').each(function() {
        if ($(this).prop('selected')) {
          category += $(this).val() + userProperty.delimetr;
          toCompare.push($(this).val());
        }
      });
      category = category.slice(0, -1); 

      var removed = [];
      var cats = $('#edit-category select[name=listCat]').val();
      var showConfirm = false;

      $.each(userProperty.initialCats, function(index, item){
        if($.inArray(item, cats) == -1){
          showConfirm = true;
          removed.push(item);
        }
      });

      if (showConfirm) {
        var confirmText = lang.ERROR_MESSAGE_21+userProperty.modalName+lang.TO_CATEGORY;
        $.each(removed, function(index, item){
          var catText = $('#edit-category select[name=listCat] option[value='+item+']').text();
          while (catText.slice(0,6) == '  --  ') {
              catText = catText.substr(6);
          }
          confirmText += catText+',\n';
        });
        confirmText = confirmText.slice(0, -2)+'?';

        if (confirm(confirmText)) {
          admin.ajaxRequest({
            mguniqueurl: "action/saveUserPropWithCat",
            id: id,
            category: category
          },
          function(response) {
            admin.indication(response.status, response.msg);
            admin.closeModal($('#edit-category'));
          });
        }
      }
      else{
        admin.ajaxRequest({
          mguniqueurl: "action/saveUserPropWithCat",
          id: id,
          category: category
        },
        (function(response) {
          admin.indication(response.status, response.msg);
          admin.closeModal($('#edit-category'));
        }));
      }
    },

    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     */
    openModalWindow: function(type, id) {

      $('#edit-category .save-button').attr('id', id);

      switch (type) {
        case 'edit':
          {
            var name = $('.userPropertyTable tr[id=' + id + '] td[class=name]');
            var nameVal = name.text();
            if(nameVal == '') {
              var name = $('.userPropertyTable tr[id=' + id + '] input[name=name]');
              var nameVal = name.val();
            } 
            $('#modalTitle').text(lang.STNG_LIST_CAT + ': "' + nameVal + '"');
            userProperty.connectionCat(id, nameVal);
            break;
          }
      }

      // Вызов модального окна.
      admin.openModal($('#edit-category'));
      // option.push('data-close-on-click:false');

    },
    connectionCat: function(id, name) {
      var nameVal = name;
      userProperty.modalName = name;

      admin.ajaxRequest({
        mguniqueurl: "action/getConnectionCat",
        id: id
      },
      (function(response) {
        html = response.data.optionHtml;      
        $('.user-fields-desc-wrapper .propertyName').text(nameVal);
        $('#select-category-form-wrapper select[name=listCat]').html(html);       
        $('.cancelSelect').click(function() {
          $('select[name=listCat] option').prop('selected', false);
        });       
        userProperty.convertCategoryIdToOption(response.data.selectedCatIds);
        userProperty.initialCats = $('#edit-category select[name=listCat]').val();
      }),
              $('#select-category-form-wrapper')
              );

    },

    /**
     * Выделяет категории в списке, которые привязаны к характеристике
     */
    convertCategoryIdToOption: function(selectedCatIds) {
      htmlOptionsSelected = selectedCatIds.split(',');
    
      function buildOption(element, index, array) {
        $('select[name="listCat"] [value="' + element + '"]').prop('selected', 'selected');
        
        userProperty.listCategoryConnect.push(element);
      }
     
      htmlOptionsSelected.forEach(buildOption);
    },

    // очищает модалку характеристик
    clearFields: function() {
      $('#user-property-edit [name=name]').val('');
      $('#user-property-edit [name=description]').val('');
      $('#user-property-edit [name=unit]').val('');
      $('#user-property-edit [name=mark]').val('');
      $('#user-property-edit table tbody').html('');

      $('#user-property-edit .add-property-field').hide();
      $('#user-property-edit .main-table').hide();
    },

    // заполняет модалку данными
    fillFields: function(id) {
      $('#user-property-edit .save-button').attr('id', id);   
      admin.ajaxRequest({
        mguniqueurl: "action/getProperty",
        id: id,
        lang: $('#tab-userField-settings .select-lang').val()
      },
      function(response) {
        var property = response.data;
        if(property.type == 'none') {
          $('#user-property-edit [name=type]').prop('disabled', false);
        } else {
          $('#user-property-edit [name=type]').prop('disabled', true);
        }
        userProperty.loadMargin(id, property.type);
        $('#user-property-edit .save-button').data('id', id);
        // выставляем загруженные параметры для характеристики в модалке
        $('#user-property-edit [name=name]').val(property.name);
        $('#user-property-edit [name=description]').val(property.description);
        $('#user-property-edit [name=unit]').val(property.unit);
        $('#user-property-edit [name=mark]').val(property.mark);		
    		$('#user-property-edit [name=group]').val(property.group_id);	
    	
    		$('#user-property-edit .select-group').html('');		
    		$('#user-property-edit .select-group').append('<option value="0">'+lang.NO_SELECT+'</option>');
    		property.selectGroup.forEach(function (element, index, array) {		
    		  $('#user-property-edit .select-group').append('<option value="'+element.id+'">'+element.name+'</option>');
    		}		
    		)
    		$('#user-property-edit .select-group option[value='+property.group_id+']').prop('selected', true);
        // если нужны наценки, показываем их
        userProperty.showPropertyMargin(property.type);
        // подключаем селекты
        var is_string = false;
        if (property.type == 'string') {
          is_string = true
        }
        typefilter =  '<option value="checkbox">'+lang.BY_CHECKBOX+'</option>';      
        typefilter += '<option value="select">'+lang.BY_LIST+'</option>';   
        typefilter += '<option value="slider" style="display:'+(is_string?'auto':'none')+'">'+lang.SLIDER+'</option>';
        $('#user-property-edit [name=type-filter]').html(typefilter);
        // 
        type =  '<option value="none">' + lang.NO_SELECT + '</option>';
        type += '<option value="string">' + lang.STRING + '</option>';
        type += '<option value="assortment">' + lang.ASSORTMENT + '</option>';
        // type += '<option value="select">' + lang.SELECT + '</option>';
        type += '<option value="assortmentCheckBox">' + lang.ASSORTMENTCHECKBOX + '</option>';
        type += '<option value="textarea">'+lang.TEXTAREA+'</option>';

        type += '<option value="color">' + lang.COLOR + '</option>';
        type += '<option value="size">' + lang.SIZE + '</option>';

        $('#user-property-edit [name=type]').html(type);

        $('#user-property-edit [name=type] option[value='+property.type+']').prop('selected', 'selected');
        $('#user-property-edit [name=type-filter] option[value='+property.type_filter+']').prop('selected', 'selected');
      });
    },

    // показыввает наценки, если по типу подходят
    showPropertyMargin: function(type) {
      switch(type) {
        case 'assortment':
        case 'assortmentCheckBox':
          $('#user-property-edit .add-property-field').show();
          $('#user-property-edit .main-table').show();
          $('.hideOnColor').show();
          $('.hideOnsize').show();
          $('.marginColumtTitle').html(lang.DISCOUNT_UP);
          break;
        case 'color':
          $('#user-property-edit .add-property-field').show();
          $('#user-property-edit .main-table').show();
          $('.hideOnColor').hide();
          $('.hideOnsize').hide();
          $('.marginColumtTitle').html(lang.COLOR);
          break;
        case 'size':
          $('#user-property-edit .add-property-field').show();
          $('#user-property-edit .main-table').show();
          $('.hideOnColor').show();
          $('.hideOnsize').hide();
          $('.marginColumtTitle').html('');
          break;

        default:
          $('#user-property-edit .add-property-field').hide();
          $('#user-property-edit .main-table').hide();
          $('.hideOnColor').show();
          $('.hideOnsize').show();
          $('.marginColumtTitle').html(lang.DISCOUNT_UP);
      }

      if(type == 'string') {
        $('#user-property-edit [name=type-filter] option[value=slider]').show();
      } else {
        $('#user-property-edit [name=type-filter] option[value=slider]').hide();
      }

      propId = $('#user-property-edit .save-button').data('id');
      userProperty.loadMargin(propId);
    },

    // сохраняет редактируемую характеристику
    saveFields: function(close) {
      close = typeof close !== 'undefined' ? close : true;
      // собираем данные
      var id = $('#user-property-edit .save-button').data('id');
      var name = $('#user-property-edit [name=name]').val();
      var description = $('#user-property-edit [name=description]').val();
      var unit = $('#user-property-edit [name=unit]').val();
      var mark = $('#user-property-edit [name=mark]').val();
	  var group = $('#user-property-edit .select-group').val();

      var type = $('#user-property-edit [name=type]').val();
      var typefilter = $('#user-property-edit [name=type-filter]').val();
      // 
      var dataProp = {};
      var count = 1;
      $('#user-property-edit .main-table tbody tr').each(function() {
        dataProp[count] = {};
        dataProp[count]['id'] = $(this).data('id');
        dataProp[count]['name'] = $(this).find('[name=prop-name]').val();
        dataProp[count]['margin'] = $(this).find('[name=prop-margin]').val();
        dataProp[count]['color'] = $(this).find('[name=color]').data('color');
        count++;
      });
      admin.ajaxRequest({
        mguniqueurl: "action/saveUserProperty",
        id: id,
        name: name,
        type: type,
        description: description,
        type_filter: typefilter,
        unit: unit,
        mark: mark,
		group_id:group,
        dataProp: dataProp,
        lang: $('#tab-userField-settings .select-lang').val()
      },
      function(response) {
        if(close) {
          admin.indication(response.status, response.msg);
          admin.closeModal('#user-property-edit');
          admin.refreshPanel();
        } else {
          userProperty.loadMargin(propId);
        }
      });
    },

    deleteImgMargin: function(id) {
      propId = $('#user-property-edit .save-button').data('id');
      admin.ajaxRequest({
        mguniqueurl: "action/deleteImgMargin",
        id: id,
      },
      function(response) {
        $('#user-property-edit .new-added-properties').hide();
        userProperty.loadMargin(propId)
      });
    },

    // добавляет новое поле для наценок
    addPropertyMargin: function(id) {
      propId = $('#user-property-edit .save-button').data('id');
      name = $('#user-property-edit [name=margin-name]').val();
      margin = $('#user-property-edit [name=margin-value]').val();
      admin.ajaxRequest({
        mguniqueurl: "action/addPropertyMargin",
        propId: propId,
        name: name,
        margin: margin
      },
      function(response) {
        $('#user-property-edit .new-added-properties').hide();
        userProperty.saveFields(false)
      });
    },

    // загружает наценки для характеристик
    loadMargin: function(id, type) {
      admin.ajaxRequest({
        mguniqueurl: "action/loadPropertyMargin",
        id: id,
        lang: $('#tab-userField-settings .select-lang').val()
      },
      function(response) {
        $('#user-property-edit table tbody').html(userProperty.htmlMargin(response.data, type));

        if($('#user-property-edit table tbody tr').length > 0) {
          $('#user-property-edit table').show();
        } else {
          $('#user-property-edit table').hide();
        }

        $('.admin-center #user-property-edit .color').hover(function() {
          var thisIs = $(this);
          $(this).ColorPicker({
            color: admin.rgb2hex($(this).css('backgroundColor')),
            onShow: function (colpkr) {
              $(colpkr).fadeIn(0);
              return false;
            },
            onHide: function (colpkr) {
              $(colpkr).fadeOut(0);
              return false;
            },
            onChange: function (hsb, hex, rgb) {
              $(thisIs).data('color', '#' + hex);
              $(thisIs).css('backgroundColor', '#' + hex); 
            }
          });
        });
      });
    },

    // создает верстку для наценок харктеристик
    htmlMargin: function(data, type) {
      type = typeof type !== 'undefined' ? type : $('#user-property-edit [name=type]').val();
      var html = '';
      if(data != null) {
        for(i = 0; i < data.length; i++) {
          html += '<tr data-id="'+data[i].id+'">';
          html += '<td class="mover" style="width: 40px;"><i class="fa fa-arrows ui-sortable-handle" aria-hidden="true"></i></td>';
          html += '<td>';
          html += '<input type="text" name="prop-name" value="'+data[i].name+'" style="margin:0;">';
          html += '</td>';
          html += '<td>';
          switch(type) {
            case 'color':
              html += '<div class="color" name="color" data-color="'+data[i].color+'" style="background:'+data[i].color+';display:inline-block;"></div>\
                <form class="imageFormToProp" method="post" noengine="true" enctype="multipart/form-data" style="display:inline-block;position:relative;">\
                  <label class="img-iploader-to-prop tip fl-left" for="'+data[i].id+'" data-hasqtip="64" title="'+(data[i].img==''?lang.UPLOAD_PROP_IMG:lang.UPLOAD_PROP_IMG_DEL)+'" \
                    style="height:30px;width:30px;border: 1px solid #aaa;display:inline-block;background:url('+admin.SITE+'/'+data[i].img+');background-size:cover;">\
                      <i style="margin: 7px 6px; '+(data[i].img==''?'':'display:none;')+'" class="fa fa-image" ></i>\
                  </label>\
                  <i style="position:absolute;top:-5px;right:-5px;color:red;cursor:pointer; '+(data[i].img!=''?'':'display:none;')+'" class="fa fa-times deleteImg" ></i>\
                  <input type="file" id="'+data[i].id+'" name="propImg" class="add-img tool-tip-top" style="display:none;">\
                </form>';
              break;
            case 'size':
              html += '';
              break;

            default:
              html += '<input type="text" name="prop-margin" value="'+data[i].margin+'" style="margin:0;">';
              break;
          }
          html += '</td>';
          html += '<td class="action" style="text-align: right;font-size: 16px;">';
          html += '<a href="javascript:void(0)" style="color:#333;padding:2px;" class="fa fa-trash"></a>';
          html += '</td>';
          html += '</tr>';
        }
      }

      return html;
    },

    // загружает картинку к характеристике
    addImageToProp:function(id, form) {
     $('.img-loader').show();
      $(form).ajaxForm({
        type:"POST",
        url: "ajax",
        data: {
          mguniqueurl:"action/addImageToProp",
          propDataId:id
        },
        cache: false,
        dataType: 'json',
        success: function(response) {
          admin.indication(response.status, response.msg);
          $('.img-loader').hide();
          propId = $('#user-property-edit .save-button').data('id');
          userProperty.loadMargin(propId);
        }
      }).submit();
    },

    // удаляет поле с наценкой
    deletePropertyMargin: function(id) {
      userProperty.saveFields(false);
      admin.ajaxRequest({
        mguniqueurl: "action/deletePropertyMargin",
        id: id
      },
      function(response) {
        propId = $('#user-property-edit .save-button').data('id');
        userProperty.loadMargin(propId);
      });
    },

    /**
     * Получает все значения свойств из модального окна для сохранения в БД
     */
    getUserFields: function() {
      var data = {};

      // собираем всю информацию о пользовательских характеристиках
      $('.userField .property, .addedProperty .property').each(function() {
        var name = $(this).attr('name');
        // определяем тип характеристики по типу тэга для ее описания
        switch($(this)[0].tagName) {
          // считываем селекты
          case 'SELECT':
            var typeView = "";
            data[name] = {};
            data[name]['type'] = 'select';
            $(this).parents('.price-body').find('.setup-type').each(function() {
              if($(this).hasClass('selected')) {
                // data[name]['type-view'] = $(this).data('type');
                typeView = $(this).data('type');
              }
            });
            $(this).find('option').each(function() {
              if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
              data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
              data[name][$(this).data('id')]['prop-data-id'] = $(this).data('prop-data-id');
              data[name][$(this).data('id')]['val'] = $(this).val();
              if($(this).prop('selected')) {
                data[name][$(this).data('id')]['active'] = 1;
                del = false;
              } else {
                data[name][$(this).data('id')]['active'] = 0;
                del = true;
              }
              data[name][$(this).data('id')]['type-view'] = typeView;
              if(del) delete data[name][$(this).data('id')];
            });
            break;
          // считываем инпуты
          case 'INPUT':
            // для считываения чекбоксов и селектов
            if($(this).attr('type') == 'checkbox') {
              var propId = $(this).parents('.assortmentCheckBox').data('property-id');
              if(data[propId] == undefined) data[propId] = {};
              if(data[propId][$(this).data('id')] == undefined) data[propId][$(this).data('id')] = {};
              data[propId]['type'] = 'checkbox';
              data[propId][$(this).data('id')]['val'] = $(this).parents('.checkbox').find('span').html();
              data[propId][$(this).data('id')]['prop-data-id'] = $(this).data('prop-data-id');
              if($(this).prop('checked')) {
                data[propId][$(this).data('id')]['active'] = 1;
                del = false;
              } else {
                data[propId][$(this).data('id')]['active'] = 0;
                del = true;
              }
              data[propId][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
              if(del) delete data[propId][$(this).data('id')];
            } 
            // для полей типа строка
            else {
              data[name] = {};
              data[name]['type'] = 'input';
              if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
              data[name][$(this).data('id')]['val'] = $(this).val();
              data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
            }
            break;
          // считываем поля типа textarea
          case 'A':
            data[name] = {};
            data[name]['type'] = 'textarea';
            if(data[name][$(this).data('id')] == undefined) data[name][$(this).data('id')] = {};
            data[name][$(this).data('id')]['val'] = $(this).parent().find('.value').html();
            data[name][$(this).data('id')]['prod-id'] = $('.save-button').attr('id');
            break;
        }
      });
      return data;
    },

    // преобразует системные записи типов в понятные пользователю
    typeToRead: function(type) {
      switch (type) {
        case 'none':
          {
            return lang.NO_SELECT
            break;
          }
        case 'string':
          {
            return lang.STRING
            break;
          }
        case 'select':
          {
            return lang.SELECT
            break;
          }
        case 'assortment':
          {
            return lang.ASSORTMENT
            break;
          }
        case 'assortmentCheckBox':
          {
            return lang.ASSORTMENTCHECKBOX
            break;
          }
        case 'textarea':
          {
            return lang.TEXTAREA
            break;
          }

        case 'color':
          {
            return lang.DIMENSION_GRID_COLOR
            break;
          }
        case 'size':
          {
            return lang.DIMENSION_GRID_SIZE
            break;
          }
      }
    },

    /**
     * Вывод имеющихся настроек в  разделе пользовательские характеристики
     */
    print: function(cat_id,update, page, filter) {
      //если список была ранее загружен, то не повторяем этот процесс
      if ($('.userField-settings-list').text() != "" && !update) {
        return false;
      }
      // получаем с сервера все доступные пользовательские параметры
      admin.ajaxRequest(              {
        mguniqueurl: "action/getUserProperty",
        cat_id: cat_id,
        page: page,
        name: $('#tab-userField-settings form[name="filter"] input[name="name[]"]').val(),
        type: $('#tab-userField-settings form[name="filter"] select[name="type"]').val(),
      },
      function(response) {
	  
        var html = '<table id="userPropertySetting" class="main-table">\
            <thead class="yellow-bg">\
              <th class="border-top checkbox-cell" style="width:30px;">\
                <div class="checkbox">\
                  <input type="checkbox" id="c-all">\
                  <label for="c-all" class="check-all-page"></label>\
                </div>\
              </th>\
              <th class="border-top" style="width: 20px;"> id </th>\
              <th class="border-top" style="width: 20px;"></th>\
              <th class="border-top" style="width: 180px;">' + lang.STNG_USFLD_TYPE + '</th>\
              <th class="border-top">' + lang.STNG_USFLD_NAME + '</th>\
              <th style="width:200px;" class="border-top text-right">' + lang.ACTIONS + '</th>\
            </thead><tbody class="userPropertyTable">';
        function buildRowsUserField(element, index, array) {
          var is_string = false;
          if (element.type == 'string') {
            is_string = true
          }
          
          var activity = element.activity==='1'?'active':'';        
          // var titleActivity = element.activity==='1'?lang.ACT_V_PROP:lang.ACT_UNV_PROP;  
          var titleActivity = lang.ACT_V_PROP;  
          
          var filter = element.filter==='1'?'active':'';        
          // var titleFilter = element.filter==='1'?lang.ACT_FILTER_PROP:lang.ACT_UNFILTER_PROP;  
          var titleFilter = lang.ACT_FILTER_PROP;  

          if(element.mark != '') {
            var mark = ' <span class="badge">'+element.mark+'</span>';
          } else {
            var mark = '';
          }

          html += '<tr id=' + element.id + ' data-id=' + element.id + '>\
              <td class="check-align" style="cursor:move;">\
                <div class="checkbox">\
                  <input type="checkbox" id="c' + element.id + '" name="property-check">\
                  <label for="c' + element.id + '"></label>\
                </div>\
              </td>\
              <td class="id">'+element.id+'</td>\
              <td class="mover"><i class="fa fa-arrows" aria-hidden="true"></i></td>\
              <td class="type"><span value="' + element.type + '">' + userProperty.typeToRead(element.type) + '</span></td>\
              <td class="name">' + element.name + mark + '</td>\
              <td class="actions">\
                <ul class="action-list text-right">\
                  <li class="visible tool-tip-bottom" data-id="'+element.id+'" title="'+titleActivity+'" ><a href="javascript:void(0);" class="fa fa-eye '+activity+'"></a></li>\
                  <li class="edit-row"><a class="fa fa-pencil tip " href="javascript:void(0);" aria-hidden="true" title="' + lang.EDIT + '"></a></li>\
                  <li class="see-order connection"><a class="fa fa-list-ol tip " data-id="'+element.id+'" href="javascript:void(0);" aria-hidden="true" title="' + lang.STNG_USFLD_CAT + '"></a></li>\
                  <li class="filter-prop-row"><a class="fa fa-filter tip  '+filter+'" href="javascript:void(0);" aria-hidden="true" title="'+titleFilter+'"></a></li>\
                  <li class="delete-property"><a class="fa fa-trash tip " href="javascript:void(0);" aria-hidden="true" title="' + lang.DELETE + '"></a></li>\
                </ul>\
              </td>\
            </tr>';
        };

        if (response.data.allProperty.length != 0) {
          response.data.allProperty.forEach(buildRowsUserField);
        } else {
          html += '<tr class="tempMsg">\
              <td colspan="6" align="center">' + (response.data.displayFilter ? lang.USER_NONE : lang.STNG_USFLD_MSG) + '</td>\
             </tr>';
        }
        ;
        html += '</tbody></table>';
        $('.userField-settings-list').html(html);
        $('#tab-userField-settings .filter-container').html(response.data.filter);
        if (response.data.displayFilter) {
           $('#tab-userField-settings .filter-container').show();
        }
        if ($('.section-settings #tab-userField-settings .mg-pager').length > 0) {
          $('.section-settings #tab-userField-settings .mg-pager').remove();
        } 
        $('#tab-userField-settings .to-paginator').html(response.data.pagination);
        if (page) {
          settings.closeAllTab();
          $('#tab-userField').parent('li').addClass('ui-state-active');    
          $('#tab-userField-settings').css('display', 'block');
        }
        admin.sortable('.userPropertyTable','property');
        admin.initToolTip();


      },
              $('.userField-settings-list')
              );
    },
    //Добавляет новую строку
    addRow: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/addUserProperty"
      },
      function(response) {

        admin.indication(response.status, response.msg);
         
        var html = '<tr id=' + response.data.allProperty.id + '>\
                <td class="check-align" style="cursor:move">\
                  <div class="checkbox">\
                    <input type="checkbox" id="c' + response.data.allProperty.id + '" name="property-check">\
                    <label for="c' + response.data.allProperty.id + '"></label>\
                  </div>\
                </td>\
                <td class="id">' + response.data.allProperty.id + '</td>\
                <td class="mover"><i class="fa fa-arrows ui-sortable-handle" aria-hidden="true"></i></td>\
                <td class="type"><span value="' + response.data.allProperty.type + '">' + lang.NO_SELECT + '</span></td>\
                <td class="name">' + response.data.allProperty.name + '</td>\
                  <td class="actions text-right">\
                    <ul class="action-list" style="width:100%;">\
                      <li class="visible tool-tip-bottom" data-id="'+response.data.allProperty.id+'" title="'+lang.ACT_V_PROP+'" ><a href="javascript:void(0);" class="fa fa-eye active"></a></li>\
                      <li class="edit-row" id="' + response.data.allProperty.id + '"><a class="tool-tip-bottom fa fa-pencil" href="javascript:void(0);" title="' + lang.EDIT + '"></a></li>\
                      <li class="see-order connection" id="' + response.data.allProperty.id + '"><a class="tool-tip-bottom fa fa-list-ol" href="javascript:void(0);" title="' + lang.STNG_USFLD_CAT + '"></a></li>\
                      <li class="filter-prop-row tool-tip-bottom" data-id="'+response.data.allProperty.id+'" title="'+lang.ACT_UNFILTER_PROP+'" ><a href="javascript:void(0);" class="fa fa-filter active"></a></li>\
                      <li class="delete-property" id="' + response.data.allProperty.id + '"><a class="tool-tip-bottom fa fa-trash" href="javascript:void(0);" title="' + lang.DELETE + '"></a></li>\
                    </ul>\
                  </td>\
              </tr>';

        if ($(".userField-settings-list tr[class=tempMsg]").length != 0) {
          $(".userPropertyTable").html('');
        }

        $('.userPropertyTable').prepend(html);

        $('.userField-settings-list tr:eq(1) .edit-row').click();
      });
    },
    deleteRow: function(id) {
      if (confirm(lang.DELETE + '?')) {
        admin.ajaxRequest({
          mguniqueurl: "action/deleteUserProperty",
          id: id
        },
        (function (response) {
          admin.indication(response.status, response.msg);
          if (response.status == 'success') {
            $('.userPropertyTable tr[id=' + id + ']').remove();
            if ($(".userPropertyTable tr").length == 0) {
              var html = '<tr class="tempMsg">\
                  <td colspan="5" align="center">' + lang.STNG_USFLD_MSG + '</td>\
                 </tr>';
              $('.userPropertyTable').append(html);
            }
          };
        }));
      }
    },
    /**
     * Получает весь набор доступных пользовательских характеристик из базы
     */
    getUserFromBD: function() {

    },
    /**
     * сортировка свойств по алфавиту
     */
    propertySort: function(arr) {
      return arr.sort(function(a, b) {

        var compA = a.toLowerCase();
        var compB = b.toLowerCase();
        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
      })
    },
    /*
     * Возвращает значение наценки из характеристики, которое отделяется от названия #Цена#
     * пример красный#700# получим 700 и название красный.
     */
    getMarginToProp: function(str) {
      // str = admin.htmlspecialchars(str);
      var margin = /#([\d\.\,-]*)#$/i.exec(str);      
      var parseString = {name: str, margin: 0}
      if (margin != null) {
        parseString = {name: str.slice(0, margin.index), margin: margin[1]}
      }    
      return parseString;
    },
            
    // Сохраняет установленные наценки для каждого пункта характеристик
    // hiddenData - скрытое значение пунктов, записанное в одну строку с разделителями,
    // propId - номер характеристики        
    saveMagrin: function(propId, type) {
      var hiddenData = $('.userPropertyTable tr[id=' + propId + '] .hiddenPropertyData');

      var hiddenDataText = hiddenData.text();
      if(type=='string') {
        hiddenDataText = hiddenData.html();
      }

      if($('.userPropertyTable tr[id=' + propId + '] .itemData ').length!=0) {
        hiddenDataText = "";    
     
        $('.userPropertyTable tr[id=' + propId + '] .itemData ').each(function() {

          //если в поле введено число болье нуля то записываем его к характеристикам
          var margin = $(this).find('.setMargin input[type=text]').val();
          if (margin * 1 != 0 && !isNaN(margin)) {
            margin = '#' + margin + '#';
          } else {
            margin = "";
            
            if(type=='select') {
              margin = "#0#";
            }
            
          }
          if (type=='assortmentCheckBox'&&$('.propertyDataNameEdit input[name=valuenew]').is(':visible')) {
            hiddenDataText += $(this).find('.propertyDataNameEdit input[name=valuenew]').val() + margin + userProperty.delimetr;
          } else {
            hiddenDataText += $(this).find('.propertyDataName').text() + margin + userProperty.delimetr;
          }
          
        });
        hiddenDataText = hiddenDataText.slice(0, -1);
      }
      
      hiddenData.text(hiddenDataText);
    },

    /**
     * 
     * @param {type} propId - id характеристики 
     * @param {type} val - значение  по умолчанию
     * @returns {undefined}
     */
    setDefVal: function(propId, val) {
      var data = $('.userPropertyTable tr[id=' + propId + '] td[class=default]').text(val);
    },
    /**
     * Панель для настройки наценок к каждому товару
     * select - объект содержащий все доступные значения характеристики
     */
    panelMargin: function(select) {

      var html = '<div class = "panelMargin custom-popup">';
      select.find('option').each(function() {

        var parseProp = userProperty.getMarginToProp($(this).val());
        var selected = '';
        if ($(this).attr('selected') == 'selected' || $(this).prop('selected')) {
          selected = ' selected="selected" ';
        }
        var currency = $('#add-product-wrapper .currency-block select[name=currency_iso] option:selected').text();
        html += '<div class="row">\
                  <div class="panelMargin-unit small-6 columns">\
                    <label>' + parseProp.name + ':</label>\
                  </div>\
                  <div class="small-6 columns">\
                    <input type="text" '+ selected + " value='" + parseProp.margin + "' data-propname='" + parseProp.name + "' class='price-input'/>"+currency+
                  '</div>\
                </div>';
      });
      html += '<div class="row"><div class="large-12 columns">\
                <a href="javascript:void(0);" class="apply-margin tool-tip-bottom button success fl-right" title="'+lang.APPLY+'">'+lang.APPLY+'</a>\
              </div></div></div>';
      return html;
    }, 
    /**
     * Применяет установленные в panelMargin наценки
     * tr - строка таблицы полей в которой хранятся наценки и список
     */
    applyMargin: function(tr) {
      var i = 0;
      // формируем новый список из данных в панели наценок
      tr.find('.panelMargin input[type=text]').each(function() {
        if(isNaN($(this).val())) {
          $(this).val('0');
        } 
        tr.find('select option:eq('+i+')').val($(this).data('propname') + '#' + $(this).val() + '#');
        i++;
      });
      // вставляем сформированный список  на место
    },
    /**
     * Заполняет поля модального окна продуктов данными
     * allProperty - объект содержащий все доступные пользовательские характеристики
     * userFields - объект содержит  значения пользовательских характеристик для текущего продукта
     */
    createUserFields: function(container, userFields, allProperty) {

      if (!allProperty)
        return false;
      var htmlOptions = '';
      var htmlOptionsSelected = '';
      var htmlOptionsSetup = ''; // установленные наценки для текущего продукта
      var htmlUserField = '';
      var htmlCheckBox = '';
      var curentProperty = '';
      //строит html элементы из полученных данных
      function printToLog(element, index, array) {
        // console.log("a[" + element.id + "] = " +
        //         " - " + element.name +
        //         " - " + element.type +
        //         " - " + element.default +
        //         " - " + element.data
        //         );
      }

      // Проверяет,
      // было ли уже установлено пользовательское свойство,
      // и возвращает его значение
      // propertyId - идентификатор свойства
      function getUserValue(propertyId) {
        var userValue = false;
        if (!userFields) {
          return;     
        }
        userFields.forEach(function(element, index, array) {         
          if (element.property_id == propertyId) {
            userValue = {value: element.value, product_margin: element.product_margin, type_view:element.type_view};
          }
        });
        return userValue;
      }


      function buildCheckBox(element, index, array) {
        var checked = '';
    
        // для мульти списка проверяем наличие  значения в массиве htmlOptionsSelected
        // if (htmlOptionsSelected instanceof Array) {
        //   if (htmlOptionsSelected.indexOf(element+'#0#') != -1 || htmlOptionsSelected.indexOf(element) != -1) {
        //     checked = 'checked="checked"';
        //   }
        // } else {
          // для простого селекта соответствие значению htmlOptionsSelected
          // if (htmlOptionsSelected == element) {
          //   checked = 'checked="checked"';
          // }
        // }
        var random = Math.random();
        if(element.id) {
          id = element.id;
        } else {
          id = 'temp-'+tmpCount;
          tmpCount++; 
        }
        if(element.active == 1) {
          checked = 'checked="checked"';
        } else {
          checked = '';
        }
        // тут надо соорудить что-то для чекбоксов, чтобы потом получить значения
        htmlCheckBox += '<div class="checkbox" style="position:relative;margin-bottom:5px;">\
                          <input type="checkbox" data-id="'+id+'" data-prop-id="'+element.prop_id+'" data-prop-data-id="'+element.prop_data_id+'" id="' + element.name + random + '" class="propertyCheckBox property" ' + checked + ' name="' + admin.htmlspecialchars(element.name) + '"/>\
                          <label for="' + element.name + random + '"></label>\
                          <span style="position:absolute;top:0px;left:23px;">' + admin.htmlspecialchars(element.name) + '</span>\
                        </div>'; 
      }
      

      function buildOption(element, index, array) {
        if(element.active == 1) {
          selected = 'selected="selected"';
        } else {
          selected = '';
        }
        if(element.id) {
          id = element.id;
        } else {
          id = 'temp-'+tmpCount;
          tmpCount++; 
        }
        htmlOptions += '<option data-id="'+id+'" data-prop-data-id="'+element.prop_data_id+'" data-margin="'+element.prop_id+'" ' + selected + ' value="' + element.name + '#' + element.margin + '#">' + element.name + '</option>';
      }
      

      //строит html элементы из полученных данных
      function buildElements(property, index, array) {        
        
        // если наименование не задано то не выводить характеристику
        if(property.name==null) {          
          return false;
        } 
       
        var html = '';
        var created = false;

        // для пользовательского поля типа string
        if (property.type == 'string') {
          // console.warn(property);
          var userValue = getUserValue(property.id);
          if(!property['data'][0]['id']) {
            property['data'][0]['id'] = 'temp-'+tmpCount;
            tmpCount++; 
          }
          var value = (userValue.value) ? userValue.value : '';

          html = '<div class="new-added-prop" style="margin-bottom:10px;"><div class="row"><div class="medium-5 small-12 columns"><label class="dashed">' + property.name + '\
               ' + (property.unit ? '('+property.unit+')' : '') + ': </label></div>'
               + '<div class="medium-7 small-11 columns"><input class="property custom-input" data-id="'+property['data'][0]['id']+'" data-margin="'+property.prop_id+'" style="margin:0;" name="' + property.id + '" type="text" value="' + admin.htmlspecialchars(property['data'][0]['name']) + '">\
               </div></div></div>';
          created = true;
        }
          // для пользовательского поля типа текстовое поле
        if (property.type == 'textarea') {
          if(!property['data'][0]['id']) {
            property['data'][0]['id'] = 'temp-'+tmpCount;
            tmpCount++; 
          }
          var userValue = getUserValue(property.id);
          var value = (userValue.value) ? userValue.value : '';
          
          html = '<div class="new-added-prop" style="margin-bottom:10px;"><div class="row"><div class="medium-5 small-12 columns"><label class="dashed">' + property.name + ': </label></div>'
               + '<div class="medium-7 small-12 columns"><a href="javascript:void(0);" class="property custom-textarea link" data-id="'+property['data'][0]['id']+'" data-margin="'+property.prop_id+'" data-name="' + property.id + '" name="' + property.id + '">Открыть редактор</a><span class="value" style="display:none">' + admin.htmlspecialchars(property['data'][0]['name']) + '</span></div></div></div>';
          created = true;
        }
       
        // для пользовательского поля типа assortment или select
        if (property.type == 'select' || property.type == 'assortment') {
          
          var multiple = (property.type == 'assortment')?'multiple':'';// определяем будет ли строиться мульти список или обычный
          
          html = '<div class="new-added-prop" style="margin-bottom:10px;"><div class="row"><div class="medium-5 small-12 columns"><label class="dashed">' + property.name + ': </label></div>'
               + '<div class="medium-7 small-12 columns"><div class="price-settings">\
               <div class="price-body"><select class="property last-items-dropdown select" name="' + property.id + '"'+multiple+' style="max-height:none;">';
          // обнуляем список опций
          htmlOptions = '';
          
          // получаем  настройки характеристики (выбранные пункты и их стоимости в текущем товаре)
          var userValue = getUserValue(property.id);
          
          var arrayValues = null;
          // если ранее настройки небыли установлены в товаре, то берутся дефолтные, заданные в разделе характеристик
          // if (userValue) {
            // arrayValues = userValue.value.split(userProperty.delimetr);
          // } else {
          //   arrayValues = property.default.split(userProperty.delimetr);
          // }
       
            htmlOptionsSelected = []; // массив выделенных пунктов списка БЕЗ ЦЕН, чтобы можно было сравнить с дефолтным пунктами и выделить нужные
            property.data.forEach(function(element, index, array) {
              var dataProp = userProperty.getMarginToProp(element);
              htmlOptionsSelected.push(dataProp);
            });


            htmlOptionsSetup = []; // массив остановленных ранее настроек для текущего товара значений
            if(userValue.product_margin) {
              userValue.product_margin.split(userProperty.delimetr).forEach(function(element, index, array) {
                var dataProp = userProperty.getMarginToProp(element);  
                htmlOptionsSetup.push(dataProp);
              });
            }

          // генерируем список опций
          property.data.forEach(buildOption);

          // присоединяем список опций к основному контенту
          html += htmlOptions;
          var options = property.data;
          // закрываем селект
          html += '</select>';
            // формируем панель кнопок устанавливающих тот или иной тип вывода характеристики
          html += '<div class="price-footer">\
                    <div class="link-holder clearfix">\
                      <a href="javascript:void(0);" class="toggle-properties link fl-left" style="display:'+(options > 3 ? 'inline-block': 'none')+'">'+lang.PROD_OPEN_CAT+'</a>\
                      <a href="javascript:void(0);" class="clear-select-property link fl-right"><span>'+lang.PROD_CLEAR_CAT+'</span></a>\
                    </div>\
                    <div class="buttons-holder clearfix">\
                      <div class="popup-holder fl-left">\
                        <a href="javascript:void(0);" class="button setup-margin-product tool-tip-bottom" title="'+lang.T_TIP_SETUP_MARGIN+'" ><span>'+lang.SETUP_MARGIN+'</span></a>\
                      </div>';
         
         
          // формирование панели настроек вывода в публичной части
          var selected = '';

          if(property.data[0].type_view=="select" || property.data[0].type_view == false) {
            selected = 'selected';
          }
          
          html += '<div class="icon-buttons clearfix fl-right">';
          html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PRINT_SELECT+'" class="tool-tip-bottom type-select setup-type '+selected+'" data-type="select"><i class="fa fa-list" aria-hidden="true"></i></a>';
          //  вывод чекбоксами доступен только для мульти селекта
          if(multiple=='multiple') {
            selected = '';
            if(property.data[0].type_view=="checkbox") {
              selected = 'selected';
            }
            html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PIRNT_CHECK+'" class="tool-tip-bottom type-checkbox setup-type '+selected+'" data-type="checkbox"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
          }
          
          selected = '';
          if(property.data[0].type_view=="radiobutton") {
            selected = 'selected';
          }
          html += '<a href="javascript:void(0);" title="'+lang.T_TIP_PIRNT_RADIO+'" class="tool-tip-bottom type-radiobutton setup-type '+selected+'" data-type="radiobutton"><i class="fa fa-dot-circle-o" aria-hidden="true"></i></a>';
          html += '</div>';  
       
          html += '</div></div></div></div></div> </div>';
          created = true;
        }
        
       
        // для пользовательского поля типа assortmentCheckBox
        if (property.type == 'assortmentCheckBox') {
          html = '<div class="new-added-prop" style="margin-bottom:10px;"><div class="row"><div class="medium-5 small-12 columns"><label>' + property.name + ':</label></div>' 
               + '<div class="medium-7 small-12 columns"><div class="assortmentCheckBox" data-property-id="' + property.id + '" style="margin-bottom:10px;">';
          // обнуляем список опций
          htmlCheckBox = '';

          // устанавливаем выбранный элемент, чтобы отловить
          // его при построении опций и выделить его в списке
          var userValue = getUserValue(property.id);
          htmlOptionsSelected = (userValue.value) ? userValue.value.split(userProperty.delimetr) : (property.default ? property.default.split(userProperty.delimetr) : '');

          curentProperty = property.id;
          // генерируем список чекбоксов
          property.data.forEach(buildCheckBox);

          // присоединяем список опций к основному контенту
          html += htmlCheckBox;

          // закрываем селект
          html += '</div></div></div>';
          created = true;
        }

          /*Дублирует к каждой характеристики по одному пустом блоку*/
          htmlUserField += '<div class="userfd">' + html + '</div>';
    
      }

      htmlUserField = '';  
     
      allProperty.forEach(buildElements);
     
      container.html(htmlUserField);

    },
        
    /**
     * Разворачивает список доступных значений в таблице характеристик
     * button - объект клик по которому открывает список
      */
    showOptions: function(button) {
      if(button.data('visible')=='hide') {
        button.parents('td').find('.itemData').each(function(i,element) {
          if(i>3) {
            $(this).hide();
          }
        });
        button.text('Показать все');
        button.data('visible','show');
      }else{
        button.parents('td').find('.itemData').show();
        button.text('Свернуть список');
        button.data('visible','hide');
      }
     
    },
    
     // Устанавливает статус - видимый
     visibleProperty:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/visibleProperty",
        id: id,
        activity: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
     // Устанавливает статус - выводить в фильтрах
     filterVisibleProperty:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/filterVisibleProperty",
        id: id,
        filter: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },       
    
  }
})();

// инициализация модуля при подключении
userProperty.init();