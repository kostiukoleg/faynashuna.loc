//Инициализация fancybox
  $("a.pic").fancybox({
    'overlayShow': false,
    tpl: {
      next: '<a title="Вперед" class="fancybox-nav fancybox-next" href="javascript:;"><span></span></a>',
      prev: '<a title="Назад" class="fancybox-nav fancybox-prev" href="javascript:;"><span></span></a>'
    }
  });