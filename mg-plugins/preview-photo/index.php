<?php

/*
  Plugin Name: Генератор миниатюр
  Description: Плагин создает миниатюры для загруженых картинок товаров, необходимых для корректного отображения фотографий продукции в магазине. Для корректной работы плагина создайте в uploads папку tempimage.
  Author: Avdeev Mark
  Version: 1.1.5
 */

new PhotoPreview;

class PhotoPreview {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {


    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);    
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;
    self::$path = PLUGIN_DIR.self::$pluginName;     
    
  }
  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />  
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");         
      </script> 
    ';
         
     $option = MG::getSetting('preview-photo-option');
     $option = stripslashes($option);
     $options = unserialize($option); 
   
  }
  


  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    
    //получаем опцию preview-photoOption в переменную option
    $option = MG::getSetting('preview-photo-option');
    $option = stripslashes($option);
    $options = unserialize($option);     
    
    self::preparePageSettings();  
    include('pageplugin.php');
  }

}