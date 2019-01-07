<?php
/*
  Plugin Name: Брошенная корзина
  Description: Плагин отслеживает брошенные корзины и отправляет до 3-х напоминающих письма вручную или автоматически, возможна отправка по cron, время отправки писем задается в настройках. Для автоматической отправки по cron, необходимо на хостинге настроить выполнение файла ваш сайт/ac-mgcron каждый час.
  Author: Чуркина Дарья
  Version: 1.0.2
 */

new abandonedCart;

class abandonedCart {
  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  public static $duplicate = array(); //масив с данными из карзины

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddAction('SmalCart_getCartData', array(__CLASS__, 'addToBDcart'), 1);
    mgAddAction('models_order_addOrder', array(__CLASS__, 'orderCart'), 1);
    mgAddAction('mg_start', array(__CLASS__, 'fullCartFromBD'));
    mgAddAction('mg_end', array(__CLASS__, 'checkCartAndTrigg'), 1);
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    if (URL::isSection('mg-admin')) {
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
      mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/script.js"></script>');
    } else {
      mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/ab-cart.js"></script>');
    }
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    // Запрос для проверки, был ли плагин установлен ранее.
    $exist=false;
    $result = DB::query('SHOW TABLES LIKE "'.PREFIX.self::$pluginName.'"');
    while($row = DB::fetchArray($result)){    
      $exist=true;
    }  
    DB::query("
     CREATE TABLE IF NOT EXISTS  `".PREFIX.self::$pluginName."` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
     `content` TEXT NOT NULL COMMENT 'Содержимое корзины',
     `id_user` INT( 11 ) NOT NULL COMMENT 'id пользователя',
     `email`  varchar(255) NOT NULL COMMENT 'email не авторизованный',
     `status` INT( 11 ) NOT NULL COMMENT 'статус корзины',
     `date_add` DATETIME NOT NULL COMMENT 'Дата первого добавления',
     `date_act` DATETIME NOT NULL COMMENT 'Дата последнего обновления корзины',
     `id_letter`  VARCHAR(32) NOT NULL COMMENT 'номер последнего письма',
     `date_update` DATETIME NOT NULL COMMENT 'Дата отправки письма',
     `hash` varchar(255) NOT NULL COMMENT 'hash для отказа',
     `click` DATETIME NOT NULL COMMENT 'переход по ссылке',
     `date_order` DATETIME NOT NULL COMMENT 'Дата оформления заказа',
     PRIMARY KEY ( `id` )
     ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
   ");
    DB::query("
     CREATE TABLE IF NOT EXISTS  `".PREFIX.self::$pluginName."-letters` (
     `id` INT( 11 ) NOT NULL ,
     `title` TEXT NOT NULL COMMENT 'Название',
     `auto` INT( 11 ) NOT NULL COMMENT 'автоотправление',
     `period`  INT(11) NOT NULL COMMENT 'интервал отправления',
     `time` INT( 11 ) NOT NULL COMMENT 'тип отправления',
     `subject` TEXT NOT NULL COMMENT 'Тема письма',
     `text` TEXT NOT NULL COMMENT 'Текст письма',
     `special` TEXT NOT NULL COMMENT 'Специальные предложения',
     PRIMARY KEY ( `id` )
     ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
   ");   
    DB::query("
     CREATE TABLE IF NOT EXISTS  `".PREFIX.self::$pluginName."-owncart` (
     `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
     `content` TEXT NOT NULL COMMENT 'Содержимое корзины',
     `url` varchar(255) NOT NULL COMMENT 'url корзины',
     `title`  varchar(255) NOT NULL COMMENT 'название корзины',
     `date_create` DATETIME NOT NULL COMMENT 'Дата создания',
     `click` INT( 11 ) NOT NULL COMMENT 'Количество переходов',
     PRIMARY KEY ( `id` )
     ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
   ");
    // Если плагин впервые активирован, то задаются настройки по умолчанию 
    if (!$exist) {    
      DB::query("        
        INSERT INTO `".PREFIX.self::$pluginName."-letters` 
         (`id`, `title`, `auto`, `period`, `time`, `subject`, `text`, `special`)
       VALUES
         (1, 'Первое письмо', '0', '1','0','Помочь оформить заказ в магазине ', 
         'Здравствуйте, {name}! Вы забыли корзину с покупками на сайте: {cartContent}
         Если у Вас возникли трудности с оформлением заказа, позвоните нам на 8-800-555-55-55 и
         мы поможем завершить покупку {linkOrder}. Если товары корзины Вам уже не нужны, пожалуйста, перейдите 
         по ссылке: {linkCancel}. Спасибо! Отвечать на это письмо не нужно!', 'null'),         
         (2, 'Второе письмо', '0', '3','1','Купите со скидкой!', 
         'Здравствуйте, {name}! Недавно вы были в магазине и забыли корзину: {cartContent}
         Она ждет Вас, а мы предлагаем Вам оформить заявку со скидкой 20% {linkOrder}. Если товары корзины Вам уже не нужны, пожалуйста, перейдите 
         по ссылке: {linkCancel}. Спасибо! Отвечать на это письмо не нужно!', 'a:1:{s:8:\"discount\";s:2:\"20\";}'),
         (3, 'Третье письмо', '0', '7','1','Вы забыли корзину' , 
         'Здравствуйте, {name}! Недавно вы были в магазине и забыли корзину: {cartContent}. 
         Она ждет Вас, а мы предлагаем Вам оформить заявку со скидкой 20% {linkOrder}. Если товары корзины Вам уже не нужны, пожалуйста, перейдите 
         по ссылке: {linkCancel}. Спасибо! Отвечать на это письмо не нужно!', 'null')     
      ");    
      MG::setOption(array('option' => 'countPrintRowsCarts', 'value' => 20));
      MG::setOption(array('option' => 'countPrintRowOwnCarts', 'value' => 20));
    } 
    $file = PLUGIN_DIR.'abandoned-cart/mg-pages/basket.php';
    $newfile = 'basket.php';
    if (!file_exists(PAGE_DIR.$newfile)) {
      copy($file, PAGE_DIR.$newfile);
    }
    $file = PLUGIN_DIR.'abandoned-cart/mg-pages/cron.php';
    $newfile = 'ac-mgcron.php';
    if (!file_exists(PAGE_DIR.$newfile)) {
      copy($file, PAGE_DIR.$newfile);
    }
    $option = MG::getSetting('abandonedCartOption');
    if (empty($option)) {
      $array = Array(
        'return' => 'Вернуться к корзине',
        'cancel' => 'Спасибо, я уже все купил!',
        'diff' => '3',
      );

      MG::setOption(array('option' => 'abandonedCartOption', 'value' => addslashes(serialize($array))));
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
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $option = MG::getSetting('abandonedCartOption');
    $option = stripslashes($option);
    $options = unserialize($option); 
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $entity = self::getCarts();
    $displayFilter = isset($_POST['applyFilter'])&&$_POST['applyFilter']==1 ? true : false;
    $pagination = $entity['pagination'];
    $filter = $entity['filter'];
    $itemsCount = $entity['itemsCount'];
    $entity = $entity['entity'];
    $letters = self::getLetters();
    self::preparePageSettings();    
    $owncartRezult = self::getOwnCart();
    $owncart = $owncartRezult['entity'];
    $owncartpage = $owncartRezult['pagination'];
    //получаем опцию buyClickOption в переменную option
    $countPrintRowsCarts = MG::getSetting('countPrintRowsCarts');
    $countPrintRowsOwnCarts = MG::getSetting('countPrintRowsOwnCarts');
    $statusClass = array('1'=>'in-delivery', '2'=>'dont-paid', '3'=>'paid');
    $example = DB::query('SELECT `title` FROM `'.PREFIX.'product` LIMIT 0,1');
    if ($rez=DB::fetchArray($example)) {
       $exampleName = $rez['title'];
    }   
    $codes= array();
    // Запрос для проверки , существуют ли промокоды.  
    $res = DB::query('SELECT `active` FROM `'.PREFIX.'plugins` WHERE `folderName`="promo-code"');
    if (DB::numRows($res)) {
        $res = DB::query('SELECT `id`, `code`, `percent` FROM `'.PREFIX.'promo-code` 
          WHERE invisible = 1 
          AND now() >= `from_datetime`
          AND now() <= `to_datetime`');
        while ($code = DB::fetchAssoc($res)) {
          $codes[] = $code;
        }
    }
    $errorTime = '';
    $sql = DB::query('SELECT now() as `date`');
    if ($time = DB::fetchArray($sql)) {
    if (date('d.m.Y H:i') != date('d.m.Y H:i', strtotime($time['date'])) ){
      $diff = (time() - strtotime($time['date']))/ 3600; 
      $errorTime = 'Обратите внимание! Время настроек php и MySql различны! Для автоматической отправки устанавливайте время с учетом разницы: '.$diff.' ч. - прибавьте это значение к необходимому времени.';
    }
    }
    include('pageplugin.php');
  }

  /**
   * Получает из БД информацию о корзинах
   */
  static function getCarts() {
    $res = DB::query('
      SELECT MIN(date_add) as min, MAX(date_add) as max 
      FROM `'.PREFIX.self::$pluginName.'`'
    );
    if ($row = DB::fetchObject($res)) {
      $minDate = $row->min;
      $maxDate = $row->max;
    }
    $row = '';
    USER::AccessOnly('1,4', 'exit()');
    //фильтры 
     $property = array(
      'email' => array(
        'type' => 'text',
        'label' => 'email',
        'value' => !empty($_POST['email']) ? $_POST['email'] : null,
      ),
      'status' => array(
        'type' => 'select',
        'option' =>array('null'=>self::$lang['STATUS_NONE'],'1'=>self::$lang['STATUS_1'], '2'=>self::$lang['STATUS_2'],'3'=>self::$lang['STATUS_3']),
        'selected' => (!empty($_POST['status'])||$_POST['status']==='0')?$_POST['status']:'null', // Выбранный пункт (сравнивается по значению)
        'label' => self::$lang['STATUS']
      ),
         'date_add' => array(
          'type' => 'beetwen', //Два текстовых инпута
          'label1' => self::$lang['FILTR_PRICE5'],
          'label2' => self::$lang['FILTR_PRICE6'],
          'min' => !empty($_POST['date_add'][0]) ? $_POST['date_add'][0] : $minDate,
          'max' => !empty($_POST['date_add'][1]) ? $_POST['date_add'][1] : $maxDate,
          'factMin' => '',
          'factMax' => '',
          'special' => 'date',
          'class' => 'date'
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
        'email'=> !empty($_POST['email']) ? $_POST['email'] : null,
        'status'=> (!empty($_POST['status'])||$_POST['status']==='0')?$_POST['status']:'null',
        'date_add' => array(!empty($_POST['date_add'][0]) ? $_POST['date_add'][0] : $minDate, !empty($_POST['date_add'][1]) ? $_POST['date_add'][1] : $maxDate, 'date'),
    );
    
    $userFilter = $filter->getFilterSql($arr, explode('|',$_POST['sorter']));
 
    $sorterData = explode('|',$_POST['sorter']);

    if($sorterData[1]>0){
      $sorterData[3] = 'desc';          
    } else{
      $sorterData[3] = 'asc';   
    }

    $page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    $countPrintRowsCarts = MG::getSetting('countPrintRowsCarts') ? MG::getSetting('countPrintRowsCarts'):20 ;

    if(empty($_POST['sorter'])){
        if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
        $userFilter .= "  ORDER BY `date_add` DESC";
    } 
    $sql = "
      SELECT * FROM `".PREFIX.self::$pluginName."` 
      WHERE ".$userFilter."
    ";
    $navigator = new Navigator($sql, $page, $countPrintRowsCarts); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');   
    $filter = $filter->getHtmlFilter();
    $totalItem = $navigator->getNumRowsSql(); 
    foreach ($entity as $keyCart => $carts) { 
      $cartHtml = self::getHtmlCart($carts['content']);     
      $entity[$keyCart]['summ'] = $cartHtml['summCart'];
      $entity[$keyCart]['contentHide'] = $cartHtml['cartdata'];
      $entity[$keyCart]['promo'] = $cartHtml['promo'];
    }
    $result = array(
        'entity' => $entity,
        'pagination' => $pagination,
        'filter' => $filter,
        'itemsCount' => $totalItem,
    );
    return $result;
  }
  /**
   * Получает из БД информацию о письмах
   */
  static function getLetters() {
    USER::AccessOnly('1,4', 'exit()');
    $letters = array();
    $result = DB::query("
      SELECT `title`, `auto`, `id` 
      FROM `".PREFIX.self::$pluginName."-letters` 
    ");
    while ($row = DB::fetchAssoc($result)) {
      $letters[$row['id']] = $row;
    }
    return $letters;
  }
  /*
   * 
   */
  static public function getOwnCart(){
    $page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    $countPrintRowsOwnCarts = MG::getSetting('countPrintRowsOwnCarts') ? MG::getSetting('countPrintRowsOwnCarts'):20 ;
    $sql = "
      SELECT * FROM `".PREFIX.self::$pluginName."-owncart`";
    $navigator = new Navigator($sql, $page, $countPrintRowsOwnCarts); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');  
    foreach ($entity as $keyCart => $carts) { 
      $cartHtml = self::getHtmlCart($carts['content']);     
      $entity[$keyCart]['summ'] = $cartHtml['summCart'];
      $entity[$keyCart]['contentHide'] = $cartHtml['cartdata'];
      $entity[$keyCart]['promo'] = $cartHtml['promo'];
    }
    $result = array(
        'entity' => $entity,
        'pagination' => $pagination,
    );
    return $result;
}

  /**
   * 
   * Обработчик хука функции addToCart($id, $count = 1, $property = array('property' => '', 'propertyReal' => ''), $variantId = null)
   * записываем в бд информацию о корзине и пользователе
   */
  static function addToBDcart($arg) {
    self::addUpdateCart();    
    return $arg['result'];
  }

  /*
   * Функция добавления или обновления информации о корзине в бд при  любых изменениях
   * содержимого корзины.
   */

  static function addUpdateCart() {
    if (!empty($_SESSION['cart'])){
      self::$duplicate = array(
        'couponCode'=> $_SESSION['couponCode'],
        'propertySetArray' => $_SESSION['propertySetArray'],
        'cart' => $_SESSION['cart']);
    } else {
      self::$duplicate = self::$duplicate ? self::$duplicate  : array();
    }    
    
      $email = '';
      $id_user = '';
      $byEmail = '';
      if (USER::isAuth()){
        $email = $_SESSION['user']->email;
        $id_user = $_SESSION['user']->id;
        $byEmail = '(`email`='.DB::quote($email).' AND `status`=1) ';
      }      
      $cartContent = '';
      $cartId = '';
      if ($_COOKIE['cart_id']) {
        $cartId = '(`id`='.DB::quote(intval($_COOKIE['cart_id'])).' AND `status`=1)';
        $cartId = ($byEmail!='' ? ' OR '.$cartId : $cartId);
      }
      if ($email || $cartId) {
        $res = DB::query('SELECT `id`,`content`, `email` FROM `'.PREFIX.self::$pluginName.'`'
              . 'WHERE '.$byEmail.$cartId);
        if ($cart = DB::fetchArray($res)) {
          if (!empty(self::$duplicate)) {
            $emailUp = ($cart['email'] == '' && $email !='') ? ', `email`='.DB::quote($email) : '' ;
            $cartContent = addslashes(serialize(self::$duplicate));
            DB::query('UPDATE `'.PREFIX.self::$pluginName.'` SET  content ='.DB::quote($cartContent)
              . ', `date_act`= now()'.$emailUp.' WHERE `id`='.DB::quote($cart['id']));
            setcookie("cart_id", $cart['id'], time() + 3600*24);
          } else {
            if($_REQUEST['delFromCart']) {
              DB::query('DELETE FROM `'.PREFIX.self::$pluginName.'` 
                 WHERE `id`='.DB::quote($cart['id']).' AND status=1');
              setcookie ("cart_id", "", time() - 3600);
            }
            
          }
        }         
      }
      if (!$cartContent && !empty(self::$duplicate)) {
        $cartContent = addslashes(serialize(self::$duplicate));      
        DB::query('INSERT INTO `'.PREFIX.self::$pluginName.'`'
                . '(`id`,`content`,`id_user`, `email`, `status`, `date_add`, `date_act`)'
                . 'VALUES (null, '.DB::quote($cartContent).','.DB::quote($id_user).' ,'.DB::quote($email).',1,now(), now() )');
        $id = DB::insertId();
        setcookie("cart_id", $id, time() + 3600*24);
      }  
      return true;
  }
 // переход по ссылке из письма - заполняем корзину оставленными товарами
static function openCartLink($hash='', $id=null){
  if ($hash&&$id) {
    $sql = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'` '
            . 'WHERE `hash`='.DB::quote($hash).' AND `id`='.DB::quote(intval($id)));
    if ($row = DB::fetchArray($sql)) {
      $cart = unserialize(stripslashes($row['content']));      
      $_SESSION['couponCode'] = $cart['couponCode'] ? $cart['couponCode'] : '';
      $_SESSION['propertySetArray'] = $cart['propertySetArray'] ? $cart['propertySetArray'] : '';
      $_SESSION['cart'] = $cart['cart'] ? $cart['cart'] : '';
      DB::query('UPDATE `'.PREFIX.self::$pluginName.'` SET `click`=now() WHERE `id`='.DB::quote(intval($id)));
      setcookie("cart_id", $id, time() + 3600*24);
      return true;
    }
  }
  return false;
}
// очищаем корзину и меняем статус корзины на "отменена"
static function cancelCartLink($hash='', $id=null){
  if ($hash&&$id) {
    DB::query('UPDATE `'.PREFIX.self::$pluginName.'` SET `status`=2, `click`=now() '
            . 'WHERE `hash`='.DB::quote($hash).' AND `id`='.DB::quote(intval($id)));   
      unset($_SESSION['propertySetArray']);
      unset($_SESSION['cart']);
      return true;
  }
  return false;
}
// при оформлении заказа меняем статус корзины и добавляем дату оформления заказа
static function orderCart($arg){
  $id = $arg['result']['id'];
  if ($id) {
    $res = DB::query('SELECT `user_email`, `add_date` FROM `'.PREFIX.'order` WHERE `id`='.DB::quote($id));
    $cartId = '';
    if ($row = DB::fetchArray($res)) {
      if ($_COOKIE['cart_id']) {
        $cartId = ' OR (`id`='.DB::quote(intval($_COOKIE['cart_id'])).' AND `status`=1)';
      }
      if ($row['user_email'] || $cartId) {
        $res = DB::query('SELECT `id` FROM `'.PREFIX.self::$pluginName.'`'
              . 'WHERE (`email`='.DB::quote($row['user_email']).' AND `status`=1) '.$cartId );
        if ($cart = DB::fetchArray($res)) {
            DB::query('UPDATE `'.PREFIX.self::$pluginName.'` SET `status` = 3
              , `date_order` = '.DB::quote($row['add_date']).' WHERE `id`='.DB::quote($cart['id']));
            setcookie ("cart_id", "", time() - 3600);
          } 
        }         
      }
  }  
  return $arg['result'];  
}
// открытие корзины по ссылке
  static public function openOwnCartLink($id) {
    if ($id) {
    $sql = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'-owncart` '
            . 'WHERE `id`='.DB::quote(intval($id)));
    if ($row = DB::fetchArray($sql)) {
        $cart = unserialize(stripslashes($row['content']));      
        $_SESSION['couponCode'] = $cart['couponCode'] ? $cart['couponCode'] : '';
        $_SESSION['propertySetArray'] = $cart['propertySetArray'] ? $cart['propertySetArray'] : '';
        $_SESSION['cart'] = $cart['cart'] ? $cart['cart'] : '';
        DB::query('UPDATE `'.PREFIX.self::$pluginName.'-owncart` SET `click`=`click`+1 WHERE `id`='.DB::quote(intval($id)));
      return true;
      }   
  }
  return false;
  }

// заполняет корзину из БД по авторизованному email или id из куки 
  public function fullCartFromBD(){
    // проверка включен ли плагин
    $on = false;
    $res = DB::query('SELECT `active` FROM `'.PREFIX.'plugins` WHERE `folderName`='.DB::quote(self::$pluginName));
    $row = DB::fetchArray($res);
    if ($row['active']=='1') {
      $on = true;
    } else {
      return false;
    }    
    if (URL::getQueryParametr('logout')) {
      self::$duplicate = array(
        'couponCode'=> $_SESSION['couponCode'],
        'propertySetArray' => $_SESSION['propertySetArray'],
        'cart' => $_SESSION['cart']);
    }
    // заполнение корзины из бд если авторизован или есть куки 
    if (!empty($_SESSION['cart'])||URL::isSection('mg-admin')||URL::isSection('mgadmin')){
      return true;
    }
      $byEmail = '';
      $email = '';
      if (USER::isAuth()){
        $email = $_SESSION['user']->email;
        $byEmail = ' (`email`='.DB::quote($email).' AND `status`=1) ';
      }  
      $cartId = '';
      if ($_COOKIE['cart_id']) {
        $cartId = ' (`id`='.DB::quote(intval($_COOKIE['cart_id'])).' AND `status`=1)';
        $cartId = ($byEmail!='' ? ' OR '.$cartId : $cartId);
      }
      if ($email || $cartId) {
        $res = DB::query('SELECT `id`, `content` FROM `'.PREFIX.self::$pluginName.'`'
                  . 'WHERE '.$byEmail.$cartId);
            if ($row = DB::fetchArray($res)) {
              $cart = unserialize(stripslashes($row['content']));     
      $_SESSION['couponCode'] = $cart['couponCode'] ? $cart['couponCode'] : '';
      $_SESSION['propertySetArray'] = $cart['propertySetArray'] ? $cart['propertySetArray'] : '';
      $_SESSION['cart'] = $cart['cart'] ? $cart['cart'] : '';
      setcookie("cart_id", $row['id'], time() + 3600*24);
      self::$duplicate = array(
        'couponCode'=> $_SESSION['couponCode'],
        'propertySetArray' => $_SESSION['propertySetArray'],
        'cart' => $_SESSION['cart']);
                              } 
            }         
          
    
   
}
/**
 * отправка письма 
 * @param type $let - массив с информацией о пиьсме
 * @param type $cart - массив с информацией о корзине
 * @return boolean
 */

private function sendLetter($let, $cart) {
  // подготовка текста письма
  $option = MG::getSetting('abandonedCartOption');
  $option = stripslashes($option);
  $options = unserialize($option); 
  if (time() - (strtotime($cart['date_update'])&&strtotime($cart['date_update']) >0 ? strtotime($cart['date_update']) : 0) > $options['diff']*60*60) {
  $id = $cart['id'];
  $text = $let['text'];
  if (empty($cart['hash'])){      
    $cart['hash'] = htmlspecialchars(crypt($cart['email']));
  }
  $link = '<a href="'.SITE.'/basket?cart='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['return'].'</a>';
  $linkCan = '<a href="'.SITE.'/basket?cancel='.$cart['hash'].'&id='.$id.'" target="blank">'.$options['cancel'].'</a>';
  $content = self::getHtmlCart($cart['content']);
  ob_start();
  include ('layout_cart.php');
  $html = ob_get_contents();
  ob_clean();
  $text = str_replace('{name}', $cart['name'], $text);
  $text = str_replace('{linkOrder}', $link, $text);
  $text = str_replace('{linkCancel}', $linkCan, $text);
  $text = str_replace('{cartContent}', $html, $text);
  $sitename = MG::getSetting('sitename');
  if(preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,6}$/', $cart['email'])){
  Mailer::sendMimeMail(array(
			  'nameFrom' => MG::getSetting('noReplyEmail'),
			  'emailFrom' => MG::getSetting('noReplyEmail'),
			  'nameTo' => $sitename,
			  'emailTo' => $cart['email'],
			  'subject' => $let['subject'] ? $let['subject'] : self::$lang['SUBJECT_DEFAULT'],
			  'body' => $text,
			  'html' => true
			));
  DB::query('UPDATE `'.PREFIX.self::$pluginName.'` SET '
          . '`date_update`=now(), `id_letter`=CONCAT(`id_letter`,\'|'.DB::quote($let['id'], true).'\'), `hash` ='.DB::quote($cart['hash']).' 
          WHERE `id`='.DB::quote($id));
  }
  return true;
  }
  return false;
}
// html таблица корзины
  public function getHtmlCart($cart){
  $productPositions = array();
      $totalSumm = 0;
      $cartArray = unserialize(stripslashes($cart));
    if (!empty($cartArray['cart'])) {
      $currencyRate = MG::getSetting('currencyRate');   
      $currencyShopIso = MG::getSetting('currencyShopIso');
      $variantsId = array();
      $productsId = array();
      foreach ($cartArray['cart'] as $key => $item) {
        if (!empty($item['variantId'])) {
          $variantsId[] = $item['variantId'];
        }
        $productsId[] = $item['id'];        
      }
      $products_all = array();
      $variants_all = array();
      if (!empty($variantsId)) {
          $ids = implode(',', $variantsId);
          $variants_res = DB::query('SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`,
          p.currency_iso
          FROM `'.PREFIX.'product_variant` pv   
          LEFT JOIN `'.PREFIX.'product` as p ON 
            p.id = pv.product_id
          LEFT JOIN `'.PREFIX.'category` as c ON 
            c.id = p.cat_id       
          WHERE pv.id IN ('.trim(DB::quote($ids, true)).')');
          while ($variant_row = DB::fetchAssoc($variants_res)) {
            $variants_all[$variant_row['id']] = $variant_row;
           }
        }
        if (!empty($productsId)) {
          $ids = implode(',', array_unique($productsId));
          $product_res = DB::query('
            SELECT  CONCAT(c.parent_url,c.url) as category_url,
            p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
            p.`currency_iso` 
            FROM `'.PREFIX.'product` p
            LEFT JOIN `'.PREFIX.'category` c
            ON c.id = p.cat_id
            WHERE p.id IN ('.DB::quote($ids, true).')');
   
          if (!empty($product_res)) {
            while ($result = DB::fetchAssoc($product_res)) {
              $products_all[$result['id']] = $result;
            }          
          }
        }
      foreach ($cartArray['cart'] as $key => $item) {
        $variant = '';
        if (!empty($item['variantId'])) {
        //  $variants = $itemPosition->getVariants($item['id']);
          $variant = $variants_all[$item['variantId']];
        }
        // Заполняет массив информацией о каждом продукте по id из куков.
        // Если куки не актуальны, пропускает товар.
        $product = $products_all[$item['id']];
        if (!empty($product)) {
          $product['property'] = $cartArray['cart'][$key]['propertySetId'];
          $product['property_html'] = htmlspecialchars_decode(str_replace('&amp;', '&', $cartArray['cart'][$key]['property']));
          $product['propertySetId'] = $cartArray['cart'][$key]['propertySetId'];

          if (!empty($variant)) {           
            $product['price'] = $variant['price'];
            $product['code'] = $variant['code'];        
            $product['title'] .= " ".$variant['title_variant'];
            $product['variantId'] = $variant['id'];
          	$product['price_course']  = $variant['price_course'];
          }
          // если установлен формат без копеек то округлим стоимость.
          $priceFormat = MG::getSetting('priceFormat');          
          if(in_array($priceFormat, array('1234','1 234','1,234',''))){               
            $price = round($product['price_course']);          
          }else{
            $price = $product['price_course'];     
          }          
          if ($item['id'] == $product['id']) {
            $count = $item['count'];
            $price = SmalCart::plusPropertyMargin($price, $item['propertyReal'], $currencyRate[$product['currency_iso']]);
            $product['price'] = $price;            
            $product['priceInCart'] = MG::priceCourse($product['price'] * $count)." ".MG::getSetting('currency');          
            $arrayImages = explode("|", $product['image_url']);            
            if (!empty($arrayImages)) {
              $product['image_url'] = SITE.'/uploads/thumbs/30_'.$arrayImages[0];
            }
          }
          $product['category_url'] = (SHORT_LINK == '1' ? '' : $product['category_url'].'/');
          $row['category_url'] = ($row['category_url'] == '/' ? '' : $row['category_url']);
          $product['link'] = SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog'.'/').$product["product_url"];
          $product['countInCart'] = $item['count'];

          if ($product['countInCart'] > 0) {
            $productPositions[] = $product;
          }
          $totalSumm += $product['price'] * $item['count'];          
         
        }
      }
    }
    $cartData = $productPositions;
    $totalSumm = MG::numberFormat($totalSumm).' '.MG::getSetting('currency');
    return array('summCart'=>$totalSumm, 'cartdata'=>$cartData, 'promo'=>$cartArray['couponCode']);
}
public function checkCartAndTrigg() {
 $res = DB::query('SELECT `active` FROM `'.PREFIX.'plugins` WHERE `folderName`='.DB::quote(self::$pluginName));
    $row = DB::fetchArray($res);
    if ($row['active']=='1') {
      $on = true;
    } else {
      return false;
    } 
    $res = DB::query('SELECT * FROM `'.PREFIX.self::$pluginName.'-letters` '
            . 'WHERE `auto`=1 ORDER BY `period`, `time`');    
    $arrLet= array();
    while ($row = DB::fetchArray($res)) {
      $arrLet[] = $row;
    }
    if (!empty($arrLet)) {
      foreach ($arrLet as $let) {
      $res = DB::query('SELECT c.*, u.name FROM `'.PREFIX.self::$pluginName.'` c '
              . 'LEFT JOIN `'.PREFIX.'user` u ON c.`id_user`= u.`id` WHERE `status` = 1 '
              . 'AND c.`id_letter` NOT LIKE \'%'.DB::quote($let['id'], true).'%\' AND c.`email`<>\'\' LIMIT 100');
      $arrCart= array();
      while ($row = DB::fetchArray($res)) {
        $arrCart[] = $row;
      }
      if (!empty($arrCart)) {
        // проверка времени прошедшее после послденего действия корзины       
          foreach ($arrCart as $cart) {
            if (stristr($cart['id_letter'], $let['id'])===FALSE) {              
              $days = $let['time']=='1' ? 24 : 1;
              $timeSend = $let['period'] * 3600 * $days;
              if (time() - (strtotime($cart['date_act'])&&strtotime($cart['date_act'])>0 ? strtotime($cart['date_act']) : 0) >= $timeSend) {
                self::sendLetter($let, $cart);
              }
           }
          }          
        }
      }
    }
}
}