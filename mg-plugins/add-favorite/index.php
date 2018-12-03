<?php

/*
  Plugin Name: Добавить в закладки
  Description: Выводит кнопоку "Добавить в закладки". В разметкe страницы необходимо вставить шорт код: [add-favorite].
  Author: <img src="http://mogutashop.ru/favicon.ico" style="position: relative;top: 3px;" /><a style="text-decoration: none; color:black" href="http://mogutashop.ru/" target="_blank">MogutaSHOP Developers</a>
  Version: 1.0
 */

new AddFavorite;

class AddFavorite {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации    
    mgAddShortcode('add-favorite', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [add-favorite] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
	  mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/add-favorite.js"></script>');
    }
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    self::createDateBase();
  }

 
  /**
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
      `type` varchar(255) NOT NULL COMMENT 'Тип записи',
	    `nameEntity` text NOT NULL COMMENT 'Название',
      `value` text NOT NULL COMMENT 'Значение',      
      `sort` int(11) NOT NULL COMMENT 'Порядок',
      `invisible` int(1) NOT NULL COMMENT 'Видимость',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

    // Запрос для проверки, был ли плагин установлен ранее.
    $res = DB::query("
      SELECT id
      FROM `".PREFIX.self::$pluginName."`
      WHERE id in (1,2,3) 
    ");


  }

  
  /**
   * Обработчик шотркода вида [add-favorite] 
   * выполняется когда при генерации страницы встречается [add-favorite] 
   */
  static function handleShortCode() {
 

    $html = '<a class="favorite" onclick="return add_favorite(this);">Добавить в закладки</a> ';   
 
    return $html;
  }

}