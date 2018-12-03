<?php

/*
  Plugin Name: Мои желания
  Description: Плагин "Мои желания" позволяет пользователю добавлять понравившийся товар в отдельную вкладку в личном кабинете, а администратору магазина делать персональную скидку этому покупателю и отправлять уведомление на email.  
  Author: <img src="http://mogutashop.ru/favicon.ico" /><a href="http://mogutashop.ru" style="text-decoration: none;color: #000;border-bottom: 1px dotted;">MogutaSHOP.ru</a>

  Version: 1.4.0
 */

class MyDesiresPlugin {
	
	public static $listRoles = array(
  'null' => 'Не выбрано',
  '1' => 'Администратор',
  '2' => 'Пользователь',
  '3' => 'Менеджер',
  '4' => 'Модератор'
);
	
	public static $desires = array();
	public static $options = array();
	
  // инициализация составляющих плагина
  public function __construct(){
  	mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activateAction'));
	mgDeactivateThisPlugin(__FILE__, array(__CLASS__, 'deactivateAction'));
    mgAddAction(__FILE__, array(__CLASS__, 'pagePluginAction'));
	mgAddAction('mg_start', array(__CLASS__, 'mgStartAction'));
	mgAddAction('models_cart_applycoupon', array(__CLASS__, 'applyCouponAction'), 1);
	mgAddAction('Models_Product_getProduct', array(__CLASS__, 'getProductAction'), 1);
	mgAddAction('Models_Order_addOrder', array(__CLASS__, 'addOrderAction'), 1);
	mgAddAction('Models_Catalog_getList', array(__CLASS__, 'getProductAction'), 1);
	//mgAddAction('mg_meta', array(__CLASS__, 'meta'));

    mgAddShortcode('wish-list', array(__CLASS__, 'shortCodeAction'));
	mgAddShortcode('addtowishlist', array(__CLASS__, 'addShortCodeAction'));
	
  }

  static function activateAction() {
    DB::query("
     CREATE TABLE IF NOT EXISTS  `".PREFIX."desires` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `add_date` datetime NOT NULL,
		`end_date` datetime NULL,
        `status` tinyint(1) DEFAULT '0',
		`activate_link` VARCHAR(255) DEFAULT '',
		`enable_discount` tinyint(1) DEFAULT '0',
		`discount_percent` FLOAT DEFAULT '0',
		`used_discount` tinyint(1) DEFAULT '0',
		`user_visible` tinyint(1) DEFAULT '1',
        PRIMARY KEY (`id`)
     ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
   ");
   
   
   $options = self::getPluginConfig();
   
   if (empty($options['emailText'])) {
	   $options['emailText'] = '<h3>Уважаемый {USER_NAME}!</h3>
Наш магазин рад Вам сообщить, что специально для Вас, цена на товар:<br>
<a href="{DISCOUNT_PRODUCT_URL}"><img src="{PRODUCT_BIG_IMAGE_URL}" /></a><br>
<a href="{DISCOUNT_PRODUCT_URL}">{DISCOUNT_PRODUCT_TITLE}</a><br>
снижена на <b><span style="font-size:24px;color:red;">{DISCOUNT_PERCENT}%</span></b>!<br>
Для активации скидки вы должны перейти по ссылке <a href="{DISCOUNT_ACTIVATE_URL}">{DISCOUNT_ACTIVATE_URL}</a>.
При добавлении продукта в корзину цена на него будет автоматически снижена.<br>
Удачных покупок!<br />
<br />
{SHOP_LOGO}';
   }
   
   if (!isset($options['enableCounter'])) $options['enableCounter'] = true;
   if (!isset($options['enableManyDesires'])) $options['enableManyDesires'] = false;
   if (!isset($options['timerValue'])) $options['timerValue'] = 0;
   
	$options = addslashes(serialize($options));
	MG::setOption(array(
		'option' => 'desiresPluginSettings',
		'value' => $options,
	));

	$s = file_get_contents(__DIR__."/../../mg-admin/design/css/style.css");
	$s .= "\n".'.desire-icon {background: url("../../../mg-plugins/desires/images/wishlist_off.png"); display:block; position: relative; top:8px; left:8px; width:16px; height:14px;}'."\n";
	file_put_contents(__DIR__."/../../mg-admin/design/css/style.css", $s);

	copy(dirname(__FILE__).'/desiretemplate.php', SITE_DIR.'mg-pages/desiretemplate.php');
  }

	static function deactivateAction() {	
		if (file_exists(SITE_DIR.'mg-pages/desiretemplate.php')) {
			unlink(SITE_DIR.'mg-pages/desiretemplate.php');
		}
	}
  
  //выводит страницу плагина в админке
  static function pagePluginAction(){
    $lang = PM::plugLocales('desires');
    if($_POST["page"])
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс (нужно для пагинации)
	$countPrintRowsDesires = MG::getOption('countPrintRowsDesires');
	
	if (empty($countPrintRowsDesires)) $countPrintRowsDesires = 10;
	
    $navigator = new Navigator("
      SELECT  d.*, us.name, us.sname, us.email, us.date_add as user_register_date, us.role as user_role, p.title, p.url as product_url,
      c.url, c.parent_url
      FROM ".PREFIX."desires d
		LEFT JOIN `".PREFIX."user` us ON us.id = d.user_id
		LEFT JOIN `".PREFIX."product` p ON p.id = d.product_id
		LEFT JOIN `".PREFIX."category` c ON p.cat_id = c.id
      ORDER BY d.`add_date` DESC", $page, $countPrintRowsDesires); //определяем класс
    $desires = $navigator->getRowsSql(); // выборка
    $pagination = $navigator->getPager('forAjax'); // Постраничная навигация

    // подключаем view для страницы плагина
    include 'pagePlugin.php';
  }
  
  static function getStatus($status, $discount_percent=0) {
	  switch ($status) {
		  case 0:
			return '<span style="color:green">Актуально</span>';
		  break;
		  case 1:
			return '<span style="color:green">'.$discount_percent.'%</span>';
		  break;
		  case 2:
			return '<span style="color:red">Отклонено</span>';
		  break;
		  
		  case 3:
			return '<span style="color:red">Удалено пользователем</span>';
		  break;
	  }
  }
  
  static function getStatusForUser($status, $discount_percent=0) {
	  switch ($status) {
		  case 0:
			return '<span style="color:green">Актуально</span>';
		  break;
		  case 1:
			return '<span style="color:green">'.$discount_percent.'%</span>';
		  break;
		  case 2:
			return '<span style="color:green">Актуально</span>';
		  break;
	  }
  }

	static function addShortCodeAction($options) {
		//if (!USER::isAuth()) return '';
		
		$product_id = (int)$options['product'];

		$data = self::$options;
		
		$class = '';
		if (isset(self::$desires[$product_id])) {
			$class = 'desire-added';
		}
		
		$counter = '';
		if ($data['enableCounter']) {
			$result = DB::fetchAssoc(DB::query('SELECT COUNT(*) as count FROM '.PREFIX.'desires WHERE product_id='.$product_id.''));
			$counter = ' ('.$result['count'].')';
		}
		
		$button_html = '<div class="wishlist"><a href="#" class="btn addToWishList '.$class.'" data-item-id="'.$product_id.'">'.$data['buttonTitle'].''.$counter.'</a></div>';
		return $button_html;
	}
  
	private static function getPluginConfig() {
		$data = stripslashes(MG::getSetting('desiresPluginSettings'));
		$data = unserialize($data);
		if (!isset($data['buttonTitle'])) $data['buttonTitle'] = 'В мои желания';
		if (!isset($data['emailText'])) $data['emailText'] = '';
		if (!isset($data['emailTemplate'])) $data['emailTemplate'] = 'email.php';
		
		return $data;
	}
  
  //метод срабатывающий при вызове шорткода в контексте сайта
  static function shortCodeAction(){
    if($_GET["page"])
      $page = $_GET["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс

	$config = self::getPluginConfig();
  
    $countRows = $config['desiresLcPerPage'];
    $navigator = new Navigator("
	SELECT  d.*, us.name, us.sname, us.email, p.title, p.image_url, p.url as product_url, 
	c.rate,(p.price_course + p.price_course * (IFNULL(c.rate,0))) as `price_course`, c.url, c.parent_url
	FROM ".PREFIX."desires d
		LEFT JOIN `".PREFIX."user` us ON us.id = d.user_id
		LEFT JOIN `".PREFIX."product` p ON p.id = d.product_id
		LEFT JOIN `".PREFIX."category` c ON c.id = p.cat_id
	WHERE d.user_id = ".DB::quote(USER::getThis()->id)." AND d.user_visible = 1
	
	ORDER BY d.`add_date` DESC", $page, $countRows); //определяем класс
    $tickets = $navigator->getRowsSql();
    $pagination = $navigator->getPager();

	$currentDate = new DateTime();
	//$interval = $endTime->diff($startDate);
	//$max_date = $interval->format('%m ');
	//$max_date = $endTime->format('Y-m-d H:i:s');
	
    $html = '
      <div id="my-desires" class="desires-container">
        <h4>Список ваших желаний:</h4>
        <table class="ticket-table">
          <thead>
            <tr>
              <th class="add_date">Дата</th>
              <th class="product">Товар</th>
              <th class="status">Статус</th>
			  <th class="price">Цена</th>
              <th class="actions">Действия</th>
            </tr>
          </thead>
        <tbody class="ticket-tbody">
    ';

    if(!empty($tickets)){
      foreach($tickets as $data){
        $status = self::getStatusForUser($data['status'], $data['discount_percent']);
		$data['image_url'] = explode('|', $data['image_url']);
        $data['image_url'] = $data['image_url'][0];
		$url = mgImageProductPath($data['image_url'], $data['product_id'], 'small');
		
		$image = '<img class="product-image" src="'.$url.'" alt="">';
		
		if ($data['status'] == 1 && !$data['used_discount'] && !is_null($data['end_date'])) {
			if ($config['enableLcTimer']) {
				$time = '<span class="desiresEnableTimer" data-time="'.(strtotime($data['end_date'])-time()).'"><span class="clockDays"></span>:<span class="clockHours"></span>:<span class="clockMinutes"></span>:<span class="clockSeconds"></span>';
			}
			else {
				$time = 'Действует по: '.$data['end_date'];
			}
		}
		else {
			$time = '';
		}
		
		$price = '<span class="price">'.MG::priceCourse($data['price_course'],true).'</span>';
		if ($data['status'] == 1) {
			$new_price = $data['price_course'] - $data['price_course']/100*$data['discount_percent'];
			$price = '<span class="old-price">'.MG::priceCourse($data['price_course'], true)." ".MG::getSetting("currency").'</span><br>
			<span class="price">'.MG::priceCourse($new_price, true).'</span>
			';
		}

		if($config['useLinks'] == 1) {
		$html .= '
          <tr data-id="'.$data['id'].'">
             <td class="add_date">'.date('d.m.y H:i', strtotime($data['add_date'])).'</td>
             <td class="product"><a href="'.SITE."/".$data["parent_url"].$data["url"].'/'.$data['product_url'].'">'.$image.' '.$data['title'].'</a></td>
             <td class="closed">'.$status.'<br>'.$time.'</td>
			 <td class="price">'.$price." ".MG::getSetting("currency").'</td>
             <td class="actions">
             <ul class="action-list">
               <li class="delete-row"><a class="default-btn desire-delete" href="#" style="margin-bottom: 5px" data-id="'.$data['id'].'">Удалить</a></li>
			   <li>
<a href="'.SITE.'/catalog?inCartProductId='.$data['product_id'].'" class="addToCart product-buy" data-item-id="'.$data['product_id'].'">В корзину</a>
			   </li>
             </ul>
             </td>
        </tr>';
		}
		else
		{
        $html .= '
          <tr data-id="'.$data['id'].'">
             <td class="add_date">'.date('d.m.y H:i', strtotime($data['add_date'])).'</td>
             <td class="product"><a href="'.SITE.'/'.$data['product_url'].'">'.$image.' '.$data['title'].'</a></td>
             <td class="closed">'.$status.'<br>'.$time.'</td>
			 <td class="price">'.$price." ".MG::getSetting("currency").'</td>
             <td class="actions">
             <ul class="action-list">
               <li class="delete-row"><a class="default-btn desire-delete" style="margin-bottom: 5px" href="#" data-id="'.$data['id'].'">Удалить</a></li>
			   <li>
<a href="'.SITE.'/catalog?inCartProductId='.$data['product_id'].'" class="addToCart product-buy" data-item-id="'.$data['product_id'].'">В корзину</a>
			   </li>
             </ul>
             </td>
        </tr>';
    	}
      }
    } else {
      $html .= '<tr class="noneRows"><td colspan="6">Нет желаний</td></tr>';
    }
	
    $html .= '
        </tbody>
       </table>';
	   
	    $html .= $pagination;
	   
    $html .= '</div>';

    return $html;
  }
  
  public static function mgStartAction() {

  	if (isset($_GET['desire']) && USER::isAuth()) {
		  $result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND activate_link = '.DB::quote($_GET['desire']));
		  $desire = DB::fetchAssoc($result);
		  if (isset($desire['id']) && $desire['used_discount'] == 0) {
			if (!is_null($desire['end_date'])) {
				if (strtotime($desire['end_date']) > time()) DB::query('UPDATE '.PREFIX.'desires SET enable_discount = 1 WHERE id='.$desire['id']);
			  }
			  else {
				  DB::query('UPDATE '.PREFIX.'desires SET enable_discount = 1 WHERE id='.$desire['id']);
			  }
		  }
	 }
	 
	 if (USER::isAuth()) {
	 self::$desires = array();
	 $result = DB::query('SELECT id,product_id FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.'');
	 while ($row = DB::fetchAssoc($result)) self::$desires[$row['product_id']] = $row;
	 }
	 
	 self::$options = self::getPluginConfig();
	 self::setDefaultDiscount();

  }
  
	public function applyCouponAction($args) {
		
		if (!USER::isAuth()) return $args['result'];
		//file_put_contents(__DIR__."/log", print_r($args, true));
		$price = $args['args'][1];
		$product = $args['args'][2];
		$percent = 0;

		$product_id = $product['id'];
		$user_id = USER::getThis()->id;
		
		$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND product_id = '.(int)$product_id.' AND enable_discount=1 AND used_discount=0');
		$desire = DB::fetchAssoc($result);
		
		if (isset($desire['id'])) {
			if (!isset($_SESSION['desires'])) $_SESSION['desires'] = array();
			
			$percent = $desire['discount_percent'];
			$_SESSION['desires'][$desire['id']] = $desire['id'];
			
			$args['result'] = $price - $price * $percent / 100;
			return $args['result'];
		}
		
		return $args['result'];
  }
  
  public function getProductAction($args) {

  		if (!USER::isAuth()) return $args['result']; 
		
  		//if(empty($args["result"]["catalogItems"])) return $args["result"];

  		$items = array();
  		if (is_array($args["result"]["catalogItems"])) { $items = $args["result"]["catalogItems"]; }
  		else
  		{
  			$items = array($args["result"]);
  		}

  		$arr = array();

  		foreach ($items as $item) { $arr[] = $item["id"]; }
  		
  		//file_put_contents(__DIR__."/log", print_r($items, true));
  		/*
  		$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND product_id IN ('.implode(",",$arr).') AND enable_discount=1 AND used_discount=0');
		$desire = DB::fetchAssoc($result);

		//file_put_contents(__DIR__."/log", print_r($desire, true));

		if ($desire['product_id'] == $items[$i]["id"]) {
			//if (!isset($_SESSION['desires'])) $_SESSION['desires'] = array();
			
			$percent = $desire['discount_percent'];
			//$_SESSION['desires'][$desire['id']] = $desire['id'];
			$price = $items[$i]['price_course'];
			$args['result']['price'] = MG::priceCourse($price - $price * $percent / 100, true);
			$args['result']['price_course'] = MG::priceCourse($args['result']['price'], true);
			$args['result']['old_price'] = MG::priceCourse($price, true);
			$args['result']["priceInCart"] = MG::priceCourse($args['result']['price'], true)." ".MG::getSetting("currency");
			return $args['result'];
		}*/
		
	  	//if (!USER::isAuth()) return $args['result']; 
		
		$user_id = USER::getThis()->id;
		// Если в каталоге
		if (isset($args['result']['catalogItems'])) {
			$desires = array();
			$ids = array();
			
			if (count($args['result']['catalogItems'])>0) {
				foreach ($args['result']['catalogItems'] as $item) $ids[] = $item['id'];
				$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND product_id IN ('.implode(',', $ids).') AND enable_discount=1 AND used_discount=0');
			}
			else
			{
				$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE 1=1');
			}
			
			while ($row = DB::fetchAssoc($result)) {
				$desires[$row['product_id']] = $row;
			}
			
			if (!isset($_SESSION['desires'])) $_SESSION['desires'] = array();
			
			foreach ($args['result']['catalogItems'] as $key =>  $item)  {
				$price = str_replace(' ', '', $item['price']);

				
				if (isset($desires[$item['id']])) {
					$desire = $desires[$item['id']];
					$percent = $desire['discount_percent'];
					
					$_SESSION['desires'][$desire['id']] = $desire['id'];
					$price = $price - $price * $percent / 100;
					//if(self::$options["roundResult"] == "true") $price = round($price);
					$args['result']['catalogItems'][$key]['price'] = MG::priceCourse($price);
					//$args['result']['catalogItems'][$key]['price_course'] = MG::priceCourse($args['result']['catalogItems'][$key]['price']);
				}
			}
			
			return $args['result'];
		}
		
  		// Если карточка продукта
		$product_id = $args['result']['id'];
		
		$price = $args['result']['price'];

		$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND product_id = '.(int)$product_id.' AND enable_discount=1 AND used_discount=0');
		$desire = DB::fetchAssoc($result);

		if (isset($desire['id'])) {
			if (!isset($_SESSION['desires'])) $_SESSION['desires'] = array();
			
			$percent = $desire['discount_percent'];
			$_SESSION['desires'][$desire['id']] = $desire['id'];
			
			$price = $price - $price * $percent / 100;
			//if(self::$options["roundResult"] == "true") $price = round($price);
					
			$args['result']['price'] = MG::priceCourse($price, false);
			if (MG::get("controller") == "controllers_product") $args['result']['price_course'] = MG::priceCourse($price, false);
			
			return $args['result'];
		}
		
		return $args['result'];
  }
  
	public function addOrderAction($args) {
		if (isset($_SESSION['desires'])) {
			foreach ($_SESSION['desires'] as $id) {
				DB::query('UPDATE '.PREFIX.'desires SET status=1, used_discount=1 WHERE id='.$id);
			}
		}
		return $args['result'];
  }

  public function getActualDesiresCount(){
   $query="SHOW TABLES LIKE '".PREFIX."desires'";
   $result=DB::query($query);
   if(DB::numRows($result)){   
      $sql = "
        SELECT `id`
        FROM `".PREFIX."desires`
        WHERE `status`= 0";

      $res = DB::query($sql);
      $count = DB::numRows($res);      
   }
   return $count?$count:0;   
  }

  static function setDefaultDiscount()
  {

  	$config = self::$options;
  	if($config["defaultUse"] == "true" and (int)$config["defaultPeriod"] >= 0) {
  		$defaultDiscount = $config["defaultDiscount"];
  		$defaultPeriod = $config["defaultPeriod"];
  		$res = DB::query("SELECT * FROM `".PREFIX."desires` WHERE status=0 AND add_date < DATE_SUB(NOW(), INTERVAL $defaultPeriod DAY)");
  		while($row = DB::fetchAssoc($res)) {
  			$user = (array)USER::getUserById($row["user_id"]);
			$product = DB::fetchAssoc(DB::query("SELECT
        		c.title as category_title,
        		CONCAT(c.parent_url,c.url) as category_url,
        		p.url as product_url,
        		p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`
      			FROM `".PREFIX."product` p
      			LEFT JOIN `".PREFIX."category` c
       			 ON c.id = p.cat_id
      			WHERE p.id=".$row["product_id"]));
	
			$discount = (float)$defaultDiscount;
			$timer = $config['timerValue'];
	
			$endTimeValue = '';
			if ($timer > 0) {
				$endTime = new DateTime();
				$interval = new DateInterval('P'.$timer.'D');
				$endTime->add($interval);
				$endTimeValue = '"'.$endTime->format('Y-m-d H:i:s').'"';
				$startDate = new DateTime();
				$interval = $endTime->diff($startDate);
				$max_date = $endTime->format('Y-m-d H:i:s');
			}
			else {
				$endTimeValue = 'NULL';
				$max_date = '';
			}

			$unic = md5(uniqid().time().microtime()).uniqid();
	
			$content = $config['emailText'];
			$product['image_url'] = explode("|", $product['image_url']);
			$product['image_url'] = $product['image_url'][0];
	
			$small_url = mgImageProductPath($product['image_url'], $product['id'], 'small');
			$big_url = mgImageProductPath($product['image_url'], $product['id'], 'big');
			if(SHORT_LINK == 0) { 
				$mainlink = SITE."/".$product["category_url"]."/".$product['url'];
				$link = SITE."/".$product["category_url"]."/".$product['url'].'?desire='.$unic;
			}
			else { 
				$mainlink = SITE."/".$product['url'];
				$link = SITE.'/'.$product['url'].'?desire='.$unic;
			}
			$replaceData = array(
				'{USER_NAME}' => $user['name'],
				'{USER_SURNAME}' => $user['sname'],
				'{DISCOUNT_PRODUCT_URL}' => $mainlink,
				'{DISCOUNT_PRODUCT_TITLE}' => $product['title'],
				'{DISCOUNT_PERCENT}' => $discount,
				'{DISCOUNT_ACTIVATE_URL}' => $link,
				'{PRODUCT_SMALL_IMAGE_URL}' => $small_url,
				'{PRODUCT_BIG_IMAGE_URL}' => $big_url,
				'{SHOP_LOGO}' => mgLogo(),
				'{DISCOUNT_MAX_DATE}' => $max_date,
			);

			$content = str_replace(array_keys($replaceData), array_values($replaceData), $content);
	
			$emailText = $content;
			ob_start();
			include dirname(__FILE__).'/tpl/'.$config['emailTemplate'];
			$content = ob_get_clean();
				
			$m= new Mail('UTF-8');
			$m->From(MG::getSetting('noReplyEmail'));
			$m->To($user['email']);   // кому, в этом поле так же разрешено указывать имя
			$m->Subject('Цена на товар "'.$product['title'].'" снижена специально для вас!');
			$m->Body($content, 'html');
     		$m->Send();    // отправка

			DB::query('UPDATE '.PREFIX.'desires SET activate_link="'.$unic.'", enable_discount=1, discount_percent="'.$discount.'", used_discount=0, status=1,end_date='.$endTimeValue.' WHERE id='.$row["id"]);
		}
	}

  	return;
  }

}


mgAddMeta('<script src="'.SITE.'/mg-plugins/desires/js/desires-public.js"> </script>');
mgAddMeta('<link rel="stylesheet" href="'.SITE.'/mg-plugins/desires/css/style.css" type="text/css" />');

if(!in_array(VER,array("v3.0.1","v3.0.2","v3.0.4"))){  
  $desires = new MyDesiresPlugin();
  MG::addInformer(array('count'=>$desires->getActualDesiresCount(),'class'=>'count-wrap','classIcon'=>'desire-icon', 'isPlugin'=>true, 'section'=>'desires', 'priority'=>80));
}
