<?php

/*
  Plugin Name: Обратный звонок
  Description: Плагин является заготовкой для разработчиков плагинов определяется шорткодом [back-ring], имеет страницу настроек, создает в БД таблицу для дальнейшей работы, использует собственный файл локали, свой  CSS и JS скрипы.
  Author: Avdeev Mark
  Version: 1.2.8
 */

new BackRing;

class BackRing {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('back-ring', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [back-ring] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);    
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
    if(URL::isSection('mg-admin')) {
      MG::addInformer(array('count'=>self::getEntityActive(),'class'=>'count-wrap','classIcon'=>'phone-small-icon', 'isPlugin'=>true, 'section'=>'back-ring', 'priority'=>80));
    }
    
    mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/backring.js"></script>');
//   mgAddMeta('<link rel="stylesheet" href="'.SITE.'/mg-admin/design/css/jquery-ui.css">');
 //  mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-core/script/jquery-ui-1.10.3.custom.min.js"></script>'); 
  mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/jquery.maskedinput.min.js"></script>');
  // mgAddMeta('<script> var availableTags = null;</script>');      
    
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    self::createDateBase();
  }

  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <!--<link rel="stylesheet" href="'.SITE.'/mg-admin/design/css/jquery-ui.css">-->
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />  
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
        includeJS("'.SITE.'/mg-core/script/jquery.maskedinput.min.js");  
      </script> 
    ';
         
     $option = MG::getSetting('back-ring-option');
     $option = stripslashes($option);
     $options = unserialize($option); 
     $arrayCity = explode(',',$options['city_list']);
     $jsArrayCity = json_encode($arrayCity);     
     echo '<script> var availableTags = '.$jsArrayCity.';</script>';     
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
      `type` varchar(255) NOT NULL COMMENT 'Тип записи',
      `add_datetime` DATETIME NOT NULL COMMENT 'Дата добавления',
	    `name` text NOT NULL COMMENT 'Имя',
      `comment` text NOT NULL COMMENT 'Комментарий',
      `phone` text NOT NULL COMMENT 'Телефон', 
      `city_id` text NOT NULL COMMENT 'Город',  
      `mission` text NOT NULL COMMENT 'Цель',
      `date_callback` DATE NOT NULL COMMENT 'Дата для перезвона',
      `time_callback` varchar(255) NOT NULL COMMENT 'Время для перезвона',
      `status_id` int(11) NOT NULL COMMENT 'Статус',
      `sort` int(11) NOT NULL COMMENT 'Порядок',
      `invisible` int(1) NOT NULL COMMENT 'Видимость',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
        
    // Если плагин впервые активирован, то задаются настройки по умолчанию 
    if (!$exist) {    
      DB::query("        
       INSERT INTO `".PREFIX.self::$pluginName."` (`id`, `type`, `add_datetime`, `name`, `comment`, `phone`, `city_id`, `mission`, `date_callback`, `time_callback`, `status_id`, `sort`, `invisible`) VALUES
       (1, '','2013-12-11 00:00:00', 'Авдеев Марк', 'Перезвоните, пожалуйста, есть вопрос по оплате.', '8-555-55-43-21', 'Москва', 'Доставка', '2013-12-11', 'C 10 До 19', 1, 0, 0);
      ");
    
      $array = Array(
        'name' => 'true',
        'city' => 'true',
        'comment' => 'true',
        'period' => 'true',
        'mission' => 'true',
        'caphpa' => 'true',
        'date' => 'true',
        'from' => '09',
        'to' => '18',
        'city_list' => 'Москва,Санкт-Петербург,Владивосток',
        'mission_list' => 'Доставка,Оплата,Технические вопросы',  
      );      
   
      
      MG::setOption(array('option' => 'back-ring-option', 'value' => addslashes(serialize($array)))); 
      MG::setOption(array('option' => 'countPrintRowsBackRing', 'value'=>20));
    }
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    
    //получаем опцию back-ringOption в переменную option
    $option = MG::getSetting('back-ring-option');
    $option = stripslashes($option);
    $options = unserialize($option);   
  
    
    self::preparePageSettings();       
    $status = array('null'=>'Не выбрано', 1=>'Ожидает', 2=>'Не дозвониться', 3=>'Завершен');
    
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

    $countPrintRowsBackRing = MG::getSetting('countPrintRowsBackRing');

    if(empty($_POST['sorter'])){
        if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
        $userFilter .= "  ORDER BY `add_datetime` DESC";
    }
 
    $sql = "
      SELECT * FROM `".PREFIX.self::$pluginName."`
      WHERE ".$userFilter."
    ";

    $navigator = new Navigator($sql, $page, $countPrintRowsBackRing); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');   
    $filter = $filter->getHtmlFilter();
    //фильтры конец

    $hours = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');
    foreach ($hours as $item) {    
      $selectHour.="<option value='".$item."'>".$item."</option>"; 
    }
    
    $missions = explode(',',$options['mission_list']);
    foreach ($missions as $id=>$item) {
      $selectMissions.="<option value='".$item."'>".$item."</option>";
    }
   
    include('pageplugin.php');
  }

  
  /**
   * Получает из БД записи
   */
  static function getEntity($count=1) {
    $result = array();
    $sql ="SELECT * FROM `".PREFIX.self::$pluginName."` ORDER BY sort ASC";
    if ($_POST["page"]){
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    $navigator = new Navigator($sql, $page, $count); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');
    $result = array(
      'entity' => $entity,
      'pagination' => $pagination
    );
    return $result;
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
  
  /**
   * Обработчик шотркода вида [back-ring] 
   * выполняется когда при генерации страницы встречается [back-ring] 
   */
  static function handleShortCode() {      
   $option = MG::getSetting('back-ring-option');
   $option = stripslashes($option);
   $options = unserialize($option);   
   
   $hours = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');
   foreach ($hours as $item) {
     if($item>=$options['from']&&$item<=$options['to']){
       $selectHour.="<option value='".$item."'>".$item."</option>";     
     }
   }
   
   $missions = explode(',',$options['mission_list']);

   foreach ($missions as $id => $item) { 
    $selectMissions.="<option value='".$item."'>".$item."</option>";
   } 
    
   $html = "
	<div class='wrapper-back-ring'><button type='submit' class='back-ring-button default-btn'>Обратный звонок</button></div>";
    $html .= "
    <div class='wrapper-modal-back-ring'>    
      <div class='header-modal-back-ring'>
        <h2 class='title-modal-back-ring'>
          Заказать бесплатный звонок
        </h2>
          <a href='javascript:void(0);' class='close-ring-button' ></a>
      </div>
      <div class='content-modal-back-ring'>
        <ul class='modal-ring-list'>
            <li style='".(($options['name']!='true')?'display:none':'')."'>
                <span>Ваше имя:</span>
                <input type='text' name='name' placeholder='Ваше Имя'>
            </li>
            <li>
                <span>Телефон:</span>
                <input type='text' name='phone' placeholder='Телефон' data-mask='00/00/0000'>
            </li>
            <li style='".(($options['city']!='true')?'display:none':'')."'>
                <span>Город:</span>
                <input type='text' name='city_id' placeholder='Город'>
            </li>
            <li style='".(($options['comment']!='true')?'display:none':'')."'>
                <span>Комментарий:</span>
                <textarea class='comment-back-ring' name='comment' placeholder='Комментарий'></textarea>
            </li>
            <li style='".(($options['mission']!='true')?'display:none':'')."'>
                <span>Цель звонка:</span>
                <select name='mission'>".$selectMissions."</select>
            </li>
            <li style='".(($options['period']!='true')?'display:none':'')."'>
                <span>Укажите время и дату, когда вам перезвонить</span>
                <div class='left'>
                     С <select name='from'>".$selectHour."</select>
                    До <select name='to'>".$selectHour."</select>
                </div>
                <div class='right' style='".(($options['date']!='true')?'display:none':'')."'>
                     <input type='text' name='date_callback' placeholder='Дата для перезвона'>
                </div>
                <div style='clear: both;'></div>
            </li>
            <li style='".(($options['caphpa']!='true')?'display:none':'')."'>
                <div class='cap-left'>
                    <img style='margin-top: 5px; border: 1px solid gray; background: url(".PATH_TEMPLATE."/images/cap.png)' src=\"".SITE."/captcha.html\" width=\"140\" height=\"36\">
                    <span>Введите текст с картинки: </span>
                </div>
                <div class='cap-right'>
                    <input type='text' name='capcha' class='captcha'>
                </div>
                <div style='clear:both;'>
                </div>

            </li>
        </ul>
	  	<div class='utm-metka' style='display:none'>".$section."</div>
        <a href='javascript:void(0);' class='send-ring-button  red-btn default-btn' style='display:block;'>Отправить заявку</a>
      </div>
  
    </div>";
     $arrayCity = explode(',',$options['city_list']);
     $jsArrayCity = json_encode($arrayCity);
     $html .= '<script> var availableTags = '.$jsArrayCity.';</script>';
    
    return $html;
  }
  
   /**
    * Сохраняет заявку в базу
    * @param array - массив данным заявки
   */
  static function saveOrderRing($array) {
 
    $sql ="
      INSERT INTO `".PREFIX.self::$pluginName."` 
      (`id`, `type`, `nameEntity`, `value`, `sort`, `invisible`)
      (1, 'img', 'name1', 'src1', 1,1)
    ";
    $res = DB::query($sql);
    if($res){
      return true; 
    }
    return false;
  }

}