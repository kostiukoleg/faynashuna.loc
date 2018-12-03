<?php 
/**
 * Класс RetailCRM используется для выгрузки и синхронизации с RetailCRM
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class RetailCRM {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {
		$lang = MG::get('lang');
		$ds = DIRECTORY_SEPARATOR;
		$ls = Models_Order::$status;
		if (class_exists('statusOrder')) {
			$dbQuery = DB::query('SELECT `id_status`, `status` FROM `'.PREFIX.'mg-status-order`');
			while ($dbRes = DB::fetchArray($dbQuery)) {
				$listStatus[$dbRes['id_status']] = $dbRes['status'];
			}
		} else {
			foreach ($ls as $key => $value) {
				$listStatus[$key] = $lang[$value];
			}
		}

		$res = DB::query("SELECT `id`, `name` FROM `".PREFIX."delivery`");
		while ($row = DB::fetchAssoc($res)) {
			$deliverys[$row['id']] = $row['name'];
		}

		$res = DB::query("SELECT `id`, `name` FROM `".PREFIX."payment`");
		while ($row = DB::fetchAssoc($res)) {
			$payments[$row['id']] = $row['name'];
		}

		if (MG::enabledStorage()) {
			$storages = unserialize(stripslashes(MG::getSetting('storages')));
			foreach ($storages as $key => $value) {
				$storage[$value['id']] = $value['name'];
			}
		}

		$opFieldz = unserialize(stripslashes(MG::getSetting('optionalFields')));
		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));
		$opFields = array();

		foreach ($opFieldz as $value) {
			$value['name'] = MG::translitIt(trim($value['name']));
			if ($options['retailOpFields'][MG::translitIt($value['name'])]) {
				$opFields[$value['name']] = $options['retailOpFields'][$value['name']];
			}
			else{
				$opFields[$value['name']] = '';
			}
		}

// MG::loger($options);
		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';
		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Сохраняет настройки.
	* @param string $url URL личного кабинета retailCRM
	* @param string $api Ключ приложения
	* @param string $site Cимвольный код магазина
	* @param string $warehouseCode Cимвольный код склада
	* @param string $paid Cимвольный код статуса оплаты - "Оплачен"
	* @param string $notPaid Cимвольный код статуса оплаты - "Не оплачен"
	* @param string $syncUsers Синхронизировать ли пользователей
	* @param string $syncOrders Синхронизировать ли заказы
	* @param string $syncRemains Синхронизировать ли остатки
	* @param array $retailStorage Массив соответствия складов
	* @param array $retailStatuses Массив соответствия статусов заказов
	* @param array $retailDeliverys Массив соответствия доставок
	* @param array $retailPayments Массив соответствия оплат
	* @param string $retailIndividual Cимвольный код типа заказа - "Физическое лицо"
	* @param string $retailLegal Cимвольный код типа заказа - "Юридическое лицо"
	* @param string $reportSync Отправка письма при ошибке синхронизации
	* @return bool
	*/
	static function saveOptions($url, $api, $site, $warehouseCode, $paid, $notPaid, $syncUsers, $syncOrders, $syncRemains, $retailStorage, $retailOpFields, $retailStatuses, $retailDeliverys, $retailPayments, $retailIndividual, $retailLegal, $reportSync, $useOrderNumber){

		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));

		$retailStatuses = array_filter($retailStatuses);
		$retailDeliverys = array_filter($retailDeliverys);
		$retailPayments = array_filter($retailPayments);
		$retailStorage = array_filter($retailStorage);

		$options['retailIndividual'] = $retailIndividual;
		$options['retailLegal'] = $retailLegal;
		$options['retailStatuses'] = $retailStatuses;
		$options['retailDeliverys'] = $retailDeliverys;
		$options['retailPayments'] = $retailPayments;
		$options['retailStorage'] = $retailStorage;
		$options['retailOpFields'] = $retailOpFields;
		$options['url'] = $url;
		$options['api'] = $api;
		$options['site'] = $site;
		$options['warehouseCode'] = $warehouseCode;
		$options['paid'] = $paid;
		$options['notPaid'] = $notPaid;
		$options['syncUsers'] = $syncUsers;
		$options['syncOrders'] = $syncOrders;
		$options['syncRemains'] = $syncRemains;
		$options['reportSync'] = $reportSync;
		$options['useOrderNumber'] = $useOrderNumber;

		MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));

		return true;
	}
   /**
	* Стартовая выгрузка.
	* @param string $uploadUsers выгружать ли пользователей
	* @param string $uploadOrders выгружать ли заказы
	* @return bool
	*/
	static function uploadAll($uploadUsers, $uploadOrders){
		$version = 'v5';
		$errorUsers = false;
		$errorOrders = false;

		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));

		if ($uploadUsers == 'true') {
			
			$data_customers = array();
			$res = DB::query("SELECT * FROM `".PREFIX."user`");
			while($row = DB::fetchAssoc($res)) {
				if(!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {$row['email'] = 'incorrect.email@mail.err';}
				@set_time_limit(30);
				$data_customers[] = self::createUserArr($row, $options);
			}

			$url = $options['url'].'/api/'.$version.'/customers/upload';

			$data_chunks = array_chunk($data_customers, 50);
			foreach ($data_chunks as $key => $value) {
				@set_time_limit(30);
				$data = array();
				$GLOBALS['rcrmQuery'] = $value;
				$data['customers'] = json_encode($value);
				$data['site'] = $options['site'];
				$data['apiKey'] = $options['api'];
				
				if (!self::request($data, $url, 'post')) {
					$errorUsers = true;
				}
				usleep(100000);
			}
			if (!$errorUsers) {
				$options['usersUpdated'] = date('Y-m-d H:i:s');
				MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
			}
		}
		else{
			$options['usersUpdated'] = date('Y-m-d H:i:s');
			MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
		}

		if ($uploadOrders == 'true') {
			$data_orders = array();
			$res = DB::query("SELECT * FROM `".PREFIX."order`");
			while($row = DB::fetchAssoc($res)) {
				@set_time_limit(30);
				$tmp = self::createOrderArr($row, $options);
				if ($tmp !== false) {
					$data_orders[] = $tmp;
				}
			}

			$url = $options['url'].'/api/'.$version.'/orders/upload';

			$data_chunks = array_chunk($data_orders, 50);
			foreach ($data_chunks as $key => $value) {
				@set_time_limit(30);
				$data = array();
				$GLOBALS['rcrmQuery'] = $value;
				$data['orders'] = json_encode($value);
				$data['site'] = $options['site'];
				$data['apiKey'] = $options['api'];
				
				if (!self::request($data, $url, 'post')) {
					$errorOrders = true;
				}
				usleep(100000);
			}
			if (!$errorOrders) {
				$options['ordersUpdated'] = date('Y-m-d H:i:s');
				MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
			}
		}
		else{
			$options['ordersUpdated'] = date('Y-m-d H:i:s');
			MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
		}
			
		if (!$errorUsers && !$errorOrders) {
			return true;
		}
		else{
			return false;
		}
	}
   /**
	* Синхранизация.
	* @return bool
	*/
	static function syncAll(){
		$version = 'v5';
		$errorUsers = false;
		$errorOrders = false;
		$errorRemains = false;

		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));

		if ($options['syncUsers'] == 'true') {

			$url = $options['url'].'/api/'.$version.'/customers/history';
			$data['site'] = $options['site'];
			$data['apiKey'] = $options['api'];
			if ($options['lastSyncID_user'] > 0) {
				$data['filter']['sinceId'] = $options['lastSyncID_user'];
			}

			$GLOBALS['rcrmQuery'] = $data;
			$result = self::request($data, $url, 'get');

			if (!$result) {
				$errorUsers = true;
			}

			$tmp = self::processUsers($result, $options);
			if ($tmp > 0) {
				$options['lastSyncID_user'] = $tmp;
			}
			
			if ($result['pagination']['totalPageCount'] > 1) {
				for ($i=2; $i < $result['pagination']['totalPageCount'] + 1; $i++) { 
					$data['page'] = $i;
					$GLOBALS['rcrmQuery'] = $data;
					$res = self::request($data, $url, 'get');
					if (!$res) {
						$errorUsers = true;
					}
					usleep(100000);
					$tmp = self::processUsers($res, $options);
					if ($tmp > 0) {
						$options['lastSyncID_user'] = $tmp;
					}
				}
			}

			unset($data);

			$res = DB::query("SELECT * FROM `".PREFIX."user` WHERE `last_updated` > ".DB::quote($options['usersUpdated']));
			while($row = DB::fetchAssoc($res)) {

				if(!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {$row['email'] = 'incorrect.email@mail.err';}
				$data_customer = self::createUserArr($row, $options);

				$url = $options['url'].'/api/'.$version.'/customers/'.$row['id'].'/edit';
				$data = array();
				$GLOBALS['rcrmQuery'] = $data_customer;
				$data['customer'] = json_encode($data_customer);
				$data['site'] = $options['site'];
				$data['apiKey'] = $options['api'];
				
				$resp = self::request($data, $url, 'post');

				if ($resp['errorMsg'] == "Not found") {
					$url = $options['url'].'/api/'.$version.'/customers/create';
					$resp = self::request($data, $url, 'post');
				}
				if ($resp == false) {
					$errorUsers = true;
				}
				usleep(100000);
			}

			if (!$errorUsers) {
				$options['usersUpdated'] = date('Y-m-d H:i:s');
			}
		}

		if ($options['syncOrders'] == 'true') {
			$GLOBALS['rcrmOrderMismatch'] = false;
			$url = $options['url'].'/api/'.$version.'/orders/history';
			$data = array();
			$data['site'] = $options['site'];
			$data['apiKey'] = $options['api'];
			if ($options['lastSyncID_order'] > 0) {
				$data['filter']['sinceId'] = $options['lastSyncID_order'];
			}
			$GLOBALS['rcrmQuery'] = $data;
			$result = self::request($data, $url, 'get');

			if (!$result) {
				$errorOrders = true;
			}

			$tmp = self::processOrders($result, $options);
			if ($tmp > 0) {
				$options['lastSyncID_order'] = $tmp;
			}
			
			if ($result['pagination']['totalPageCount'] > 1) {
				for ($i=2; $i < $result['pagination']['totalPageCount'] + 1; $i++) { 
					$data['page'] = $i;
					$GLOBALS['rcrmQuery'] = $data;
					$res = self::request($data, $url, 'get');
					if (!$res) {
						$errorOrders = true;
					}
					usleep(100000);
					$tmp = self::processOrders($res, $options);
					if ($tmp > 0) {
						$options['lastSyncID_order'] = $tmp;
					}
				}
			}

			unset($data);

			$res = DB::query("SELECT * FROM `".PREFIX."order` WHERE `updata_date` > ".DB::quote($options['ordersUpdated']));
			while($row = DB::fetchAssoc($res)) {

				$data_order = self::createOrderArr($row, $options);
				if ($data_order === false) {continue;}

				if ($options['useOrderNumber'] == 'true') {
					$tmpNumber = $data_order['number'];
					unset($data_order['number']);
				}
				
				$tmpLastName = $data_order['lastName'];
				unset($data_order['lastName']);

				$url = $options['url'].'/api/v4/orders/'.$row['id'].'/edit';
				$data = array();
				$GLOBALS['rcrmQuery'] = $data_order;
				$data['order'] = json_encode($data_order);
				$data['site'] = $options['site'];
				$data['apiKey'] = $options['api'];

				$resp = self::request($data, $url, 'post');

				if ($resp['errorMsg'] == "Not found") {

					if ($options['useOrderNumber'] == 'true') {
						$data_order['number'] = $tmpNumber;
					}
					
					$data_order['lastName'] = $tmpLastName;
					$GLOBALS['rcrmQuery'] = $data_order;
					$data['order'] = json_encode($data_order);

					$url = $options['url'].'/api/v4/orders/create';
					$resp = self::request($data, $url, 'post');
				}
				if ($resp == false) {
					$errorOrders = true;
				}
				usleep(100000);
			}

			if (!$errorOrders) {
				$options['ordersUpdated'] = date('Y-m-d H:i:s');
			}
		}

		if ($options['syncRemains'] == 'true') {

			$data_remains = self::createRemainsArr($options);

			$url = $options['url'].'/api/'.$version.'/store/inventories/upload';

			$data_chunks = array_chunk($data_remains, 250);
			foreach ($data_chunks as $key => $value) {
				$data = array();
				$GLOBALS['rcrmQuery'] = $value;
				$data['offers'] = json_encode($value);
				$data['site'] = $options['site'];
				$data['apiKey'] = $options['api'];
				if (!self::request($data, $url, 'post')) {
					$errorRemains = true;
				}
				usleep(100000);
			}

			unset($data);

			$url = $options['url'].'/api/'.$version.'/store/inventories';
			$data = array();
			$data['filter']['sites'][] = $options['site'];
			if (MG::enabledStorage()) {
				$data['filter']['details'] = 1;
			}
			$data['apiKey'] = $options['api'];
			$data['limit'] = 250;

			$GLOBALS['rcrmQuery'] = $data;
			$result = self::request($data, $url, 'get');

			if (!$result) {
				$errorRemains = true;
			}

			$tmp = self::processRemains($result, $options);
			
			if ($result['pagination']['totalPageCount'] > 1) {
				for ($i=2; $i < $result['pagination']['totalPageCount'] + 1; $i++) { 
					$data['page'] = $i;
					$GLOBALS['rcrmQuery'] = $data;
					$res = self::request($data, $url, 'get');
					if (!$res) {
						$errorRemains = true;
					}
					usleep(100000);
					$tmp = self::processRemains($res, $options);
				}
			}

			if (!$errorRemains) {
				$options['remainsUpdated'] = date('Y-m-d H:i:s');
			}
		}

		MG::setOption(array('option' => 'retailcrm', 'value'  => addslashes(serialize($options)), 'active' => 'N'));
			
		if (!$errorUsers && !$errorOrders && !$errorRemains) {
			return true;
		}
		else{
			if ($options['reportSync'] == 'true') {
				$fileName = SITE.'/log_'.date('Y_m_d').'.txt';
				$msg = '';
				if ($errorUsers) {
					$msg .= 'Произошла ошибка при синхронизации <b>пользователей</b>, подробнее в <a target="_blank" href="'.$fileName.'">логе</a>.<br>';
				}
				if ($errorOrders) {
					$msg .= 'Произошла ошибка при синхронизации <b>заказов</b>, подробнее в <a target="_blank" href="'.$fileName.'">логе</a>.<br>';
				}
				if ($GLOBALS['rcrmOrderMismatch'] === true) {
					$msg .= 'Способы доставки и/или оплаты в retailCRM не соответствуют настройкам в Moguta.CMS.<br>Возможно у некоторых способов доставки в retailCRM не разрешены используемые способы оплаты.<br>';
				}
				if ($errorRemains) {
					$msg .= 'Произошла ошибка при синхронизации <b>остатков</b>, подробнее в <a target="_blank" href="'.$fileName.'">логе</a>.<br>';
				}
				$mails = explode(',', MG::getSetting('adminEmail'));
				foreach ($mails as $mail) {
					if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
						Mailer::sendMimeMail(array(
							'nameFrom' => MG::getSetting('shopName'),
							'emailFrom' => MG::getSetting('noReplyEmail'),
							'nameTo' => $mail,
							'emailTo' => $mail,
							'subject' => 'Произошла ошибка при синхронизации с retailCRM',
							'body' => $msg,
							'html' => true
						));
					}
				}
			}
			return false;
		}
	}
   /**
	* Отправляет curl запрос.
	* @param array $data данные для запроса
	* @param string $url ссылка для запроса
	* @param string $type тип запроса (get/post)
	* @return array|bool результат запроса или false если ошибка
	*/
	static function request($data, $url, $type){

		if ($type == 'get') {
			$url = $url.'?'.http_build_query($data, '', '&');
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		if ($type == 'post') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		
		$response = curl_exec($ch);
		// $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// $errno = curl_errno($ch);
		// $error = curl_error($ch);
		curl_close($ch);

		$response = json_decode($response, true);
// unset($GLOBALS['rcrmQuery']['site']);
// unset($GLOBALS['rcrmQuery']['apiKey']);
// MG::loger($GLOBALS['rcrmQuery']);
// MG::loger($response);

		if ($response['errorMsg'] == 'Order is not loaded' && (strpos($response['errors']['paymentType'], 'order contains several non completed payments') || strpos($response['errors']['paymentStatus'], 'order contains several non completed payments'))) {
			$tmp = $response['errors'];
			if (strpos($tmp['paymentType'], 'order contains several non completed payments')) {
				unset($tmp['paymentType']);
			}
			if (strpos($tmp['paymentStatus'], 'order contains several non completed payments')) {
				unset($tmp['paymentStatus']);
			}
			if (empty($tmp)) {
				$response['success'] = 1;
			}
		}

		if (!$response['success'] && $response['errorMsg'] != "Not found") {

			$resp = $response;
			foreach ($resp['errors'] as $key => $value) {
				if (strpos($value, 'already exists')) {
					unset($resp['errors'][$key]);
				}
				if (strpos($value, 'payment type is not supported for the type of deliver')) {
					$GLOBALS['rcrmOrderMismatch'] = true;
				}
			}
			if (!empty($resp['errors'])) {
				MG::loger('Ошибка при взаимодействии c RetailCRM');
				unset($GLOBALS['rcrmQuery']['site']);
				unset($GLOBALS['rcrmQuery']['apiKey']);
				MG::loger($GLOBALS['rcrmQuery']);
				MG::loger($resp);
				return false;
			}
		}
		return $response;
	}
   /**
	* Обработка входящего массива пользователей при синхронизации.
	* @param array $result результат запроса
	* @param array $options настройки
	* @return int последний ID синхронизации
	*/
	static function processUsers($result, $options){
		@set_time_limit(30);
		$lastSyncID = false;
		foreach ($result['history'] as $key => $value) {
			$lastSyncID = $value['id'];
			if ($value['customer']['site'] == $options['site']) {
				$id = $value['customer']['externalId'];
				$dbField = '';
				switch ($value['field']) {
					case 'first_name':
						$dbField = 'name';
						break;
					case 'last_name':
						$dbField = 'sname';
						break;
					case 'email':
						$dbField = 'email';
						break;
					case 'phones':
						$dbField = 'phone';
						break;
					case 'address.text':
						$dbField = 'address';
						break;
					case 'birthday':
						$dbField = 'birthday';
						break;
					case 'contragent.legal_name':
						$dbField = 'nameyur';
						break;
					case 'contragent.legal_address':
						$dbField = 'adress';
						break;
					case 'contragent.i_n_n':
						$dbField = 'inn';
						break;
					case 'contragent.k_p_p':
						$dbField = 'kpp';
						break;
					case 'contragent.b_i_k':
						$dbField = 'bik';
						break;
					case 'contragent.bank':
						$dbField = 'bank';
						break;
					case 'contragent.corr_account':
						$dbField = 'ks';
						break;
					case 'contragent.bank_account':
						$dbField = 'rs';
						break;
					case 'contragent.contragent_type':
						if ($value['newValue'] == 'individual') {
							DB::query("UPDATE `".PREFIX."user` SET `nameyur` = NULL, `adress` = NULL, `adress` = NULL, `inn` = NULL, `kpp` = NULL, `bik` = NULL, `bank` = NULL, `ks` = NULL, `rs` = NULL WHERE id = ".DB::quoteInt($id));
						}
						break;
					
					default:
						# code...
						break;
				}
				if (strlen($dbField) > 1) {
					$res = DB::query("select `".$dbField."` from `".PREFIX."user` where `id` = ".DB::quoteInt($id));
					$row = DB::fetchAssoc($res);
					if ($row[$dbField] != $value['newValue']) {
						DB::query("UPDATE `".PREFIX."user` SET `".$dbField."` = ".DB::quote($value['newValue'])." WHERE id = ".DB::quoteInt($id));
					}
				}
			}
		}
		return $lastSyncID;
	}
   /**
	* Обработка входящего массива заказов при синхронизации.
	* @param array $result результат запроса
	* @param array $options настройки
	* @return int последний ID синхронизации
	*/
	static function processOrders($result, $options){
		@set_time_limit(30);
		$lastSyncID = false;
		$ordersToUpdate = array();
		foreach ($result['history'] as $key => $value) {
			$lastSyncID = $value['id'];
			if ($value['order']['site'] == $options['site']) {
				$id = $value['order']['externalId'];
				$dbField = '';
				$dbValue = '';
				switch ($value['field']) {
					case 'contragent.b_i_k':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'bik', $value['newValue']);
						break;
					case 'contragent.bank':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'bank', $value['newValue']);
						break;
					case 'contragent.bank_account':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'rs', $value['newValue']);
						break;
					case 'contragent.corr_account':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'ks', $value['newValue']);
						break;
					case 'contragent.i_n_n':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'inn', $value['newValue']);
						break;
					case 'contragent.k_p_p':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'kpp', $value['newValue']);
						break;
					case 'contragent.legal_address':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'adress', $value['newValue']);
						break;
					case 'contragent.legal_name':
						$dbField = 'yur_info';
						$dbValue = self::updateOrderYur($id, 'nameyur', $value['newValue']);
						break;
					case 'status':
						$dbField = 'status_id';
						$dbValue = array_search($value['newValue']['code'], $options['retailStatuses']);
						break;
					case 'delivery_type':
						$dbField = 'delivery_id';
						$dbValue = array_search($value['newValue']['code'], $options['retailDeliverys']);
						break;
					case 'delivery_date':
						$dbField = 'date_delivery';
						$dbValue = date("d.m.Y", strtotime($value['newValue']));
						break;
					case 'delivery_cost':
						$dbField = 'delivery_cost';
						$dbValue = (float)$value['newValue'];
						break;
					case 'email':
						$dbField = 'user_email';
						$dbValue = $value['newValue'];
						break;
					case 'payments.status':
						if ($options['paid'] == $value['newValue']['code']) {
							$dbField = 'paided';
							$dbValue = '1';
						}
						if ($options['notPaid'] == $value['newValue']['code']) {
							$dbField = 'paided';
							$dbValue = '0';
						}
						break;
					case 'first_name':
						$dbField = 'name_buyer';
						$dbValue = $value['newValue'];
						break;
					case 'phone':
						$dbField = 'phone';
						$dbValue = $value['newValue'];
						break;
					case 'order_product.quantity':
						$dbField = 'order_content';
						$dbValue = self::updateOrderProduct($id, 'edit_quantity', $value['newValue'], $value['item']['offer']['externalId']);
						self::editRemains($value['item']['offer']['externalId'], ($value['oldValue']-$value['newValue']));
						$ordersToUpdate[] = $id;
						break;
					case 'order_product.summ':
						$dbField = 'order_content';
						$dbValue = self::updateOrderProduct($id, 'edit_price', $value['newValue'], $value['item']['offer']['externalId']);
						$ordersToUpdate[] = $id;
						break;
					case 'order_product':
						$dbField = 'order_content';
						if (!array_key_exists('id', $value['newValue']) && array_key_exists('id', $value['oldValue'])) {
							$dbValue = self::updateOrderProduct($id, 'remove_product', $value['item'], $value['item']['offer']['externalId']);
						}
						else{
							$dbValue = self::updateOrderProduct($id, 'add_product', $value['item'], $value['item']['offer']['externalId']);
						}
						$ordersToUpdate[] = $id;
						break;
					
					default:
						# code...
						break;
				}
				if (strpos($value['field'], 'custom_') === 0) {
					$opFieldz = unserialize(stripslashes(MG::getSetting('optionalFields')));
					$checkboxes = array();
					foreach ($opFieldz as $v) {
						if ($v['type'] == 'checkbox' || $v['type'] == 'radiobutton' || $v['type'] == 'select') {
							$checkboxes[] = MG::translitIt(trim($v['name']));
						}
					}
					$value['field'] = str_replace('custom_', '', $value['field']);
					$value['field'] = self::getArrghKey($options['retailOpFields'], $value['field']);
					if ($value['field']) {
						if (in_array($value['field'], $checkboxes)) {
							$res = DB::query("SELECT `id` FROM `".PREFIX."custom_order_fields` 
								WHERE `id_order` = ".DB::quoteInt($id)." AND `field` = ".DB::quote($value['field']));
							if ($row = DB::fetchAssoc($res)) {
								DB::query("UPDATE `".PREFIX."custom_order_fields` SET `value` = ".DB::quote(htmlspecialchars($value['newValue']))." 
									WHERE `id_order` = ".DB::quoteInt($id)." AND `field` = ".DB::quote($value['field']));
							}
							else{
								DB::query("INSERT INTO `".PREFIX."custom_order_fields` VALUES (NULL, ".DB::quote($value['field']).", ".DB::quoteInt($id).", ".DB::quote(htmlspecialchars($value['newValue'])).");");
							}
						}
						else{
							DB::query("UPDATE `".PREFIX."custom_order_fields` SET `value` = ".DB::quote(htmlspecialchars($value['newValue']))." 
								WHERE `id_order` = ".DB::quoteInt($id)." AND `field` = ".DB::quote($value['field']));
						}
					}
				}
				if (strlen($dbField) > 0 && strlen($dbValue) > 0 && $dbValue !== false) {
					$res = DB::query("select `".$dbField."` from `".PREFIX."order` where `id` = ".DB::quoteInt($id));
					$row = DB::fetchAssoc($res);
					if ($row[$dbField] != $dbValue) {
						DB::query("UPDATE `".PREFIX."order` SET `".$dbField."` = ".DB::quote($dbValue)." WHERE id = ".DB::quoteInt($id));
					}
				}
			}
		}

		$ordersToUpdate = array_unique($ordersToUpdate);
		foreach ($ordersToUpdate as $key => $value) {
			$res = DB::query("select `order_content` from `".PREFIX."order` where `id` = ".DB::quoteInt($value));
			$row = DB::fetchAssoc($res);
			$content = unserialize(stripslashes($row['order_content']));
			$newPrice = 0;
			foreach ($content as $key2 => $item) {
				$newPrice = $newPrice + ($item['price']*$item['count']);
			}
			DB::query("UPDATE `".PREFIX."order` SET `summ` = ".DB::quote($newPrice,1)." WHERE `id` = ".DB::quoteInt($value));
		}

		return $lastSyncID;
	}
   /**
	* Обработка входящего массива остатков при синхронизации.
	* @param array $result результат запроса
	* @param array $options настройки
	* @return int последний ID синхронизации
	*/
	static function processRemains($result, $options){
		@set_time_limit(30);
		$storageState = MG::enabledStorage();

		foreach ($result['offers'] as $key => $value) {
			$res = DB::query("SELECT `id`, `count` FROM `".PREFIX."product` WHERE `code` = ".DB::quote($value['externalId']));
			if ($row = DB::fetchAssoc($res)) {
				if ($storageState) {
					foreach ($value['stores'] as $k => $v) {   
						$storage = self::getArrghKey($options['retailStorage'], $v['store']);

						if ($storage) {
							$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 AND `storage` = ".DB::quote($storage));
							if ($ro = DB::fetchAssoc($rez)) {
								DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = ".DB::quote($v['quantity'])." WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 AND `storage` = ".DB::quote($storage));
							}
							else{
								DB::query("INSERT INTO `".PREFIX."product_on_storage` (`storage`, `count`, `product_id`, `variant_id`) 
									VALUES (".DB::quote($storage).", ".DB::quote($v['quantity']).", ".$row['id'].", 0)");
							}
						}
						
					}
					DB::query("UPDATE `".PREFIX."product` SET last_updated = '".date('Y-m-d H:i:s')."' WHERE id = ".DB::quote($item['id']));
				}
				else{
					if ($value['quantity'] != $row['count']) {
						DB::query("UPDATE `".PREFIX."product` SET `count` = ".DB::quoteInt($value['quantity'])." WHERE `code` = ".DB::quote($value['externalId']));
					}
				}
				
			}
			else{
				$res = DB::query("SELECT `id`, `product_id`, `count` FROM `".PREFIX."product_variant` WHERE `code` = ".DB::quote($value['externalId']));
				if ($row = DB::fetchAssoc($res)) {
					if ($storageState) {
						foreach ($value['stores'] as $k => $v) {   
							$storage = self::getArrghKey($options['retailStorage'], $v['store']);

							if ($storage) {
								$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".DB::quote($row['id'])." AND `storage` = ".DB::quote($storage));
								if ($ro = DB::fetchAssoc($rez)) {
									DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = ".DB::quote($v['quantity'])." WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".DB::quote($row['id'])." AND `storage` = ".DB::quote($storage));
								}
								else{
									DB::query("INSERT INTO `".PREFIX."product_on_storage` (`storage`, `count`, `product_id`, `variant_id`) 
									VALUES (".DB::quote($storage).", ".DB::quote($v['quantity']).", ".$row['product_id'].", ".DB::quote($row['id']).")");
								}
							}
						}
						DB::query("UPDATE `".PREFIX."product_variant` SET last_updated = '".date('Y-m-d H:i:s')."' WHERE id = ".DB::quote($row['id'])." AND product_id = ".DB::quote($row['product_id']));
					}
					else{
						if ($value['quantity'] != $row['count']) {
							DB::query("UPDATE `".PREFIX."product_variant` SET `count` = ".DB::quoteInt($value['quantity'])." WHERE `code` = ".DB::quote($value['externalId']));
						}
					}
				}
			}
		}
	}
   /**
	* Обновление юридических данных в заказе.
	* @param string $id ID заказа
	* @param string $field поле
	* @param string $val значение поля
	* @return string сериализованный массив юридических данных в заказе
	*/
	static function updateOrderYur($id, $field, $val){
		$res = DB::query("SELECT `yur_info` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($id));
		$row = DB::fetchAssoc($res);
		if (strlen($row['yur_info']) > 1) {
			$yurArr = unserialize(stripslashes($row['yur_info']));
			$yurArr[$field] = $val;
			$yurArr = array_filter($yurArr);
			return addslashes(serialize($yurArr));
		}
		else{
			$yurArr = array();
			if (strlen($val) > 0) {
				$yurArr[$field] = $val;
				$yurArr = array_filter($yurArr);
				return addslashes(serialize($yurArr));
			}
		}
		return false;
	}
   /**
	* Обновление остатков товара.
	* @param string $code артикул товара
	* @param string $number количество товара
	*/
	static function editRemains($code, $number){
		@set_time_limit(30);
		$number = (int)$number;
		$num = $number;

		if ($number === 0) {
			return false;
		}

		if ($number > 0) {
			$number = '+'.$number;
		}

		if (MG::enabledStorage()) {

			$res = DB::query("SELECT `id` FROM `".PREFIX."product` WHERE `code` = ".DB::quote($code));
			if ($row = DB::fetchAssoc($res)) {
				
				if ($num < 0) {
					$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 AND count >= ".abs($num)." ORDER BY RAND() LIMIT 0,1");
					if ($ro = DB::fetchAssoc($rez)) {
						DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = `count` ".$number." WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 AND `id` = ".$ro['id']);
					}
				}
				else{
					$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 ORDER BY RAND() LIMIT 0,1");
					if ($ro = DB::fetchAssoc($rez)) {
						DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = `count` ".$number." WHERE `product_id` = ".$row['id']." AND `variant_id` = 0 AND `id` = ".$ro['id']);
					}
				}	
			}

			$res = DB::query("SELECT `id`, `product_id` FROM `".PREFIX."product_variant` WHERE `code` = ".DB::quote($code));
			if ($row = DB::fetchAssoc($res)) {
				if ($num < 0) {
					$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".$row['id']." AND count >= ".abs($num)." ORDER BY RAND() LIMIT 0,1");
					if ($ro = DB::fetchAssoc($rez)) {

						DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = `count` ".$number." WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".$row['id']." AND `id` = ".$ro['id']);
					}
				}
				else{

					$rez = DB::query("SELECT `id` FROM `".PREFIX."product_on_storage` WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".$row['id']." ORDER BY RAND() LIMIT 0,1");
					if ($ro = DB::fetchAssoc($rez)) {

						DB::query("UPDATE `".PREFIX."product_on_storage` SET `count` = `count` ".$number." WHERE `product_id` = ".$row['product_id']." AND `variant_id` = ".$row['id']." AND `id` = ".$ro['id']);
					}
				}	
			}
		}
		else{
			DB::query("UPDATE `".PREFIX."product` SET `count` = `count` ".$number." WHERE `code` = ".DB::quote($code));
			DB::query("UPDATE `".PREFIX."product_variant` SET `count` = `count` ".$number." WHERE `code` = ".DB::quote($code));
		}
	}
   /**
	* Обновление состава заказа.
	* @param string $id ID заказа
	* @param string $type тип действия
	* @param string $val значение цены или количества товара
	* @param string $code артикул товара
	* @return string сериализованный массив состава заказа
	*/
	static function updateOrderProduct($id, $type, $val, $code){
		@set_time_limit(30);
		$res = DB::query("SELECT `order_content` FROM `".PREFIX."order` WHERE `id` = ".DB::quoteInt($id));
		$row = DB::fetchAssoc($res);
		$errorRow = $row['order_content'];
		$content = unserialize(stripslashes($row['order_content']));

		switch ($type) {
			case 'edit_quantity':
				$key = self::getArrKey($content, 'code', $code);
				if ($key !== false) {
					$content[$key]['count'] = $val;
					return addslashes(serialize($content));
				}
				else{
					return false;
				}
			case 'edit_price':
				$key = self::getArrKey($content, 'code', $code);
				if ($key !== false) {
					$content[$key]['price'] = (float)round(($val/$content[$key]['count']), 2);
					return addslashes(serialize($content));
				}
				else{
					return false;
				}
			case 'remove_product':
				$key = self::getArrKey($content, 'code', $code);
				if ($key !== false) {
					self::editRemains($code, (0+$val['quantity']));
					unset($content[$key]);
					$content = array_values($content);
					return addslashes(serialize($content));
				}
				else{
					return false;
				}
			case 'add_product':
				$newProduct = array();
				// $newProduct['name'] = $val['offer']['name'];
				// $newProduct['title'] = $val['offer']['name'];
				$newProduct['code'] = $code;
				if ((float)$val['discountTotal'] != 0) {
					$newProduct['price'] = (float)$val['initialPrice'] - (float)$val['discountTotal'];
				}
				else{
					$newProduct['price'] = $val['initialPrice'];
				}
				$newProduct['count'] = $val['quantity'];
				$newProduct['property'] = '';
				$newProduct['info'] = '';
				$newProduct['coupon'] = 0;
				$newProduct['discount'] = 0;
				$newProduct['discSyst'] = 'false/false';

				$res = DB::query("SELECT * FROM `".PREFIX."product_variant` WHERE `code` = ".DB::quote($code));
				if ($row = DB::fetchAssoc($res)) {
					$newProduct['id'] = $row['product_id'];
					$newProduct['variant'] = $row['id'];
					$newProduct['weight'] = $row['weight'];
					$newProduct['fulPrice'] = $row['price'];
					$varTitle = $row['title_variant'];

					$res = DB::query('
					SELECT  CONCAT(c.parent_url,c.url) as category_url, c.title as category_name,
					p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
					p.`currency_iso` 
					FROM `'.PREFIX.'product` p
					LEFT JOIN `'.PREFIX.'category` c
					ON c.id = p.cat_id
					WHERE p.id = '.DB::quoteInt($row['product_id'],1));

					if ($row = DB::fetchAssoc($res)) {
						$newProduct['url'] = $row['category_url'].'/'.$row['url'];
						$newProduct['name'] = $row['title'].' '.$varTitle;
						$newProduct['title'] = $row['title'].' '.$varTitle;

						$content[] = $newProduct;
						self::editRemains($code, (0-$val['quantity']));
						return addslashes(serialize($content));
					}
					else{
						return false;
					}
				}
				else{
					$res = DB::query('
					SELECT  CONCAT(c.parent_url,c.url) as category_url, c.title as category_name,
					p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
					p.`currency_iso` 
					FROM `'.PREFIX.'product` p
					LEFT JOIN `'.PREFIX.'category` c
					ON c.id = p.cat_id
					WHERE p.code = '.DB::quote($code));

					if ($row = DB::fetchAssoc($res)) {
						$newProduct['id'] = $row['id'];
						$newProduct['variant'] = '';
						$newProduct['weight'] = $row['weight'];
						$newProduct['fulPrice'] = $row['price_course'];
						$newProduct['url'] = $row['category_url'].'/'.$row['url'];
						$newProduct['name'] = $row['title'];
						$newProduct['title'] = $row['title'];

						$content[] = $newProduct;
						self::editRemains($code, (0-$val['quantity']));
						return addslashes(serialize($content));
					}
					else{
						return false;
					}
				}	
				
				break;
			
			default:
				return false;
				break;
		}
	}
   /**
	* Получение ключа массива по значению определенного поля.
	* @param array $arr массив для поиска
	* @param string $field поле
	* @param string $value значение поля
	* @return string|bool ключ массива или false если не найден
	*/
	static function getArrKey($arr, $field, $value){
		foreach($arr as $key => $element){
			if ($element[$field] === $value){
				return $key;
			}
		}
		return false;
	}
   /**
	* Получение ключа массива по значению.
	* @param array $arr массив для поиска
	* @param string $value значение поля
	* @return string|bool ключ массива или false если не найден
	*/
	static function getArrghKey($arr, $value){
		foreach($arr as $key => $element){
			if ($element === $value){
				return $key;
			}
		}
		return false;
	}
   /**
	* Создание массива данных пользователя для экспорта.
	* @param array $row массив данных из базы
	* @param array $options настройки
	* @return array подготовленный для экспорта массив данных пользователя
	*/
	static function createUserArr($row, $options){
		@set_time_limit(30);
		if (strlen($row['name'])<2) {
			switch ($row['role']) {
				case '1':
					$row['name'] = 'Администратор-'.$row['id'];
					break;
				
				case '3':
					$row['name'] = 'Менеджер-'.$row['id'];
					break;
				
				case '4':
					$row['name'] = 'Модератор-'.$row['id'];
					break;
				
				default:
					$row['name'] = 'Покупатель-'.$row['id'];
					break;
			}
		}
		if (strlen($row['phone'])<2) {
			$row['phone'] = '+7 (000) 000-00-00';
		}
		$row['address'] = str_replace(array("\n", "\r"), ' ', $row['address']);
		$row['address'] = preg_replace("/[[:blank:]]+/"," ",$row['address']);
		$row['address'] = mb_substr($row['address'], 0, 47, 'utf-8');
		$data_customer = array(
			'externalId' => $row['id'],
			'firstName' => $row['name'],
			'lastName' => $row['sname'],
			'email' => $row['email'],
			'phones' => array(
				array(
					'number' => $row['phone']
				)
			),
			'address' => array(
				'text' => $row['address']
			)
		);
		
		if (strlen($row['birthday'])>0 && $row['birthday'] != '0000-00-00') {
			$data_customer['birthday'] = date("Y-m-d", strtotime($row['birthday']));
		}
		if (strlen($row['date_add'])>0) {
			$data_customer['createdAt'] = date("Y-m-d H:i:s", strtotime($row['date_add']));
		}
		if (strlen($row['inn'])>0 || strlen($row['kpp'])>0 || strlen($row['nameyur'])>0 || strlen($row['adress'])>0 || strlen($row['bank'])>0 || strlen($row['bik'])>0 || strlen($row['ks'])>0 || strlen($row['rs'])>0) {

			$data_customer['contragent'] = array(
				'contragentType' => 'legal-entity',
				'legalName' => $row['nameyur'],
				'legalAddress' => $row['adress'],
				'INN' => $row['inn'],
				'KPP' => $row['kpp'],
				'BIK' => $row['bik'],
				'bank' => $row['bank'],
				'corrAccount' => $row['ks'],
				'bankAccount' => $row['rs']);
		}

		return array_filter($data_customer);
	}
   /**
	* Создание массива данных заказа для экспорта.
	* @param array $row массив данных из базы
	* @param array $options настройки
	* @return array подготовленный для экспорта массив данных заказа
	*/
	static function createOrderArr($row, $options){
		@set_time_limit(30);

		if (!$options['retailPayments'][$row['payment_id']] || !$options['retailDeliverys'][$row['delivery_id']]) {
			return false;
		}

		$data_order = array(
			'status' => $options['retailStatuses'][$row['status_id']],
			'externalId' => $row['id'],
			'firstName' => $row['name_buyer'],
			'phone' => $row['phone'],
			'customerComment' => $row['user_comment'],
			'managerComment' => $row['comment'],
			'delivery' => array(
				'code' => $options['retailDeliverys'][$row['delivery_id']],
				'cost' => $row['delivery_cost']
				),
			'paymentType' => $options['retailPayments'][$row['payment_id']]
		);
		if ($options['useOrderNumber'] == 'true') {
			$data_order['number'] = $row['number'];
		}
		if(filter_var($row['user_email'], FILTER_VALIDATE_EMAIL)) {$data_order['email'] = $row['user_email'];}
		$data_order['delivery']['address']['text'] = $row['address'];
		if ($row['paided'] == 1) {
			$data_order['payments'][] = array(
				'externalId' => 'p'.$row['id'], 
				'status' => $options['paid'], 
				'type' => $options['retailPayments'][$row['payment_id']], 
				'amount' => ($row['delivery_cost']+$row['summ']));
			$data_order['paymentStatus'] = $options['paid'];
		}
		else{
			$data_order['payments'][] = array(
				'externalId' => 'p'.$row['id'], 
				'status' => $options['notPaid'], 
				'type' => $options['retailPayments'][$row['payment_id']], 
				'amount' => ($row['delivery_cost']+$row['summ']));
			$data_order['paymentStatus'] = $options['notPaid'];
		}

		if (strlen($row['add_date'])>0) {
			$data_order['createdAt'] = date("Y-m-d H:i:s", strtotime($row['add_date']));
		}
		if (strlen($row['date_delivery'])>0) {
			$data_order['delivery']['date'] = date("Y-m-d", strtotime($row['date_delivery']));
		}

		$row['yur_info'] = unserialize(stripslashes($row['yur_info']));

		if (strlen($row['yur_info']['nameyur']) > 0 || strlen($row['yur_info']['adress']) > 0 || strlen($row['yur_info']['inn']) > 0 || strlen($row['yur_info']['kpp']) > 0 || strlen($row['yur_info']['bank']) > 0 || strlen($row['yur_info']['bik']) > 0 || strlen($row['yur_info']['ks']) > 0 || strlen($row['yur_info']['rs']) > 0) {
			$data_order['orderType'] = $options['retailLegal'];
		}
		else{
			$data_order['orderType'] = $options['retailIndividual'];
		}

		if (strlen($row['user_email']) > 1) {
			$dbres = DB::query("SELECT * FROM `".PREFIX."user` WHERE `email` = ".DB::quote($row['user_email']));
			if ($dbrow = DB::fetchAssoc($dbres)) {
				$data_order['lastName'] = $dbrow['sname'];
				$data_order['customer']['externalId'] = $dbrow['id'];

				if (strlen($row['yur_info']['nameyur']) < 1) {
					$row['yur_info']['nameyur'] = $dbrow['nameyur'];
				}
				if (strlen($row['yur_info']['adress']) < 1) {
					$row['yur_info']['adress'] = $dbrow['adress'];
				}
				if (strlen($row['yur_info']['inn']) < 1) {
					$row['yur_info']['inn'] = $dbrow['inn'];
				}
				if (strlen($row['yur_info']['kpp']) < 1) {
					$row['yur_info']['kpp'] = $dbrow['kpp'];
				}
				if (strlen($row['yur_info']['bank']) < 1) {
					$row['yur_info']['bank'] = $dbrow['bank'];
				}
				if (strlen($row['yur_info']['bik']) < 1) {
					$row['yur_info']['bik'] = $dbrow['bik'];
				}
				if (strlen($row['yur_info']['ks']) < 1) {
					$row['yur_info']['ks'] = $dbrow['ks'];
				}
				if (strlen($row['yur_info']['rs']) < 1) {
					$row['yur_info']['rs'] = $dbrow['rs'];
				}
			}
		}
		if (strlen($row['yur_info']['inn'])>0 || strlen($row['yur_info']['kpp'])>0 || strlen($row['yur_info']['nameyur'])>0 || strlen($row['yur_info']['adress'])>0 || strlen($row['yur_info']['bank'])>0 || strlen($row['yur_info']['bik'])>0 || strlen($row['yur_info']['ks'])>0 || strlen($row['yur_info']['rs'])>0) {

			$data_order['contragent'] = array(
				'contragentType' => 'legal-entity',
				'legalName' => $row['yur_info']['nameyur'],
				'legalAddress' => $row['yur_info']['adress'],
				'INN' => $row['yur_info']['inn'],
				'KPP' => $row['yur_info']['kpp'],
				'BIK' => $row['yur_info']['bik'],
				'bank' => $row['yur_info']['bank'],
				'corrAccount' => $row['yur_info']['ks'],
				'bankAccount' => $row['yur_info']['rs']);
		}

		$content = unserialize(stripslashes($row['order_content']));

		foreach ($content as $key => $value) {

			$name = explode(PHP_EOL, $value['name']);
			$name = MG::textMore($name[0], 125);

			$item = array();
			$item['initialPrice'] = $value['fulPrice'];
			$discountTMP = (float)$value['fulPrice'] - (float)$value['price'];
			if ($discountTMP < 0) {$discountTMP = 0;}

			$item['discount'] = $discountTMP;
			$item['discountManualAmount'] = $discountTMP;
			$item['discountPercent'] = 0;
			$item['discountManualPercent'] = 0;
			$item['quantity'] = $value['count'];
			$item['offer'] = array('externalId' => $value['code']);
			$item['productName'] = $name;
			if (strlen($value['property']) > 0) {
				$tmp = $value['property'];
				$tmp = htmlspecialchars_decode($tmp);
				$tmp = html_entity_decode($tmp);

				$pieces = preg_split('{</div>}', $tmp);

				foreach ($pieces as $key => $value) {
					if (strlen($value) > 1) {
						$prop = array();
						$parts = preg_split('{<span class="prop-val">}', $value); 
						$parts[0] = iconv('UTF-8','ASCII//TRANSLIT',$parts[0]); 
						$parts[1] = iconv('UTF-8','ASCII//TRANSLIT',$parts[1]); 
						$prop['name'] = trim(strip_tags($parts[0])) ;
					 	$prop['value'] = trim(strip_tags($parts[1]));
					 	if (strlen($prop['value']) < 1) {
					 		$prop['value'] = '0';
					 	}
					 	$item['properties'][] = $prop;
					}
				}
			}
			$data_order['items'][] = $item;
		}

		if (!empty($options['retailOpFields'])) {
			$opFieldz = unserialize(stripslashes(MG::getSetting('optionalFields')));
			$checkboxes = array();
			foreach ($opFieldz as $value) {
				if ($value['type'] == 'checkbox') {
					$checkboxes[] = MG::translitIt(trim($value['name']));
				}
			}

			$res = DB::query("SELECT * FROM `".PREFIX."custom_order_fields` WHERE `id_order` = ".DB::quote($data_order['externalId'])." AND `field` IN (".DB::quoteIn(array_keys($options['retailOpFields'])).")");
			while ($row = DB::fetchAssoc($res)) {
				if (in_array($row['field'], $checkboxes)) {
					if (!$row['value'] || $row['value'] == 'false') {
						$data_order['customFields'][$options['retailOpFields'][$row['field']]] = 'false';
					}
					else{
						$data_order['customFields'][$options['retailOpFields'][$row['field']]] = 'true';
					}
				}
				else{
					$data_order['customFields'][$options['retailOpFields'][$row['field']]] = htmlspecialchars_decode($row['value']);
				}
			}
		}

// MG::loger(array_filter($data_order));
		return array_filter($data_order);
	}
   /**
	* Создание массива остатков товара для экспорта.
	* @param array $row массив данных из базы
	* @param array $options настройки
	* @return array подготовленный для экспорта массив остатков товара
	*/
	static function createRemainsArr($options){
		@set_time_limit(30);
		$result = array();
		$storageState = MG::enabledStorage();
		if ($storageState) {
			$storages = unserialize(stripslashes(MG::getSetting('storages')));
		}
		$res = DB::query("SELECT `id`, `count`, `code` FROM `".PREFIX."product` WHERE `last_updated` > ".DB::quote($options['remainsUpdated']));
		while ($row = DB::fetchAssoc($res)) {
			if ($row['count'] < 0 || $row['count'] > 999999) {
				$row['count'] = 999999;
			}
			$item = array();
			$item['externalId'] = $row['code'];
			if ($storageState) {
				foreach ($storages as $key => $value) {
					$tmp = MG::getProductCountOnStorage(0, $row['id'], 0, $value['id']);
					if ($tmp < 0 || $tmp > 999999) {
						$tmp = 999999;
					}
					$store = $options['retailStorage'][$value['id']];
					$item['stores'][] = array('code' => $store, 'available' => $tmp);
				}
			}
			else{
				$item['stores'][] = array('code' => $options['warehouseCode'], 'available' => $row['count']);
			}
			$result[] = $item;
		}

		$res = DB::query("SELECT `id`, `product_id`, `count`, `code` FROM `".PREFIX."product_variant` WHERE `last_updated` > ".DB::quote($options['remainsUpdated']));
		while ($row = DB::fetchAssoc($res)) {
			if ($row['count'] < 0 || $row['count'] > 999999) {
				$row['count'] = 999999;
			}
			$item = array();
			$item['externalId'] = $row['code'];
			if ($storageState) {
				foreach ($storages as $key => $value) {
					$tmp = MG::getProductCountOnStorage(0, $row['product_id'], $row['id'], $value['id']);
					if ($tmp < 0 || $tmp > 999999) {
						$tmp = 999999;
					}
					$store = $options['retailStorage'][$value['id']];
					$item['stores'][] = array('code' => $store, 'available' => $tmp);
				}
			}
			else{
				$item['stores'][] = array('code' => $options['warehouseCode'], 'available' => $row['count']);
			}
			$result[] = $item;
		}
// mg::loger($result);
		return $result;
	}
   /**
	* Создает выгрузку.
	* @return string результат выгрузки
	*/
	static function generateICML(){
		
		$ds = DIRECTORY_SEPARATOR;
		$options = unserialize(stripslashes(MG::getSetting('retailcrm')));
		// $currencies = MG::getSetting('currencyRate');
		// $nds = unserialize(stripslashes(MG::getOption('propertyOrder')));
		// $nds = $nds["nds"];

	 	$YML = '<?xml version="1.0" encoding="UTF-8"?>';

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->startElement('yml_catalog');
		$xml->writeAttribute('date', date('Y-m-d H:i:s'));
		$xml->startElement('shop');
		$xml->writeElement('name', MG::getSetting('sitename'));
		$xml->writeElement('company', MG::getSetting('sitename'));

		$xml->startElement('categories');////////////////////  start categories

		$catsForExport = array();
		$res = DB::query("
			SELECT id, parent, title
			FROM `".PREFIX."category` ORDER BY `sort`");
		while ($row = DB::fetchAssoc($res)) {
			array_push($catsForExport, $row['id']);
			$xml->startElement('category');
			$xml->writeAttribute('id', $row['id']);
			if (($row['parent'] > 0) || (in_array($row['parent'], $options['catsSelect']))) {
				$xml->writeAttribute('parentId', $row['parent']);
			}
			$xml->text($row['title']);
			$xml->endElement();
		}

		$storageState = MG::enabledStorage();

		$xml->endElement();////////////////////////////////////  end categories

		$xml->startElement("offers");///////////////////////////  start offers

		$model = new Models_Product;

		$res = DB::query("SELECT `id`, `code`, `count`, `activity` FROM `".PREFIX."product`");
		while ($row = DB::fetchAssoc($res)) {
			@set_time_limit(30);
			if ($row['activity'] == 0) {
				$disabled = true;
			}
			else{
				$disabled = false;
			}

			$product = $model->getProduct($row['id']);
			$variants = $model->getVariants($row['id']);

			if (empty($variants)) {

				$xml->startElement('offer');
				$xml->writeAttribute('id', $product['code']);

				$productId = $row['id'];
				$xml->writeAttribute('productId', $productId);

				$count = $row['count'];

				if ($storageState) {
					$count = MG::getProductCountOnStorage(0, $productId, 0, 'all');
				}

				if ($count < 0 || $count > 999999) {
					$count = 999999;
				}

				$xml->writeAttribute('quantity', $count);
			
				$variantz = array_values($variants);
				$xml->writeElement('name', MG::textMore($product['title'].' '.$variantz[0]['title_variant'], 250));
				
				$xml->writeElement('url', SITE.$ds.$product['category_url'].$ds.$product['url']);
				$xml->writeElement('price', $product['price']);
				// $xml->writeElement('currencyId', $product['currency_iso']);
				$xml->writeElement('categoryId', $product['cat_id']);

				$i=0;
				foreach ($product['images_product'] as $key => $value) {
					if ($i < 1) {
						$xml->writeElement('picture', SITE.$ds.'uploads'.$ds.$value);
					}
					$i++;
				}

				if (strlen($product['description']) > 0) {
					$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
					$product['description'] = strip_tags($product['description']);
					$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
					$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
					$product['description'] = strip_tags($product['description']);
					$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
					$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
					$product['description'] = mb_substr(trim($product['description']), 0,2975, 'utf-8');
					$xml->writeElement('description', '<![CDATA[ '.$product['description'].' ]]>');
				}

				if (strlen($product['weight']) > 0 && $product['weight'] > 0) {
					if ($product['weight'] < 0.001) {
						$xml->writeElement('weight', 0.001.' кг');
					}
					else{
						$xml->writeElement('weight', $product['weight'].' кг');
					}
				}

				if ($disabled) {
					$xml->writeElement('productActivity', 'N');
				}
				// if ($nds == 0 || $nds == 10 || $nds == 18) {
				// 	$xml->writeElement('vatRate', $nds);
				// }
				
				//  params
				foreach ($product['thisUserFields'] as $key => $value) {
					if ($value['type'] == 'string') {

						if (strlen($value['data'][0]['name']) > 0 && strlen($value['name']) > 0) {

							if ($row['count'] != 0 || $options['useNull'] == 'true') {
								$xml->startElement('param');
								$xml->writeAttribute('name', $value['name']);
								if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
								$xml->text($value['data'][0]['name']);
								$xml->endElement();
							}
						}
					}
				}
				$xml->startElement('param');
				$xml->writeAttribute('name', 'Артикул');
				$xml->writeAttribute('code', 'article');
				$xml->text($product['code']);
				$xml->endElement();

				$xml->endElement();//////////////////////////////////////////////////////////  end offer
			}
			// for ($i=1; $i < count($variants); $i++) {////////////////////////////////////  start variants
			foreach ($variants as $variant) {

				$xml->startElement('offer');/////////////////////////////////////////////  start offer

				$xml->writeAttribute('id', $variant['code']);

				$xml->writeAttribute('productId', $variant['product_id']);

				$count = $variant['count'];

				if ($storageState) {
					$count = MG::getProductCountOnStorage(0, $variant['product_id'], $variant['id'], 'all');
				}

				if ($count < 0 || $count > 999999) {
					$count = 999999;
				}

				$xml->writeAttribute('quantity', $count);

				$xml->writeElement('name', MG::textMore($product['title'].' '.$variant['title_variant'], 250));
				$xml->writeElement('url', SITE.$ds.$product['category_url'].$ds.$product['url']);
				$xml->writeElement('price', $variant['price']);
				// $xml->writeElement('currencyId', $variant['currency_iso']);
				$xml->writeElement('categoryId', $product['cat_id']);

				if (strlen($variant['image']) > 0) {
					$j=1;
					$folder = floor($row['id']/100)*100;
					if ($folder == 0) {$folder = '000';}
					$imgPath = SITE.$ds.'uploads'.$ds.'product'.$ds.$folder.$ds.$row['id'].$ds.$variant['image']; 
					$xml->writeElement('picture', $imgPath);
				}
				else{
					$j=0;
				}

				foreach ($product['images_product'] as $key => $value) {
					if ($j < 1) {
						$xml->writeElement('picture', SITE.$ds.'uploads'.$ds.$value);
					}
					$j++;
				}

				if (strlen($product['description']) > 0) {
					$xml->writeElement('description', '<![CDATA[ '.MG::textMore($product['description'], 2975).' ]]>');
					// $xml->writeElement('description', '<![CDATA[ '.MG::textMore(html_entity_decode(htmlspecialchars_decode($product['description'])), 2975).' ]]>');
				}

				if (strlen($product['weight']) > 0 && $product['weight'] > 0) {
					if ($product['weight'] < 0.001) {
						$xml->writeElement('weight', 0.001.' кг');
					}
					else{
						$xml->writeElement('weight', $product['weight'].' кг');
					}
				}

				if ($disabled) {
					$xml->writeElement('productActivity', 'N');
				}

				// if ($nds == 0 || $nds == 10 || $nds == 18) {
				// 	$xml->writeElement('vatRate', $nds);
				// }

				//  params
				foreach ($product['thisUserFields'] as $key => $value) {
					if (strlen($value['data'][0]['name']) > 0 && strlen($value['name']) > 0) {
						if ($value['type'] == 'string') {

							$xml->startElement('param');
							$xml->writeAttribute('name', $value['name']);
							if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
							$xml->text($value['data'][0]['name']);
							$xml->endElement();
						}
					}
				}

				// size+color
				if (strlen($variant['color']) > 0) {
								
					$resz = DB::query("SELECT `name` 
						FROM `".PREFIX."property_data` 
						WHERE id = ".DB::quote($variant['color']));

					if ($rowz = DB::fetchAssoc($resz)) {
						$xml->startElement('param');
						$xml->writeAttribute('name', 'Цвет');
						$xml->text($rowz['name']);
						$xml->endElement();
					}
				}
				if (strlen($variant['size']) > 0) {
					
					$resz = DB::query("SELECT `name` 
						FROM `".PREFIX."property_data` 
						WHERE id = ".DB::quote($variant['size']));

					if ($rowz = DB::fetchAssoc($resz)) {
						$xml->startElement('param');
						$xml->writeAttribute('name', 'Размер');
						$xml->text($rowz['name']);
						$xml->endElement();
					}
				}
						
				$xml->startElement('param');
				$xml->writeAttribute('name', 'Артикул');
				$xml->writeAttribute('code', 'article');
				// $xml->text($product['code']);
				$xml->text($variant['code']);
				$xml->endElement();

				$xml->endElement();/////////////////////////////////////////////  end offer
			}///////////////////////////////////////////////////////////////////  end variants
		}
		$xml->endElement();/////////////////////////////////////////////////////////  end offers
		$xml->endElement();/////////////////////////////////////////////////////////  end shop
		$xml->endElement();/////////////////////////////////////////////////////////  end yml_catalog
		$YML .= $xml->outputMemory();    
		$YML = html_entity_decode($YML);
		$YML = preg_replace('/&(?!#?[A-Za-z0-9]+;)/','&amp;', $YML);
		return $YML;
	}
}

?>