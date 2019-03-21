var availableTags = null;
$(document).ready(function() {
  $(".content-modal-back-ring input[name=city_id]").autocomplete({
    source: availableTags
  });

  $(".ui-autocomplete").css('z-index', '1000');
  $.datepicker.regional['ru'] = {
    closeText: 'Закрыть',
    prevText: '&#x3c;Пред',
    nextText: 'След&#x3e;',
    currentText: 'Сегодня',
    monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
      'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
    monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
      'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
    dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
    dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
    dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
    dateFormat: 'dd.mm.yy',
    firstDay: 1,
    isRTL: false
  };
  $.datepicker.setDefaults($.datepicker.regional['ru']);
  $('.content-modal-back-ring input[name=date_callback]').datepicker({dateFormat: "yy-mm-dd"});
  $(".content-modal-back-ring input[name=phone]").mask("+38 (999) 999-99-99");

  $('.back-ring-button').click(function() {
    if ($('.wrapper-modal-back-ring').is(':visible')) {
      return false;
    }
    $('.wrapper-modal-back-ring').show();
    $("html, body").animate({ scrollTop: 0 }, "fast");

    openModal($('.wrapper-modal-back-ring'));
    $('.content-modal-back-ring input[name=name]').val('');
    $('.content-modal-back-ring textarea[name=comment]').val('');
    $('.content-modal-back-ring input[name=phone]').val('');
    $('.content-modal-back-ring input[name=city_id]').val('');
    $('.content-modal-back-ring select[name=mission]').val('');
    $('.content-modal-back-ring input[name=date_callback]').val('');

    $('.wrapper-modal-back-ring .error').remove();
  });

  $('.close-ring-button').click(function() {
    $('.wrapper-modal-back-ring').hide();
    closeModal($('.wrapper-modal-back-ring'));
  });

  $('.send-ring-button').click(function() {

    var name = $(this).parents('.content-modal-back-ring').find('input[name=name]');
    var comment = $(this).parents('.content-modal-back-ring').find('textarea[name=comment]');
    var phone = $(this).parents('.content-modal-back-ring').find('input[name=phone]');
    var city_id = $(this).parents('.content-modal-back-ring').find('input[name=city_id]');
    var mission = $(this).parents('.content-modal-back-ring').find('select[name=mission]');
    var from = $(this).parents('.content-modal-back-ring').find('select[name=from]');
    var to = $(this).parents('.content-modal-back-ring').find('select[name=to]');
    var date_callback = $(this).parents('.content-modal-back-ring').find('input[name=date_callback]');
    var captcha = $(this).parents('.content-modal-back-ring').find('input[name=capcha]');
    var time_callback = 'с ' + from.val() + ' до ' + to.val();
    if (from.parents('li').css('display') == 'none') {
      time_callback = '';
    }

    if (phone.val() == "") {
      $('.wrapper-modal-back-ring .error').remove();
      $('.title-modal-back-ring').after('<div class="error">Необходимо заполнить поля формы</div>')
      return false;
    }

    $('.send-ring-button').hide();
    $('.send-ring-button').before("<span class='loading-send-ring'>Подождите, идет отправка заявки...</span>");

    $.ajax({
      type: "POST",
      url: mgBaseDir + "/ajaxrequest",
      dataType: 'json',
      data: {
        mguniqueurl: "action/sendOrderRing", // действия для выполнения на сервере
        pluginHandler: 'back-ring',
        name: name.val(),
        comment: comment.val(),
        phone: phone.val(),
        city_id: city_id.val(),
        mission: mission.val(),
        date_callback: date_callback.val(),
        time_callback: time_callback,
        invisible: 1,
        status_id: 1,
        pub: 1,
        capcha: captcha.val(),        
      },
      success: function(response) {
        if (response.status != 'error') {
          $('.content-modal-back-ring').text('Ваша заявка №' + response.data.row.id + ' принята. Наши менеджеры свяжутся с вами!');        
          $('.send-ring-button').show();
          $('.loading-send-ring').remove();
          closeModal($('.wrapper-modal-back-ring'));
        } else {
          $('.wrapper-modal-back-ring .error').remove();
          $('.title-modal-back-ring').after(response.data.msg);
          $('.send-ring-button').show();
          $('.loading-send-ring').remove();
        }
      }
    });
  });

  /**
   * Открывает модальное окно
   */
  function openModal(object) {
    overlay();
    object.fadeIn(300);
    object.css('z-index', 1000);
  }

  /**
   * Закрывает модальное окно
   */
  function closeModal(object) {
    object.fadeOut(300);
    $("#overlay").remove();
  }

  /**
   * Фон для заднего плана при открытии всплывающего окна
   */
  function overlay() {
    var docHeight = $(document).height();
    $("body").append("<div id='overlay'></div>");
    $("#overlay").height(docHeight);
  }

});