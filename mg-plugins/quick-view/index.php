<?php

/*
  Plugin Name: Быстрый просмотр товара
  Description: Плагин предосталяет пользователю возможность быстрого просмотра товара, не перезагружая страницу. После подключения плагина необходимо в catalog.php добавить [quick-view  id="&lt;?php echo $item['id']?&gt;"] $item['id'] - название массива с информацией о товаре (может быть $data['id']). Плагин имеет страницу настроек для выбора необходимой информации, отображаемой в карточке.
  Author: Чуркина Дарья, churkina.daria@gmail.com
  Version: 1.0.1
 */

new quickView;

class quickView {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('quick-view', array(__CLASS__, 'quickViewActivate')); /* Инициализация шорткода [quick-view  id="<?php echo $data['id']?>"] - доступен в любом HTML коде движка.   */
   
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    
    if (!URL::isSection('mg-admin')) {
      $option = MG::getSetting('quickViewOption');
      $option = stripslashes($option);
      $options = unserialize($option);
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/quickview.js"></script>'); 
      mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/jquery.fancybox.css" rel="stylesheet"/>'); 
      mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/layout.images.css" rel="stylesheet"/>'); 
      mgAddMeta('<script src="'.SCRIPT.'jquery.fancybox.pack.js"></script>'); 
      mgAddMeta('<script src="'.SCRIPT.'jquery.bxslider.min.js"></script>'); 
      mgAddMeta('<script src="'.SCRIPT.'standard/js/layout.images.js"></script>'); 
    }
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    $option = MG::getSetting('quickViewOption');
    if (empty($option)) {
      $array = Array(
        'button' => 'Быстрый просмотр',
        'showbyhover' => 'true'
      );
      MG::setOption(array('option' => 'quickViewOption', 'value' => addslashes(serialize($array))));
    }
  }

  /**
   * Метод выполняющийся перед генерацией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '     
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;

    //получаем опцию quickViewOption в переменную option
    $option = MG::getSetting('quickViewOption');
    $option = stripslashes($option);
    $options = unserialize($option); 
    self::preparePageSettings();
    include('pageplugin.php');
  }

  /**
   * Обработчик шотркода вида [quick-view  id="<?php echo $data['id']?>"]
   * выполняется когда при генерации страницы встречается  
   */
  static function quickViewActivate($product) {
    if (empty($product['id'])) {
      return false;
    }
    $option = MG::getSetting('quickViewOption');
    $option = stripslashes($option);
    $options = unserialize($option);    
    $result = '<div style="display:'.($options['showbyhover'] == 'true' ? 'none' : 'block').'"'
            . 'class='.($options['showbyhover'] == 'true' ? 'showbyhover' : '').'>'
         .'<a class="mg-quick-view-button mg-plugin-btn"  data-product-id = '.$product['id'].'>'
      .(($options['button'] != '') ? $options['button'] : 'Быстрый просмотр').'
                </a></div>';
    return $result;
  }

}
