/**
 * Подключается в карточке товара
 */
$(document).ready(function() {
  //Выбирает текущий тумбнейл
  $('.slides-inner a').click(function() {
    $(this).each(function() {
      $('.slides-inner a').removeClass('active-item');
      $(this).addClass('active-item');
    });
  });

  //Инициализация fancybox
  $(".close-order, a.fancy-modal").fancybox({
    'overlayShow': false,
    tpl: {
      next: '<a title="Вперед" class="fancybox-nav fancybox-next" href="javascript:;"><span></span></a>',
      prev: '<a title="Назад" class="fancybox-nav fancybox-prev" href="javascript:;"><span></span></a>'
    }
  });

  //Слайдер картинок в карточке товаров
  $('.main-product-slide').bxSlider({
    pagerCustom: '.slides-inner',
    controls: false,
    mode: 'fade',
    useCSS: false
  });

  //Слайдер тумбнейлов
  if($('.slides-inner').length){

    $('#slide-counter').prepend('<strong class="current-index"></strong>/');

    var slider = $('.slides-inner').bxSlider({
      minSlides: 3,
      maxSlides: 3,
      slideWidth: 170,
      pager: false,
      slideMargin: 20,
      useCSS: false,
      mode: "vertical",
      moveSlides: 1,
      infiniteLoop: false,
      nextSelector: '#thumb-next',
      prevSelector: '#thumb-prev',
      onSliderLoad: function (currentIndex){
        $('#slide-counter .current-index').text(currentIndex + 1);
      },
      onSlideBefore: function ($slideElement, oldIndex, newIndex){
        $('#slide-counter .current-index').text(newIndex + 1);
      }
    });

    $('#slide-counter').append(slider.getSlideCount());
  }

	try {
    $('.main-product-slide .mg-product-image').each(function(){
      $(this).magnify({
        lensLeft: 310,
        lensTop: -5
    });});
	}
	catch(err) { }
	
  
  //клик по превью-картинке
  var $that = '';
  $(".mg-peview-foto").click(function() {
    var that = this;
    //копируем атрибуты из превью-картинки в контейнер-картинку
    $(".main-product-slide").fadeOut(600, function() {
      $(this).attr("src", $(that).attr("src")).attr("data-large", $(that).attr("data-large")).fadeIn(1000);
    });
  });
  // открытие фенсимодал
  $('body').on('click', '.tracker', function() {
    $('.product-details-image').each(function() {
      if ($(this).css('display') == 'block' || $(this).css('display') == 'list-item') {
        $(this).find('.fancy-modal').click();
      }
    });
  });

});  