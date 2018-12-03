$(document).ready(function () {
  mgQuickViewModule.init();
});

var mgQuickViewModule = (function () {
  return {
    init: function () {
      // при нажатии на кнопку купить открывается модальное окно
      $('body').on('click', '.mg-quick-view-button', function () {
        var id = $(this).data('product-id');
        openOrderForm(id);
      });
      // закрытие модального окна
      $('body').on('click', '.close-mg-quickview-button', function () {
        closeModal($('.wrapper-modal-mg-quick-view'));
      });
      // закрытие модального окна при клике вне окна
      $('body').on('click', '#overlay-quick-view', function (e) {
        var object = $('.wrapper-modal-mg-quick-view');
        // если клик был не по нашему блоку и не по его дочерним элементам
        if (!object.is(e.target) && object.has(e.target).length === 0) {
          closeModal(object);
        }
      });
      $("body").on("click", '.wrapper-modal-mg-quick-view .zoom', function () {
        $(this).prev().trigger("click");
      });
      $("body").on('click', '.wrapper-modal-mg-quick-view .block-variants label', function(){
        var id = $(this).attr('for');
        $('.wrapper-modal-mg-quick-view .block-variants td input#'+id).trigger("click");
      })
      $(".product-wrapper").hover(
            function(){
        if ($(this).find('.mg-quick-view-button').parent().hasClass('showbyhover')) {
          $(this).addClass('quick-view-background');       
          $(this).find('.mg-quick-view-button').parent().show();
        }
     
      }, function(){
        if ($(this).find('.mg-quick-view-button').parent().hasClass('showbyhover')) {
          $(this).removeClass('quick-view-background');           
          $(this).find('.mg-quick-view-button').parent().hide();
        }
        })
      /**
       * функция загрузки формы для заказа
       * @param {type} e
       * @returns {undefined}
       */
      function openOrderForm(id) {
        overlay();
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest",
          dataType: "json",
          data: {
            mguniqueurl: "action/buildProductCard", // действия для выполнения на сервере
            pluginHandler: 'quick-view',
            id: id
          },
          success: function (response) {
            $('body').append(response.data);
            var object = $('.wrapper-modal-mg-quick-view');
            $("#page-preloader").hide();
            object.fadeIn(300);
            object.css('z-index', 111);
            var offset = (window.pageYOffset);
            object.css('margin-top', offset);
            //Слайдер картинок в карточке товаров
            $('.main-product-slide').bxSlider({
              pagerCustom: '.slides-inner',
              controls: false,
              mode: 'fade',
              useCSS: false
            });

            //Слайдер тумбнейлов
            $('.slides-inner').bxSlider({
              minSlides: 3,
              maxSlides: 3,
              slideWidth: 75,
              pager: false,
              slideMargin: 10,
              useCSS: false
            });

            try {
              $('.main-product-slide .mg-product-image').each(function () {
                $(this).magnify({
                  lensLeft: 310,
                  lensTop: -5,
                });
              });
            }
            catch (err) {
            }
            object.find('.buy-container').addClass('product');
          }
        });
        return true;

      }

      /**
       * Открывает модальное окно
       */
      function openModal(object) {
        overlay();
      }
      /**
       * Закрывает модальное окно
       */
      function closeModal(object) {
        object.fadeOut(300);
        object.remove();
        $("#overlay-quick-view").remove();
      }
      /**
       * Фон для заднего плана при открытии всплывающего окна
       */
      function overlay() {
        var docHeight = $(document).height();
        $("body").append("<div id='overlay-quick-view'><div id='page-preloader'></div></div>");
        $("#overlay-quick-view").height(docHeight);
      }
    }}
})();
