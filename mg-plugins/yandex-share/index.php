<?php
/*
  Plugin Name: Яндекс поделиться
  Description: Выводит блок кнопок социальных сетей, для размещения ссылок на сайт. В разметкe страницы товара необходимо вставить шорт код: [yandex-share]
  Author: Avdeev Mark
  Version: 1.0
 */
 
/* 
  Пример использования.
  В разметке страница товара необходимо вставить шорт код:
  [yandex-share]
*/
function yandexShare(){   
  return '
  <script type="text/javascript" src="//yandex.st/share/share.js" charset="utf-8"></script>
  <div class="yashare-auto-init" data-yashareL10n="ru" data-yashareType="none" data-yashareQuickServices="yaru,vkontakte,facebook,twitter,odnoklassniki,moimir"></div> 
  ';
}

mgAddShortcode('yandex-share', 'yandexShare');