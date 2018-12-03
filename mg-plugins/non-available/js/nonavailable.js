$(document).ready(function () {
  nonAvailableModule.init();

});

var nonAvailableModule = (function () {
  return {
    init: function () {
      // появляется окно оформления заявки 
      if ($('.wrapper-modal-mg-non-available').length == 0) {
        var html = '\
              <span class="mg-non-available-overlay"></span>\
    <div class="wrapper-modal-mg-non-available">\
      <div class="header-modal-mg-non-available">\
        <h2 class="title-modal-mg-non-available">\
        </h2>\
          <span class="close-mg-booking-button"><a href="javascript:void(0);"></a></span>\
      </div>\
      <div class="content-modal-mg-non-available">\
        <div class="mg-product-info">\
          <div class="mg-product-img">\
			<img class="product-image" src="" >\
		  </div>\
      <div id="na-variant"> </div>\
        </div> \
        <div class="mg-booking-non-available">\
		<form action="' + mgBaseDir + '/" method="post">\
          <ul class="modal-mg-booking-list">\
		  <li class="link"><h3 data-link ></h3></li>\
            <li style="display:none" class="fio">\
                <span>Ваше имя:</span>\
                <input type="text" name="na-name" value ="">\
            </li>\
            <li style="display:none" class="phone">\
                <span>Телефон:<span class="red-star">*</span></span>\
                <input type="text" name="na-phone" value ="">\
            </li>\
            <li style="display:none" class="email">\
                <span>Ваш e-mail:<span class="red-star">*</span></span>\
                <input type="text" name="na-email" value ="">\
            </li>\
            <li style="display:none" class="address">\
                <span>Адрес:</span>\
                <textarea name="na-address"  value =""></textarea>\
            </li>\
            <li style="display:none" class="count">\
                <span>Количество:</span>\
                <input type="text" name="na-count"  value ="1">\
            </li>\
            <li style="display:none" class="comment">\
                <span>Комментарий:</span>\
                <textarea name="na-comment"  value =""></textarea>\
            </li>\
            <li class="mg-cap" style="display:none">\
                <div class="cap-left">\
                    <img style="margin-top: 5px; border: 1px solid gray;" src = "' + mgBaseDir + '/captcha.html" width="140" height="36">\
                    <span>Введите текст с картинки:<span class="red-star">*</span> </span>\
                    <input type="text" name="capcha" class="captcha">\
                </div>\
                <div style="clear:both;">\
                </div>\
            </li>\
          </ul>\
          </form>\
        </div>\
      </div>\
	  <div class="mg-price-non-available"></div>\
	<div class="mg-action-buttons">\
		<button class="close-mg-booking-button mg-plugin-btn">Отмена</button>\
	  	<button type = "submit" class="mg-send-booking-click-button mg-booking-btn" data-code="">Заказать</button>\
      </div>\
    </div>';
        $('body').append(html);
      }
        $('.mg-non-available-button').click(function () {
        var id = $(this).data('product_id');
        var code = null;
        
        if($('.buy-block').is('.product-code span')){
          code = $('.buy-block .product-code span').text();
        }   
        openOrderForm(id, code);        
        $('.wrapper-modal-mg-non-available .error').remove();       
        var price = $(".product-status-list li .price:first").text();
        $('.mg-price-non-available').html("Стоимость товара: <span>" + price + "</span>");
        var title_var = '';
        if ($('.block-variants input[type=radio]:checked').val()) {
          title_var = $('.block-variants input[type=radio]:checked').parents("tr").find('label').text();
          $('#na-variant').html("" + title_var + "");
        }
        var title = $('.product-details-block .product-title').text();
        $('.wrapper-modal-mg-non-available .modal-mg-booking-list .link h3').html(title+ ' ' + title_var);
        var src = $('.product-details-block img[data-product-id='+id+']').attr('src');
        $('.wrapper-modal-mg-non-available .mg-product-img img').attr('src', src);
        $('.wrapper-modal-mg-non-available').show();
        $('.mg-non-available-overlay').show();        
        $('.mg-booking-non-available input[name=na-phone]').mask(phoneMask);
        openModal($('.wrapper-modal-mg-non-available'));
      });

      // закрытие окна заявка
      $('.close-mg-booking-button').click(function () {
        $('.wrapper-modal-mg-non-available').hide();
        $('.mg-non-available-overlay').hide();
        closeModal($('.wrapper-modal-mg-non-available'));
      });

      // проверка введенных данных и отправка заявки
      $('.mg-send-booking-click-button').click(function () {
        var name = $(this).parents('.wrapper-modal-mg-non-available').find('input[name=na-name]');
        var phone = $(this).parents('.wrapper-modal-mg-non-available').find('input[name=na-phone]');
        var email = $(this).parents('.wrapper-modal-mg-non-available').find('input[name=na-email]');
        var address = $(this).parents('.wrapper-modal-mg-non-available').find('textarea[name=na-address]');
        var comment = $(this).parents('.wrapper-modal-mg-non-available').find('textarea[name=na-comment]');
        var count = $(this).parents('.wrapper-modal-mg-non-available').find('input[name=na-count]');
        var capcha = $(this).parents('.wrapper-modal-mg-non-available').find('input[name=capcha]');
        var code = $('.buy-block .code').text();
        var product_id = $(".mg-non-available-button").data('product_id');
        var title_var = '';
        if ($('.block-variants input[type=radio]:checked').val()) {
          title_var = $('.block-variants input[type=radio]:checked').parents("tr").find('label').text();
        }
        var title = $('.product-title').text();
        var price = $(".mg-price-non-available").text();

        if ($('.buy-block').find(".property-form").length) {
          var request = $('.buy-block').find(".property-form").formSerialize();
        } else {
          var inCartProd = 'inCartProductId=' + $('.mg-non-available-button').data('product_id');
          var request = inCartProd + '&amount_input=1';
        }

        var link = $('.modal-mg-booking-list h3').data('link');
        
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest?" + request,
          dataType: 'json',
          data: {
            mguniqueurl: "action/orderNonAvailable", // действия для выполнения на сервере
            pluginHandler: 'non-available',
            name: name.val(),
            phone: phone.val(),
            email: email.val(),
            address: address.val(),
            comment: comment.val(),
            count: count.val(),
            capcha: capcha.val(),
            invisible: 1,
            status_id: 1,
            code: code,
            product_id: product_id,
            title: title+' '+title_var,
            price: price,
            url: link
          },
          success: function (response) {
            if (response.status != 'error') {
              $('.wrapper-modal-mg-non-available .error').remove();
              $('.loading-send-booking').remove();
              $('.mg-action-buttons').before('<span class="loading-send-booking">' + response.data.msg + '</span>');
              $('.content-modal-mg-non-available').hide();
              $('.mg-action-buttons').hide();
              $('.mg-price-non-available').hide();

            } else {
              $('.wrapper-modal-mg-non-available .error').remove();
              $('.content-modal-mg-non-available').prepend(response.data.msg);
              $('.mg-action-buttons').show();
              $('.loading-send-booking').remove();
            }
          }
        });
      });
    
    $('.center').on('change', '.block-variants input[type=radio]', function(){
      if ($(this).data('count') != 0) {
         $('.wrapper-mg-non-available .mg-non-available-button').hide();
      }
      else {
         $('.wrapper-mg-non-available .mg-non-available-button').show();
      }
    });
      
    if ($('.block-variants input[type=radio]:checked').val()) { 
      if ($('.block-variants input[type=radio]:checked').data('count') != 0 ) {
        $('.wrapper-mg-non-available .mg-non-available-button').hide();
      }    
    };


      /**
       * Открывает модальное окно
       */
      function openModal(object) {
        overlay();
        object.fadeIn(300);
        object.css('z-index', 200);
        var offset = (window.pageYOffset );
        object.css('margin-top', offset);
      }

      /**
       * Закрывает модальное окно
       */
      function closeModal(object) {
        object.fadeOut(300);
        $("#overlay-non-av").remove();
      }

      /**
       * Фон для заднего плана при открытии всплывающего окна
       */
      function overlay() {
        var docHeight = $(document).height();
        $("body").append("<div id='overlay-non-av'></div>");
        $("#overlay-non-av").height(docHeight);
      }
      /**
       * функция загрузки формы для заказа
       * @param {type} e
       * @returns {undefined}
       */
      function openOrderForm(id, code) {
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest",
          dataType: 'json',
          data: {
            mguniqueurl: "action/buildOrderForm", // действия для выполнения на сервере
            pluginHandler: 'non-available',
            id: id
          },
          success: function (response) {            
            $('.wrapper-modal-mg-non-available .mg-send-booking-click-button').attr('data-code', code);
            $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.link h3').attr("data-link", response.data.link);
            if (response.data.options.header != '') {
              $('.wrapper-modal-mg-non-available .title-modal-mg-non-available').html(response.data.options.header);
            }       
            if (response.data.options.button != '') {
              $('.wrapper-modal-mg-non-available .mg-send-booking-click-button').text(response.data.options.button);
            } 
            if (response.data.options.name == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.fio').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list input[name=na-name]').val(response.data.user.name ? response.data.user.name  : '');
            }
            if (response.data.options.phone == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.phone').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list input[name=na-phone]').val(response.data.user.phone ? response.data.user.phone  : '');
            }
            if (response.data.options.email == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.email').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list input[name=na-email]').val(response.data.user.email ? response.data.user.email  : '');
            }
            if (response.data.options.address == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.address').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list textarea[name=na-address]').val(response.data.user.address ? response.data.user.address  : '');
            }
            if (response.data.options.comment == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.comment').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list textarea[name=na-comment]').val('');
            }
            if (response.data.options.count == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.count').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list textarea[name=na-count]').val(1);
            }
            if (response.data.options.capcha == 'true') {
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list li.mg-cap').css("display","block");
              $('.wrapper-modal-mg-non-available .modal-mg-booking-list input[name=capcha]').val('');
            };
          }
        });
        return true;
        
      }
    }}
})();
