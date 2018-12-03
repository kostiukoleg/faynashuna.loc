<?php

/*
  Plugin Name: Share кнопки pluso
  Description: Плагин-обертка для виджета социальных кнопок <a href="http://pluso.ru">pluso</a>. <br />Шорткод для вставки плагина: [pluso].
  Author: Osipov Ivan
  Version: 1.0.2
 */

new Pluso;

class Pluso{

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $arOptions = array();

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  
    mgAddShortcode('pluso', array(__CLASS__, 'handlePsulo')); // Инициализация шорткода [pluso] - доступен в любом HTML коде движка. 

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate(){
    USER::AccessOnly('1,4','exit()');
    self::setDefaultOptions();
  }
  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings(){
    USER::AccessOnly('1,4','exit()');
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" /> 
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/pluso.css" type="text/css" />
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function setDefaultOptions(){
    USER::AccessOnly('1,4','exit()');
    
    if(MG::getSetting(self::$pluginName.'-option') == null){
      $arPluginParams = array(
        'theme' => '04',
        'services' => 'vkontakte, odnoklassniki, facebook, twitter, google, moimir',
        'size' => 'big',
        'shape' => 'square',
        'multiline' => false,
        'orientation' => 'horizontal',
        'counter' => true,
        'use-background' => true,
        'background' => '#ebebeb',
      );
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($arPluginParams))));
    }  
    
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin(){
    USER::AccessOnly('1,4','exit()');
    
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $option = MG::getSetting($pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    self::$arOptions = $options;

    self::preparePageSettings(); 
    include('pageplugin.php');
  }
  
  /**
   * Обработчик шотркода вида [pluso] 
   * выполняется когда при генерации страницы встречается [pluso] 
   */
  static function handlePsulo(){
    $option = MG::getSetting(self::$pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    
    $plusoOptions = $options['size'].",".$options['shape'].",".$options['orientation'].",";
    
    if($options['multiline'] && $options['multiline']!='false'){
      $plusoOptions .= 'multiline,';
    }else{
      $plusoOptions .= 'line,';
    }
    
    if($options['counter'] && $options['counter']!='false'){
      $plusoOptions .= 'counter,';
    }else{
      $plusoOptions .= 'nocounter,';
    }
    
    $plusoOptions .= 'theme='.$options['theme'];
    
    if($options['use-background'] && $options['use-background']!='false'){
      $plusoBg = $options['background'];
    }else{
      $plusoBg = 'transparent';
    }
    
    $result = '
      <script type="text/javascript">(function() {
      if (window.pluso)if (typeof window.pluso.start == "function") return;
      if (window.ifpluso==undefined) { window.ifpluso = 1;
        var d = document, s = d.createElement(\'script\'), g = \'getElementsByTagName\';
        s.type = \'text/javascript\'; s.charset=\'UTF-8\'; s.async = true;
        s.src = (\'https:\' == window.location.protocol ? \'https\' : \'http\')  + \'://share.pluso.ru/pluso-like.js\';
        var h=d[g](\'body\')[0];
        h.appendChild(s);
      }})();
      </script>
      <div class="pluso" 
        data-background="'.$plusoBg.'" 
        data-options="'.$plusoOptions.'" 
        data-services="'.$options['services'].'">
      </div>';
    
    return $result;
  }

}