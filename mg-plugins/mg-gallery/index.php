<?php

/*
  Plugin Name: Галерея
  Description: Плагин выводит по шорткоду [gallery] галерею изображений, с возможностью увеличения по клику. При подключении шорткода можно передавать несколько параметров:<br />album - папка внутри "/uploads/mg-gallery/", из которй брать изображения(по умолчанию берутся из корня галереи)<br />line_count - количество изображений в строке(по умолчанию: 4)<br />height - высота изображения, в px(по умолчанию: 200)<br />use_fansy - подключать ли дополнительно библиотеку fancybox, значения: y(да)/n(нет)(по умолчанию: y)<br />Пример подключения с параметрами: [gallery line_count=3 height=150]
  Author: Osipov Ivan, Gaydis Mikhail
  Version: 1.0.4
 */

new mgGallery;

class mgGallery{

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $arOptions = array();

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddShortcode('gallery', array(__CLASS__, 'handleGallery')); // Инициализация шорткода [gallery] - доступен в любом HTML коде движка. 
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    
  }

  static function pageSettingsPlugin(){
    self::preparePageSettings();
    include('pageplugin.php');
  }

  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/mg-admin/design/css/jquery-ui.css">
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/admin.js"></script> 
    ';
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate(){
    USER::AccessOnly('1,4','exit()');
    self::prepareFiles();
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function prepareFiles(){
    USER::AccessOnly('1,4','exit()');

    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX."all_galleries` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `gal_name` varchar(255) NOT NULL,
      `height` text NOT NULL,
      `in_line` int(5) NOT NULL,      
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX."galleries_img` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `id_gal` int(11) NOT NULL,
      `image_url` text NOT NULL,
      `alt` text NOT NULL,      
      `title` text NOT NULL,      
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
    
  }
  
  /**
   * Обработчик шотркода вида [gallery] 
   * выполняется когда при генерации страницы встречается [gallery] 
   */
  static function handleGallery($args){
    foreach($args as $name=>$value) {
      $options[$name] = $value;
    }

    $res = DB::query('SELECT gal_name, height, in_line FROM `'.PREFIX.'all_galleries` WHERE id = '.DB::quote($options['id']));

    while ($row = DB::fetchAssoc($res)) {
      $options = array(
        'in_line' => $row['in_line'], 
        'height' => $row['height'],
        'id' => $options['id']
      );
    }

    $res = DB::query('SELECT id, id_gal, image_url, alt, title 
      FROM `'.PREFIX.'galleries_img` WHERE id_gal = '.DB::quote($options['id']));

    while ($row = DB::fetchAssoc($res)) {
      $imgList[] = $row;
    }

    $result = '';
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__));

      ob_start();
      require_once $realDocumentRoot.'/mg-plugins/'.self::$pluginName.'/include.php';

      include $realDocumentRoot.'/mg-plugins/'.self::$pluginName.'/views/imageslist.php';

      $result .= ob_get_contents();
      ob_end_clean();
    
    return $result;
  }

}