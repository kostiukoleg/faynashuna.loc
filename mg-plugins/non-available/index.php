<?php

/*
  Plugin Name: Заказать, если нет в наличии
  Description: Плагин предосталяет пользователю возможность оставить заявку на товар, если его нет в наличии. После подключения плагина необходимо вставить в Ваш шаблон, где формируется карточка товара, шорт-код [non-available id="&lt;?php echo $data['id']?&gt;"] в файле view/product.php и отметить в опциях выводить товар, если его нет на складе. Плагин имеет страницу настроек для выбора необходимых данных от покупателя при заказе.
  Author: Чуркина Дарья
  Version: 1.0.8
 */

new NonAvailable;

class NonAvailable {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
 
  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('non-available', array(__CLASS__, 'orderNonAvailable')); /* Инициализация шорткода [non-available id="<?php echo $data['id']?>"] - доступен в любом HTML коде движка.   */

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
      mgAddMeta('<script src="'.SITE.'/mg-core/script/jquery.maskedinput.min.js"></script>');
    } else {
      MG::addInformer(array('count'=>self::getEntityActive(),'class'=>'count-wrap','classIcon'=>'order-product-icon', 'isPlugin'=>true, 'section'=>'non-available', 'priority'=>80));
    }
      mgAddMeta('<script src="'.SITE.'/'.self::$path.'/js/nonavailable.js"></script>');
        
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
    // Запрос для проверки, был ли плагин установлен ранее.
    $exist=false;
    $result = DB::query('SHOW TABLES');
    while($row = DB::fetchArray($result)){     
      if( PREFIX.self::$pluginName==$row[0]){
        $exist=true;
      };
    }   
    
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
      `code` varchar(255) NOT NULL COMMENT 'Артикул товара',
      `product_id` int(11) NOT NULL COMMENT 'id товара',
      `title` varchar(255) NOT NULL COMMENT 'Наименование',
      `count` int(11) NOT NULL COMMENT 'Количество',
      `price` varchar(32) NOT NULL COMMENT 'Стоимость',
      `description` text NOT NULL COMMENT 'Описание',
      `add_datetime` DATETIME NOT NULL COMMENT 'Дата добавления',
	    `name` text NOT NULL COMMENT 'Имя',
      `phone` text NOT NULL COMMENT 'Телефон', 
      `email` text NOT NULL COMMENT 'Почта',
      `address` text NOT NULL COMMENT 'Адрес',  
      `comment` text NOT NULL COMMENT 'Комментарий пользователя',  
      `status_id` int(11) NOT NULL COMMENT 'Статус',
      `sort` int(11) NOT NULL COMMENT 'Порядок',
      `invisible` int(1) NOT NULL COMMENT 'Видимость',
      `comment_admin` text NOT NULL COMMENT 'комментарий менеджера',
      `url` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
        
    // Если плагин впервые активирован, то задаются настройки по умолчанию 
    if (!$exist) {    
      DB::query("        
       INSERT INTO `".PREFIX.self::$pluginName."` (`id`, `code`, `product_id`, `title`,`count`,`add_datetime`, `name`, `phone`, `price`,`description`, `email`, `address`, `comment` ,`status_id`, `sort`,`invisible`) VALUES
       (1, 'A111', 5, 'Ноутбук Dell', '1', '2013-12-11 00:00:00', 'Авдеев Марк', '8-555-55-43-21', '70 руб', '' ,'avdeev-mark@moguta.ru', 'Москва', 'Нужен очень срочно!','1', '0', 0 );
      ");
    
    $option = MG::getSetting('nonAvailableOption');
    if (empty($option)) {
      $array = Array(
        'name' => 'true',
        'phone' => 'true',
        'email' => 'true',
        'address' => 'true',
        'comment' => 'true',
        'count' => 'true',
        'capcha' => 'true',
        'header' => 'Заявка на товар',
        'button' => 'Заказать',
        'comment' => 'true',
      );

      MG::setOption(array('option' => 'nonAvailableOption', 'value' => addslashes(serialize($array))));
      MG::setOption(array('option' => 'countPrintNonAvailable', 'value'=>20));
    }
    }
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

    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);
    }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    //получаем опцию nonAvailableOption в переменную option
    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);   
  
    
    self::preparePageSettings();       
    $status = array('null'=>'Не выбрано', 1=>'Ожидает', 2=>'Отменен', 3=>'Завершен');
    
    //фильтры 
     $property = array(
      'id' => array(
        'type' => 'text',
        'label' => 'Номер заявки',
        'value' => !empty($_POST['id']) ? $_POST['id'] : null,
      ),

      'status_id' => array(
        'type' => 'select',
        'option' =>$status,
        'selected' => (!empty($_POST['status_id'])||$_POST['status_id']==='0')?$_POST['status_id']:'null', // Выбранный пункт (сравнивается по значению)
        'label' => 'Статус'
      ),
       
       'code' => array(
        'type' => 'text',
        'label' => 'Артикул товара',
        'value' => !empty($_POST['code']) ? $_POST['code'] : null,
      ),

      'sorter' => array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'сортировка по полю',
        'value' => !empty($_POST['sorter'])?$_POST['sorter']:null,
      ),
    );



    if(isset($_POST['applyFilter'])){
      $property['applyFilter'] = array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'флаг примения фильтров',
        'value' => 1,
      );
    }

  
    $filter = new Filter($property);

    $arr = array(
        'id'=> !empty($_POST['id']) ? $_POST['id'] : null,
        'code' => !empty($_POST['code']) ? $_POST['code'] : null,
        'status_id'=> (!empty($_POST['status_id'])||$_POST['status_id']==='0')?$_POST['status_id']:'null',
    );
    
    $userFilter = $filter->getFilterSql($arr, explode('|',$_POST['sorter']));
 
    $sorterData = explode('|',$_POST['sorter']);

    if($sorterData[1]>0){
      $sorterData[3] = 'desc';          
    } else{
      $sorterData[3] = 'asc';   
    }


    $page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс

    $countPrintNonAvailable = MG::getSetting('countPrintNonAvailable');

    if(empty($_POST['sorter'])){
        if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
        $userFilter .= "  ORDER BY `add_datetime` DESC";
    }
 
    $sql = "
      SELECT * FROM `".PREFIX.self::$pluginName."`
      WHERE ".$userFilter." 
    ";

    $navigator = new Navigator($sql, $page, $countPrintNonAvailable); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');   
    $filter = $filter->getHtmlFilter();
    $displayFilter = ($_POST['status_id'] != "null" && !empty($_POST['status_id'])) || isset($_POST['applyFilter']); // так проверяем произошол ли запрос по фильтрам или нет

    include('pageplugin.php');
  
  }

 
  /**
   * Обработчик шотркода вида  [non-available id="<?php echo $data['id']?>"]
   * выполняется когда при генерации страницы встречается  [non-available id="<?php echo $data['id']?>"]
   */
  static function orderNonAvailable($product) {
    $html = '';
    if ($product['id']) {
    $option = MG::getSetting('nonAvailableOption');
    $option = stripslashes($option);
    $options = unserialize($option);        
    $res = DB::query('SELECT p.`count`, p.`image_url`, c.parent_url, c.url, p.`url` as product_url, p.`title`, p.`code`
       FROM `'.PREFIX.'product` p LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id WHERE p.`id`='.DB::quote($product['id']));
    $prodData = DB::fetchArray($res);    
    if ($prodData['count'] != 0) {
      $res = DB::query('SELECT `count` FROM `'.PREFIX.'product_variant` WHERE `product_id`='.DB::quote($product['id']).' AND `count` = 0');
      if (DB::numRows($res)== 0) {
        return false;
      }
    }    
    $nameUser = '';
    $phoneUser = '';
    $emailUser = '';
    $addressUser = '';
    if (isset($_SESSION['user'])) {
      $nameUser = $_SESSION['user']->name;
      $phoneUser = $_SESSION['user']->phone;
      $emailUser = $_SESSION['user']->email;
      $addressUser = $_SESSION['user']->address;
    }
    $arrayImages = explode("|", $prodData['image_url']);
    if (!empty($arrayImages)) {
      $prodData['image_url'] = $arrayImages[0];
    }
    $html = '
	<div class="wrapper-mg-non-available"><button class="mg-non-available-button mg-plugin-btn"  data-product_id = '.$product['id'].'>'.((($options['button'])!= '') ? $options['button'] : 'Заказать').'</button></div>';
    }
    return $html;
  }
  
   /**
   * Получает количество активных записей
   */
  static function getEntityActive() { 
    $exist=false;
    $res = DB::query('SHOW TABLES LIKE "'.PREFIX.self::$pluginName.'"');
    if (DB::numRows($res)){     
      $exist=true;
    }     
   
    if ($exist){
      $sql ="SELECT count(id) as count FROM `".PREFIX.self::$pluginName."` WHERE invisible = 1 ORDER BY sort ASC";
      $res = DB::query($sql);
      if($count = DB::fetchAssoc($res)){
        return $count['count'];
      }    
    }
    return 0;
  }

}
