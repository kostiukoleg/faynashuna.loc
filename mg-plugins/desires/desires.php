<?php

/**
 * Класс Tiket наследник стандарного Actioner
 * Предназначен для выполнения действий, запрошеных  AJAX функциями
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Desires extends Actioner{

  // Сохранение ответа в БД
  public function addProduct(){
    $this->messageSucces = 'Желание добавлено';
    $this->messageError = 'Ошибка отправки!';

	if (!USER::isAuth()) {
		$this->messageError = 'Добавлять товары в список желаний могут только зарегистрированные пользователи!';
		return false;
	}
	
	$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND product_id='.(int)$_POST['product_id'].' ORDER BY add_date DESC');
	
	$config = $this->getPluginConfig();
	$enableManyDesires = (bool)$config['enableManyDesires'];
	
	$row = DB::fetchAssoc($result);
	if (isset($row['id'])) {
		switch ($row['status']) {
			case 0:
				$this->messageError = 'Этот товар уже есть в списке ваших желаний!';
				return false;
			break;
			
			case 1:
				if (!$enableManyDesires) {
					$this->messageError = 'Вы уже получали скидку на этот товар!';
					return false;
				}
			break;
			
			case 2:
				if (!$enableManyDesires) {
					$this->messageError = 'Администратор отклонил ваше желание на этот товар!';
					return false;
				}
			break;
			
			case 3:
				if (!$enableManyDesires) {
					$this->messageError = 'Вы уже добавляли этот товар в ваш список желаний!';
					return false;
				}
			break;
		}
	}

	$result = DB::query('
		INSERT INTO '.PREFIX.'desires SET user_id='.USER::getThis()->id.', product_id='.(int)$_POST['product_id'].', add_date=NOW(), status=0, enable_discount=1
	');

	//отправляем письмо админу
	$settings = $this->getPluginConfig();
	if(strlen($settings["sendEmail"]) > 0) {
		$emails = explode(",", $settings["sendEmail"]);
	}
	else
	{
		$emails = explode(",", MG::getSetting("adminEmail"));
	}

	foreach($emails as $i=>$val):
		$emails[$i] = trim($val);
	endforeach;

	$mprod = new Models_Product;
	$product = $mprod->getProduct($_POST['product_id']);
	$msg = "Пользователь ".USER::getThis()->name.", с адресом электронной почты ".USER::getThis()->email.' запросил скидку на товар <a href="'.SITE."/".$product["category_url"]."/".$product["product_url"].'">'.$product["title"]."</a>";
	foreach($emails as $email):
		Mailer::sendMimeMail(array(
			"emailFrom" => MG::getSetting("noReplyEmail"),
			"emailTo" => $email,
			"body" => $msg,
			"html" => true,
			"subject" => "Мои желания. Запрос на скидку",
			));
	endforeach;	

    return true;
  }
  
  public function delete(){
    $this->messageSucces = 'Желание удалено';
    $this->messageError = 'Ошибка удаления!';

	$id = (int)$_POST['desire_id'];
	$user_id = USER::getThis()->id;

	$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE id='.$id);

	$row = DB::fetchAssoc($result);
	if (!isset($row['id']) || $row['user_id'] != $user_id) {
		return false;
	}

	DB::query('UPDATE '.PREFIX.'desires SET user_visible=0, status=3 WHERE id='.$id);
	
    return true;
  }
  
	public function remove(){
		$this->messageSucces = 'Желание удалено';
		$this->messageError = 'Ошибка удаления!';
		if (!USER::AccessOnly("1")) return false;
	
		if (is_array($_POST['desire_id'])) {
			foreach ($_POST['desire_id'] as $id) {
				DB::query('DELETE FROM '.PREFIX.'desires WHERE id='.(int)$id);
			}
		
			return true;
		}
		else {
			$id = (int)$_POST['desire_id'];

			$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE id='.$id);

			$row = DB::fetchAssoc($result);
			if (!isset($row['id'])) {
				return false;
			}

			DB::query('DELETE FROM '.PREFIX.'desires WHERE id='.$id);
			
			return true;
		}
	}
  
  public function getCSV() {
    $this->messageSucces = 'CSV файл создан';
    $this->messageError = 'Ошибка выгрузки!';

	$file = SITE_DIR.'/desires_last.csv';
	
	$data = array();
	$data[] = array(
		'Номер', 'Дата', 'Покупатель', 'E-mail', 'Дата регистрации', 'Продукт', 'Статус'
	);
	
	$result = DB::query("SELECT  d.*, us.name, us.sname, us.email, us.date_add as user_register_date, p.title
      FROM ".PREFIX."desires d
		LEFT JOIN `".PREFIX."user` us ON us.id = d.user_id
		LEFT JOIN `".PREFIX."product` p ON p.id = d.product_id
      ORDER BY d.`add_date` DESC");
	
	while ($row = DB::fetchAssoc($result)) {
		
		$status = strip_tags(MyDesiresPlugin::getStatus($row['status']));
		
		$data[] = array(
			$row['id'], $row['add_date'], $row['name'].' '.$row['sname'], $row['email'], $row['user_register_date'], $row['title'], $status
		);
	}
	
	$content = '';
	foreach ($data as $data_item) {
		$content .= implode(';', $data_item)."\n";
	}

	$content = iconv('UTF-8', 'Windows-1251', $content);
	
	$fp = fopen($file, 'w');
	fwrite($fp, $content);
	fclose($file);

	$this->data['url'] = SITE.'/desires_last.csv';
	
    return true;
  }
  
  public function confirm() {
    $this->messageSucces = 'Ссылка отправлена на E-mail пользователя';
    $this->messageError = 'Ошибка отправки!';

	$id = (int)$_POST['desire_id'];

	$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE id='.$id);

	$row = DB::fetchAssoc($result);
	if (!isset($row['id'])) {
		$this->messageError = 'Такого желания не существует!';
		return false;
	}
	
	$data = $this->getPluginConfig();
	
	$user = DB::fetchAssoc(DB::query('SELECT * FROM '.PREFIX.'user WHERE id='.$row['user_id']));
	$product = DB::fetchAssoc(DB::query("SELECT
        c.title as category_title,
        CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url,
        p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`
      FROM `".PREFIX."product` p
      LEFT JOIN `".PREFIX."category` c
        ON c.id = p.cat_id
      WHERE p.id=".$row["product_id"]));
	
	$discount = (float)$_POST['discount'];
	$timer = (int)$_POST['timer'];
	if ($timer == -1) $timer = $data['timerValue'];
	
	$endTimeValue = '';
	if ($timer > 0) {
		$endTime = new DateTime();
		$interval = new DateInterval('P'.$timer.'D');
		$endTime->add($interval);
		$endTimeValue = '"'.$endTime->format('Y-m-d H:i:s').'"';
		
		$startDate = new DateTime();
		$interval = $endTime->diff($startDate);
		//$max_date = $interval->format('%m ');
		$max_date = $endTime->format('Y-m-d H:i:s');
	}
	else {
		$endTimeValue = 'NULL';
		$max_date = '';
	}

	$unic = md5(uniqid().time().microtime()).uniqid();
	
	//$link = SITE.'/'.$product['url'].'?desire='.$unic;
	
	
	$content = $data['emailText'];
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
	include dirname(__FILE__).'/tpl/'.$data['emailTemplate'];
	$content = ob_get_clean();
	
     $m= new Mail('UTF-8');
     $m->From(MG::getSetting('noReplyEmail'));
     //$m->ReplyTo(); // куда ответить, тоже можно указать имя
     $m->To($user['email']);   // кому, в этом поле так же разрешено указывать имя
     $m->Subject('Цена на товар "'.$product['title'].'" снижена специально для вас!');
     $m->Body($content, 'html');

     $m->Send();    // отправка

	DB::query('UPDATE '.PREFIX.'desires SET activate_link="'.$unic.'", enable_discount=1, discount_percent="'.$discount.'", used_discount=0, status=1,end_date='.$endTimeValue.' WHERE id='.$id);
	
    return true;
  }

 public function cancel() {
    $this->messageSucces = 'Желание пользователя отклонено';
    $this->messageError = 'Ошибка отправки!';

	$id = (int)$_POST['desire_id'];

	$result = DB::query('SELECT * FROM '.PREFIX.'desires WHERE id='.$id);

	$row = DB::fetchAssoc($result);
	if (!isset($row['id'])) {
		$this->messageError = 'Такого желания не существует!';
		return false;
	}

	DB::query('UPDATE '.PREFIX.'desires SET status=2 WHERE id='.$id);

    return true;
  }
  
	public function getData() {
		$this->messageSucces = 'Данные получены';
	   
		$result = DB::query('SELECT  d.*, us.name, us.sname, us.email, us.date_add as user_register_date, p.title, p.price,
			c.rate,(p.price_course + p.price_course * (IFNULL(c.rate,0))) as `price_course`
			FROM '.PREFIX.'desires d
			LEFT JOIN `'.PREFIX.'user` us ON us.id = d.user_id
			LEFT JOIN `'.PREFIX.'product` p ON p.id = d.product_id
			LEFT JOIN `'.PREFIX.'category` c ON c.id = p.cat_id
			WHERE d.id = '.(int)$_POST['id'].'
		');
	
		$data = DB::fetchAssoc($result);
		$this->data['data'] = $data;
		return true;
	}
	
	public function getSettings() {
		$this->messageSucces = 'Настройки получены';
	   
		

		$this->data['settings'] = $this->getPluginConfig();
		return true;
	}
	
	public function saveSettings() {
		$this->messageSucces = 'Настройки сохранены!';
		
		$data = $_POST['settings'];
		$data = addslashes(serialize($data));
		MG::setOption(array(
			'option' => 'desiresPluginSettings',
			'value' => $data,
		));
		
		return true;
	}
	
	private function getPluginConfig() {
		$data = stripslashes(MG::getSetting('desiresPluginSettings'));
		$data = unserialize($data);
		if (!isset($data['buttonTitle'])) $data['buttonTitle'] = '';
		if (!isset($data['emailText'])) $data['emailText'] = '';
		if (!isset($data['emailTemplate'])) $data['emailTemplate'] = 'email.php';
		
		return $data;
	}
	
/**
* Устанавливает количество отображаемых записей в разделе новостей
* @return boolean
*/
	public function setCountPrintRowsComments() {
		USER::AccessOnly('1,4','exit()'); 
		$count = 20;
		if (is_numeric($_POST['count'])&&!empty($_POST['count'])) {
			$count = $_POST['count'];
		}
		
		MG::setOption(array('option' => 'countPrintRowsDesires', 'value' => $count));
		return true;
	}

	public function getCount()
	{
		if (!USER::isAuth()) { 
			$this->data['count'] = -1;
			return true;
		}

		$result = DB::query('SELECT COUNT(id) as cnt FROM '.PREFIX.'desires WHERE user_id='.USER::getThis()->id.' AND enable_discount=1 AND used_discount=0 AND user_visible=1');
		$desire = DB::fetchAssoc($result);
		$this->data['count'] = $desire['cnt'];
		return true;
	}
}
