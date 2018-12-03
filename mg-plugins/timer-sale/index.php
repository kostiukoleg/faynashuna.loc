<?php

/*
  Plugin Name: Таймер акций
  Description: Плагин таймер акций дабавляет характеристику к товарам, с датой окончания распродажи, формат ввода даты - дд.мм.гггг или дд.мм.гггг чч:мм:cc В карточке товара выводится таймер времени до окнчания выгодного предложения и процент скидки, если заполнено поле старая цена. Для вывода таймера необходимо добавить в файл view/product.php шорт-код: [timer-sale id = "&lt;?php echo $data['id']?&gt;"] , в файла view/catalog шорткод: [timer-sale id = "&lt;?php  echo $item ['id'] ?&gt;"]. Если время вышло, таймер не выводится.
  Author: Daria Churkina
  Version: 1.0.2
 */
new timerSale;

class timerSale {  
 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
    function __construct() {
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activateTimerSale')); //Инициализация  метода выполняющегося при активации  
    mgAddShortcode('timer-sale', array(__CLASS__, 'showTimerSale')); // Инициализация шорткода 

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$path = PLUGIN_DIR.self::$pluginName;
    if(!URL::isSection('mg-admin')) {
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/timer-sale.js"></script> ');     
      mgAddMeta('<link rel="stylesheet" type="text/css" href="'.SITE.'/'.self::$path.'/css/jquery.countdown.css">'); 
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/jquery.plugin.min.js"></script> '); 
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/jquery.countdown.min.js"></script>');
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/jquery.countdown-ru.js"></script>');
    } 
  }
  /**
   * Активирует плагин, добавляет характеистику к товарам - окончмние акции
   * @return type
   */
  public static function activateTimerSale() {
    $res = DB::query(
        "SELECT `id` FROM `".PREFIX."property`  WHERE name = 'Окончание акции (дд.мм.гггг)'"
    );
    if (!DB::numRows($res)) {
      DB::query(
        "INSERT INTO `".PREFIX."property` 
          (`id`, `name`, `type`, `default`, `data`, `all_category`, `activity`) 
          VALUES (NULL, 'Окончание акции (дд.мм.гггг)', 'string', '', '', '1', '0')"
      );
      
    $propId = DB::insertId();
    $category = DB::query(
        "SELECT `id` FROM `".PREFIX."category` "
    );
    
    while ($cat_id = DB::fetchArray($category)) { 
      DB::query("
            INSERT IGNORE INTO `".PREFIX."category_user_property`
            VALUES (".DB::quote($cat_id['id']).", ".DB::quote($propId).")");
    }
    $array = Array(
     'propertyId' => $propId,
    );
    MG::setOption(array('option' => 'timer-sale', 'value' => addslashes(serialize($array))));
  }
}

static function showTimerSale($args) {  
  if ($args['id']) {
    $option = MG::getSetting('timer-sale');
    $option = stripslashes($option);
    $options = unserialize($option);
    $percent = '';
    $time = '';
    $sql = "SELECT (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`,"
      . " p.old_price, prop.value FROM `".PREFIX."product` p "
      . "LEFT JOIN `".PREFIX."category` c ON c.id = p.cat_id "
      . "LEFT JOIN `".PREFIX."product_user_property` prop ON prop.`product_id`= p.id "
      . "WHERE p.id = ".DB::quote($args['id'])." AND prop.property_id = ".DB::quote($options['propertyId']);   
    $res = DB::query($sql);
    if ($product = DB::fetchArray($res) ) {
    $time = $product['value'];
    $timest = strtotime($time);     
    if ($timest >= time()) {
      $percent = 100 * ($product['old_price']-$product['price_course'])/$product['old_price'];
      if ($percent > 0) {
        $sale = '<div class="discount">Скидка на товар <span class="percent">'.round($percent).'%</span></div>';
      }
      $class = '';
      if(MG::get('controller')=="controllers_catalog") {
        $class = 'mg-timer-in-catalog';
       }
       $tableTime = '<table class="mg-timer"><tbody><tr><td class="icon"><img src="'.SITE.'/'.self::$path.'/image/timer-icon.png" alt=""></td>'
        . '<td><div class="mg-timer-sale" id="'.$args['id'].'"  data-time-finish="'.$time.'"> </div></td></tr></tbody></table>';
      $html .= '<div class="timer-sale-block '.$class.'">'.$sale.$tableTime.'</div>';
      
      return $html;
    }    
  }
  }
  return false;
  }
}