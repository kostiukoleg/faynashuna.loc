/* 
 * Модуль  nonAvailableModule, подключается на странице настроек плагина.
 */

var nonAvailableModule = (function () {

  return {
    lang: [], // локаль плагина 
    init: function () {

      // установка локали плагина 
      admin.ajaxRequest({
        mguniqueurl: "action/seLocalesToPlug",
        pluginName: 'non-available'
      },
      function (response) {
        nonAvailableModule.lang = response.data;
      }
      );


      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-non-available .edit-row', function () {
        var id = $(this).data('id');
        nonAvailableModule.showModal(id);
      });

      // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-non-available .b-modal .save-button', function () {
        var id = $(this).data('id');
        nonAvailableModule.saveField(id);
      });

      // Сброс фильтров.
      $('.admin-center').on('click', '.section-non-available .refreshFilter', function () {
        admin.show('non-available', "plugin", "refreshFilter=1", nonAvailableModule.callbackNonAvailable);
        return false;
      });

      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-non-available .countPrintRowsEntity', function () {
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsEnity",
          pluginHandler: 'non-available',
          option: 'countPrintNonAvailable',
          count: count
        },
        function (response) {
          admin.refreshPanel();
        }
        );

      });


      // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-non-available .visible', function () {
        $(this).toggleClass('active');
        var id = $(this).data('id');
        if ($(this).hasClass('active')) {
          nonAvailableModule.visibleEntity(id, 1);
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          nonAvailableModule.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });

      // Удаляет запись
      $('.admin-center').on('click', '.section-non-available .delete-row', function () {
        var id = $(this).data('id');
        nonAvailableModule.deleteEntity(id);
      });

      // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-non-available .base-setting-save', function () {

        var obj = '{';
        $('.section-non-available .list-option input, .section-non-available .list-option textarea, .section-non-available .list-option select').each(function () {
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data = eval("(" + obj + ")");

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'non-available', // плагин для обработки запроса
          data: data // id записи
        },
        function (response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
        }

        );

      });

      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-non-available .filter-now', function () {
        nonAvailableModule.getProductByFilter();
        return false;
      });


      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-non-available .show-property-order', function () {
        $('.property-order-container').slideToggle(function () {
          $('.filter-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });

      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-non-available  .show-filters', function () {
        $('.filter-container').slideToggle(function () {
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
    showModal: function (id) {
      nonAvailableModule.clearField();
      nonAvailableModule.fillField(id);

      admin.openModal($('.b-modal'));

    },
    /**
     * Очистка модального окна
     */
    clearField: function () {
      $('.section-non-available .b-modal .fields-order .name').text('');
      $('.section-non-available .b-modal .fields-order .phone').text('');
      $('.section-non-available .b-modal .fields-order .email').text('');
      $('.section-non-available .b-modal .fields-order .address').text('');
      $('.section-non-available .b-modal .fields-order .add_datetime').text('');
      $('.section-non-available .b-modal .fields-order .title').text('');
      $('.section-non-available .b-modal .fields-order .code').text('');
      $('.section-non-available .b-modal .fields-order .product_id').text('');
      $('.section-non-available .b-modal .fields-order .count').text('');
      $('.section-non-available .b-modal .fields-order .description').text('');
      $('.section-non-available .b-modal .fields-order textarea[name="comment_admin"]').text('');
      $('.section-non-available .b-modal .fields-order .comment').text('');
      $('.section-non-available .b-modal .save-button').data('id', '');
    },
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
     */
    fillField: function (id) {

      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'non-available', // плагин для обработки запроса
        id: id // id записи
      },
      function (response) {
        var title = '<a href ='+ mgBaseDir +'/'+response.data.url+' title ="'+response.data.title+'" target="_blank" >'+response.data.title+'</a>';
        $('.section-non-available .b-modal .fields-order .name').text(response.data.name);
        $('.section-non-available .b-modal .fields-order .phone').text(response.data.phone);
        $('.section-non-available .b-modal .fields-order .email').text(response.data.email);
        $('.section-non-available .b-modal .fields-order .address').text(response.data.address);
        $('.section-non-available .b-modal .fields-order .add_datetime').text(response.data.add_datetime);
        $('.section-non-available .b-modal .fields-order .title').html(title);
        $('.section-non-available .b-modal .fields-order .code').text(response.data.code);
        $('.section-non-available .b-modal .fields-order .product_id').text(response.data.product_id);
        $('.section-non-available .b-modal .fields-order .count').text(response.data.count);
        $('.section-non-available .b-modal .fields-order .comment').text(response.data.comment);
        $('.section-non-available .b-modal .fields-order textarea[name="comment_admin"]').val(response.data.comment_admin);
        $('.section-non-available .b-modal .fields-order .description').html(response.data.description);
        $('.section-non-available .b-modal .fields-order select[name="status_id"] ').val(response.data.status_id);
        $('.section-non-available .b-modal .save-button').data('id', response.data.id);
      },
        $('.b-modal .widget-table-body') // вывод лоадера в контейнер окна, пока идет загрузка данных

        );

    },
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
     */
    saveField: function (id) {

      var invisible = '0';
      if ($('.entity-table-tbody tr[data-id=' + id + '] .visible').hasClass('active')) {
        invisible = '1';
      }

      admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'non-available', // плагин для обработки запроса
        id: id,
        status_id: $('.section-non-available .b-modal .fields-order select[name=status_id]').val(),
        comment_admin: $('.section-non-available .b-modal .fields-order textarea[name="comment_admin"').val(),
        invisible: invisible,
      },
        function (response) {
          admin.indication(response.status, response.msg);
          var replaceTr = $('.entity-table-tbody tr[data-id=' + id + ']');
          nonAvailableModule.drawRow(response.data.row, replaceTr); // перерисовка строки новыми данными
          admin.closeModal($('.b-modal'));
          nonAvailableModule.clearField();
        },
        $('.b-modal .widget-table-body') // на месте кнопки
          
        );

    },
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */
    drawRow: function (data, replaceTr) {

      var invisible = data.invisible === '1' ? 'active' : '';
      var titleInvisible = data.invisible ? lang.ACT_V_ENTITY : lang.ACT_UNV_ENTITY;
      var status = ['Ожидает', 'Отменен', 'Завершен'];

      var name = $(replaceTr).find('.name').text();
      var phone = $(replaceTr).find('.phone').text();
      var add_date = $(replaceTr).find('.add_datetime').text();
      var title = $(replaceTr).find('.title').text();
      var code = $(replaceTr).find('.code').text();

      var statusId = data.status_id;
      var $class = 'get-paid';

      if (statusId == 1) {
        $class = 'get-paid';
      }
      if (statusId == 2) {
        $class = 'dont-paid';
      }
      if (statusId == 3) {
        $class = 'activity-product-true';
      }

      if (!status[data.status_id - 1]) {
        status[data.status_id - 1] = "Без статуса";
      }

      var status_id = " <span class='" + $class + "'> " + status[data.status_id - 1] + "</span>";
      var tr = '\
       <tr data-id="' + data.id + '">\
        <td>' + data.id + '</td>\
        <td class="add_datetime">' + add_date + '</td>\
        <td class="name">' + name + '</td>\
        <td class="phone">' + phone + '</td>\
        <td class="code">' + code + '</td>\
        <td class="title">' + title + '</td>\
        <td class="status_id">' + status_id + '</td>\
         <td class="actions">\
           <ul class="action-list">\
             <li class="edit-row" data-id="' + data.id + '" ><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.EDIT + '"></a></li>\
             <li class="visible tool-tip-bottom ' + invisible + '" data-id="' + data.id + '" title="' + titleInvisible + '"><a href="javascript:void(0);"></a></li>\
             <li class="delete-row" data-id="' + data.id + '"><a class="tool-tip-bottom" href="javascript:void(0);"  title="' + lang.DELETE + '"></a></li>\
           </ul>\
         </td>\
      </tr>';

      if (!replaceTr) {

        if ($('.entity-table-tbody tr').length > 0) {
          $('.entity-table-tbody tr:first').before(tr);
        } else {
          $('.entity-table-tbody').append(tr);
        }
        $('.entity-table-tbody .no-results').remove();

      } else {
        replaceTr.replaceWith(tr);}
    },
    /**    
     * Удаляет  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */
    deleteEntity: function (id) {
      if (!confirm(lang.DELETE + '?')) {
        return false;
      }

      admin.ajaxRequest({
        mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
        pluginHandler: 'non-available', // плагин для обработки запроса
        id: id
      },
      function (response) {
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id=' + id + ']').remove();
        if ($(".entity-table-tbody tr").length == 0) {
          var html = '<tr class="no-results">\
            <td colspan="8" align="center">' + nonAvailableModule.lang['ENTITY_NONE'] + '</td>\
          </tr>';
          $(".entity-table-tbody").append(html);
        }
        ;
      }

      );
},
    /*
     * Переключатель активности
     */
    visibleEntity: function (id, val) {
      admin.ajaxRequest({
        mguniqueurl: "action/visibleEntity",
        pluginHandler: 'non-available', // плагин для обработки запроса
        id: id,
        invisible: val,
      },
        function (response) {
          admin.indication(response.status, response.msg);
        }
      );
    },
    getProductByFilter: function () {
      var request = $("form[name=filter]").formSerialize();
      admin.show("non-available", "plugin", request + '&applyFilter=1', nonAvailableModule.callbackNonAvailable);
      return false;
    },
    /**
     *Пакет выполняемых действий после загрузки раздела товаров
     */
    callbackNonAvailable: function () {
      admin.AJAXCALLBACK = [
        {callback: 'admin.sortable', param: ['.entity-table-tbody', 'non-available']},
      ];
    },
  }
})();

nonAvailableModule.init();
