<?php

/**
 * Контроллер: Api
 *
 * Класс Controllers_Api позволяет сторонним сайтам и приложениям осуществлять взаимодействия с магазином;
 *	
 * <code>
 *	$api = new mogutaApi('адрес магазина', 'токен', 'секретный ключ');
 *	$testParam = array('111', '222', '333');
 * 	$res = $api->run('test', $testParam, true);
 * 	viewData($res);
 * </code>
 *
 * @package moguta.cms
 * @subpackage Controller
 */

class Controllers_Api extends BaseController {

	//================================================
	//			ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ API
	//================================================

	private static $status = 'OK'; 
	private static $error = '0'; 
	private static $sign = ''; 
	private static $key = ''; 
	private static $options = array(); 
	private static $workTime = null; 

	//================================================
	//			  РАЗРЕШЕННЫЕ ФУНКЦИИ
	//================================================

	private static $functionsArray = array(
		// - test
		'test', 
		// - пользователи
		'exportUsers',
		'importUsers',
		'deleteUser',
		// - категории
		'exportCategory',
		'importCategory',
		'deleteCategory',
		// - заказы
		'exportOrder',
		'importOrder',
		'deleteOrder',
		// - товары
		'exportProduct',
		'importProduct',
		'deleteProduct',
	);

	//================================================
	//				  СПИСОК ОШИБОК
	//================================================
	// 1 - неверный токен
	// 2 - ошибка вызова функции
	// 3 - API не настроен
	//================================================

	public function __construct() {
		self::$workTime = microtime(true);
		// загружаем настройки для API
		self::$options = unserialize(stripslashes(MG::getOption('API')));
		if(empty(self::$options)) {
			self::error(3);
		}

		// проверка ключа
		$valid = false;
		foreach (self::$options as $item) {
			if($item['token'] == $_POST['token']) {
				$valid = true;
				self::$key = $item['key'];
			}
		}
		if(!$valid) self::error(1);

		// вызов нужной функции
		$result = self::run();

		// генерируем подпись
		self::signGen();

		// выдаем ответ
		self::echoResult($result);
	}

	//================================================
	//			  ВНУТРЕННИЕ ФУНКЦИИ API
	//================================================

	/**
	 * Метод для формирования и отдачи ответа (звершает работу движка)
	 * @param int $data
	 * @return int $data
	 */
	private static function echoResult($data) {
		$result = array(
			'status' => self::$status,
			'response' => $data,
			'error' => self::$error,
			'sign' => self::$sign,
			'workTime' => round(microtime(true) - self::$workTime, 3).' ms'
		);

		echo json_encode($result);
		exit;
	}

	/**
	 * Eсли произошла ошибка, то запускаем эту функцию и передаем в нее код ошибки, дальше она сама.
	 * @param array $code
	 */
	private static function error($code) {
		self::$error = $code;
		self::$status = 'ERROR';
		self::echoResult();
	}

	/**
	 * Метод для генерации подписи, чтобы клиент был уверен в подлиннсоти ответа.
	 */
	private static function signGen() {
		self::$sign = md5($_POST['token'].$_POST['method'].str_replace('amp;', '', $_POST['param']).self::$key);
	}

	/**
	 * Метод для вызова методов класса.
	 * @return array
	 */
	private static function run() {
		if(in_array($_POST['method'], self::$functionsArray)) {
			$function = $_POST['method'];
			$param = json_decode(htmlspecialchars_decode($_POST['param']), true);
			$result = self::$function($param);
		} else {
			self::error(2);
		}
		return $result;
	}

	//================================================
	//		  ЗАПРАШИВАЕМЫЕ ФУНКЦИИ (внешние)
	//================================================

	/**
	 * Метод для проверки подключения к API магазина.
	 * <code>
	 * $param = array('test1' => '111', 'test2' => '222');
	 * $res = $api->run('test', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param массив с любыми параметрами для тестового подключенияы
	 * @return array
	 */
	public static function test($param = null) {
		return $param;
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ЮЗЕРАМИ
	//------------------------------------------------

	/**
	 * Метод для отправки пользователей
	 * <code>
	 * $param = array('page' => '1', 'count' => '15');
	 * $res = $api->run('exportUsers', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['page'] - страница для выгрузки
	 *					 $param['count'] - количество пользователей на страницу максимум 250
	 * @return array
	 */
	public static function exportUsers($param = null) {
		// ставим параметры по умолчанию, если нет входящих параметров
		if(empty($param['page'])) $param['page'] = 1;
		if(empty($param['count'])) $param['count'] = 250;
		// получаем количество пользователей
		$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'user');
		$tmp = DB::fetchAssoc($res);
		$result['countUsers'] = $tmp['count'];
		// определение того, что нужно выгрузить
		if($param['count'] > 250) $param['count'] = 250; 
		if($result['countUsers'] < $param['count']) {
			$limit = '';
		} else {
			$start = ($param['page'] - 1) * $param['count'];
			$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
		}
		// достаем список пользователей
		$res = DB::query("SELECT * FROM ".PREFIX."user".$limit);
		while($row = DB::fetchAssoc($res)) {
			$result['users'][] = $row;
		}
		$result['page'] = $param['page'];
		$result['count'] = $param['count'];
		return $result;
	}

	/**
	 * Метод для импорта пользователей.
	 * <code>
	 * $param['users'] = array(
	 * 	Array(
     *  	'id' => 1,	// id в базе
     *  	'email' => admin@admin.ru,	// email пользователя	
     *  	'role' => 1,	// группа пользователей
     *  	'name' => Администратор,	// имя пользователя (ФИО)
     *  	'sname' => ,	// фамилия (не используется почти)
     *  	'address' => ,	// адресс
     *  	'phone' => ,	// телефон
     *  	'date_add' => 2017-07-12 10:05:47,	// дата создания пользователя				
     *  	'blocked' => 0,	// блокировка доступа к личному кабинету 
     *  	'activity' => 1,	// статус
     *  	'inn' => ,	// ИНН
     *  	'kpp' => ,	// КПП
     *  	'nameyur' => ,	// Юр. лицо
     *  	'adress' => ,	// Юр. адрес
     *  	'bank' => ,	// Банк
     *  	'bik' => ,	// БИК
     *  	'ks' => ,	// К/Сч
     *  	'rs' => ,	// Р/Сч
     *  	'birthday' => ,	// день рождения пользователя
     *  )));
     * $param['enableUpdate'] = true;
	 * $res = $api->run('importUsers', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['users'] - входящий список пользователей для импорта (желательно до 100)
	 * 					 $param['enableUpdate'] - включить или выключить обновление пользователей
	 * @return string
	 */
	public static function importUsers($param = null) {
		foreach($param['users'] as $item) {
			// проверка полей по белому списку
			foreach ($item as $key => $value) {
				if(in_array($key, array('email','role','name','sname','phone','date_add','blocked','restore','activity','inn','kpp',
					'nameyur','adress','bank','bik','ks','rs','birthday','ip','pass'))) {
					$user[$key] = $value;
				}
			}
			// проверка наличия юзера
			$res = DB::query('SELECT id FROM '.PREFIX.'user WHERE email = '.DB::quote($user['email']));
			if($id = DB::fetchAssoc($res)) {
				if($param['enableUpdate'])
					DB::query('UPDATE '.PREFIX.'user SET '.DB::buildPartQuery($user).' WHERE id = '.DB::quoteInt($id['id']));
			} else {
				DB::query('INSERT INTO '.PREFIX.'user (email,role,name,sname,phone,date_add,blocked,restore,activity,inn,kpp,
					nameyur,adress,bank,bik,ks,rs,birthday,ip,pass) VALUES 
					('.DB::quote($user['email']).','.DB::quote($user['role']).','.DB::quote($user['name']).','.DB::quote($user['sname']).',
						'.DB::quote($user['phone']).','.DB::quote($user['date_add']).','.DB::quote($user['blocked']).','.DB::quote($user['restore']).',
							'.DB::quote($user['activity']).','.DB::quote($user['inn']).','.DB::quote($user['kpp']).',nameyur,'.DB::quote($user['adress']).',
								'.DB::quote($user['bank']).','.DB::quote($user['bik']).','.DB::quote($user['ks']).','.DB::quote($user['rs']).',
									'.DB::quote($user['birthday']).','.DB::quote($user['ip']).','.DB::quote($user['pass']).')');
			}
		}

		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления пользователей.
	 * <code>
	 * $param['email'] = array('user1@mail.ru', 'user2@email.ru', 'user3@mail.ru');
	 * $res = $api->run('deleteUser', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['email'] - список емэйлов пользователей, которых нужно удалить.
	 * @return string
	 */
	public static function deleteUser($param = null) {
		foreach ($param['email'] as $user) {
			$res = DB::query('SELECT id FROM '.PREFIX.'user WHERE email = '.DB::quote($user));
			$id = DB::fetchAssoc($res);
			USER::delete($id['id']);
		}
		
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С КАТЕГОРИЯМИ                      
	//------------------------------------------------

	/**
	 * Метод для отправки категорий.
	 * <code>
	 * $param['page'] = 1;
	 * $param['count'] = 15;
	 * $res = $api->run('exportCategory', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['page'] - страница для выгрузки
	 * 					 $param['count'] - количество категорий на страницу максимум 250
	 * @return array
	 */
	public static function exportCategory($param = null) {
		// ставим параметры по умолчанию, если нет входящих параметров
		if(empty($param['page'])) $param['page'] = 1;
		if(empty($param['count'])) $param['count'] = 250;
		// получаем количество категорий
		$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'category');
		$tmp = DB::fetchAssoc($res);
		$result['countCategory'] = $tmp['count'];
		// определение того, что нужно выгрузить
		if($param['count'] > 250) $param['count'] = 250; 
		if($result['countCategory'] < $param['count']) {
			$limit = '';
		} else {
			$start = ($param['page'] - 1) * $param['count'];
			$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
		}
		// достаем список категорий
		$res = DB::query("SELECT * FROM ".PREFIX."category".$limit);
		while($row = DB::fetchAssoc($res)) {
			$result['categories'][] = $row;
		}
		$result['page'] = $param['page'];
		$result['count'] = $param['count'];
		return $result;
	}

	/**
	 * Метод для импорта категорий.
	 * <code>
	 * $param['categories'] = Array(
     *     Array (												 
     *         'id' => 1,	// id категории
     *         'title' => 'Обезжелезивание реагентное',	// название категории
     *         'url' => 'obezjelezivanie-reagentnoe',	// url категории
     *         'parent' => 0,	// id родительской категории
     *         'parent_url' => ,	// родительский url (полная ссылка без сайта)
     *         'sort' => 1,	// параметр для сортировки
     *         'html_content' => ,	// описание категории
     *         'meta_title' => ,	// SEO заголовок
     *         'meta_keywords' => ,	// SEO ключевые слова
     *         'meta_desc' => ,	// SEO описание
     *         'invisible' => 0,	// скрыть категорию
     *         '1c_id' => ,	// идентификатор в 1с
     *         'image_url' => ,	// изображение категории
     *         'menu_icon' => ,	// иконка в меню
     *         'rate' => 0,	// наценка
     *         'unit' => 0,	// единица измерения товара
     *         'export' => 1,	// 
     *         'seo_content' =>,	// 
     *         'activity' => 1,	// активность
     *     )
     * );
	 * $res = $api->run('importCategory', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['categories'] - входящий список категорий для импорта (желательно до 100)
	 * @return string
	 */
	public static function importCategory($param = null) {
		foreach ($param['categories'] as $category) {
			// если нет id то пытаемся его найти
			if(empty($category['id'])) {
				$res = DB::query('SELECT id FROM '.PREFIX.'category WHERE url = '.DB::quote($category['url']));
				if($row = DB::fetchAssoc($res)) {
					$category['id'] = $row['id'];
				}
			}
			// если id все же нет, значит новая категория, создаем, иначе обновляем
			if(empty($category['id'])) {
				MG::get('category')->addCategory($category);
			} else {
				MG::get('category')->updateCategory($category);
			}
		}
		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления категорий.
	 * <code>
	 * $param['category'] = array('1', '2', '3', '4', '5');
	 * $res = $api->run('deleteCategory', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['category'] - массив с id категорий, которые нужно удалить
	 * @return string
	 */
	public static function deleteCategory($param = null) {
		foreach ($param['category'] as $item) {
			MG::get('category')->delCategory($item);
		}
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ЗАКАЗАМИ                      
	//------------------------------------------------

	/**
	 * Метод для отправки заказов.
	 * <code>
	 * $param['page'] = 1;
	 * $param['count'] = 25;
	 * $res = $api->run('exportOrder', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['page'] - страница для выгрузки
	 * 					 $param['count'] - количество заказов на страницу максимум 250
	 * @return array
	 */
	public static function exportOrder($param = null) {
		// ставим параметры по умолчанию, если нет входящих параметров
		if(empty($param['page'])) $param['page'] = 1;
		if(empty($param['count'])) $param['count'] = 250;
		// получаем количество заказов
		$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'order');
		$tmp = DB::fetchAssoc($res);
		$result['countOrder'] = $tmp['count'];
		// определение того, что нужно выгрузить
		if($param['count'] > 250) $param['count'] = 250; 
		if($result['countOrder'] < $param['count']) {
			$limit = '';
		} else {
			$start = ($param['page'] - 1) * $param['count'];
			$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
		}
		// достаем список заказов
		$res = DB::query("SELECT * FROM ".PREFIX."order".$limit);
		while($row = DB::fetchAssoc($res)) {
			$result['orders'][] = $row;
		}
		$result['page'] = $param['page'];
		$result['count'] = $param['count'];

		return $result;
	}

	/**
	 * Метод для импорта заказов.
	 * <code>
	 * $param['orders'] = Array (
     *     Array(
     *         'id' => 1,	// id заказа
     *         'updata_date' => '2017-08-18 13:07:29',	// время изменения заказа
     *         'add_date' => '2017-08-18 13:07:29',	// время добавления заказа
     *         'pay_date' => '2017-08-18 13:07:29',	// время оплаты заказа
     *         'close_date' => '2017-08-18 13:07:29',	// время завершения заказа
     *         'user_email' => 'hg@ds.aq',	// емэйл пользователя
     *         'phone' =>,	// телефон пользователя
     *         'address' =>,	// адресс доставки
     *         'summ' => 17519.00,	// сумма товаров заказа
     *         'order_content' => 'a:1:{i:0;a:16:{s:2:\"id\";s:3:\"256\";s:7:\"variant\";s:1:\"0\";s:5:\"title\";s:90:\"Кухонная мойка гранитная MARRBAXX Рики Z22 темно-серый\";s:4:\"name\";s:90:\"Кухонная мойка гранитная MARRBAXX Рики Z22 темно-серый\";s:8:\"property\";s:0:\"\";s:5:\"price\";s:4:\"1000\";s:8:\"fulPrice\";s:4:\"1000\";s:4:\"code\";s:5:\"CN256\";s:6:\"weight\";s:1:\"0\";s:12:\"currency_iso\";s:3:\"RUR\";s:5:\"count\";s:1:\"1\";s:6:\"coupon\";s:1:\"0\";s:4:\"info\";s:0:\"\";s:3:\"url\";s:71:\"kuhonnye-moyki/kuhonnaya-moyka-granitnaya-marrbaxx-riki-z22-temno-seryy\";s:8:\"discount\";s:1:\"0\";s:8:\"discSyst\";s:11:\"false/false\";}}',
     * // сожержание заказа в сериализированном виде
     *         'delivery_id' => 1,	// id способа доставки
     *         'delivery_cost' => 0,	// стоимость доставки
     *         'delivery_options' =>,	// дополнительная информация о способе доставке
     *         'payment_id' => 1,	// id способа оплаты
     *         'status_id' => 0,	// статус заказа
     *         'user_comment' => ,	// комментарий пользователя
     *         'comment' => ,	// комментарий менеджера
     *         'yur_info' => ,	// информация о юридическом лице
     *         'name_buyer' => ,	// ФИО покупателя
     *         'date_delivery' => ,	// дата доставки
     *         'ip' => '::1',	// ip с которого был оформлен заказ
     *         'number' => 'M-0105341895042',	// номер заказа
     *         '1c_last_export' => '2017-08-18 13:07:29',	// идентификатор в 1с
     *         'storage' => ,	// склад
     *         'summ_shop_curr' => 1000,	// сумма заказа в валюте магазина
     *         'delivery_shop_curr' => 0,	// стоимость доставки в валюте магазина
     *         'currency_iso' => 'RUR',	// код валюты
     *     )
	 * );
	 * $res = $api->run('importOrder', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['orders'] - входящий список заказов для импорта (желательно до 100)
	 * @return string
	 */
	public static function importOrder($param = null) {
		$model = new Models_Order;
		foreach ($param['orders'] as $order) {
			// если id все же нет, значит новая категория, создаем, иначе обновляем
			if(empty($order['id'])) {
				// расшифровка содержимого заказа
				$order['order_content'] = unserialize(stripcslashes($order['order_content']));
				foreach ($order['order_content'] as &$item) {
					$item['title'] = urldecode($item['name']);
				}
				$model->addOrder($order);
			} else {
				$model->updateOrder($order);
			}
		}

		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления заказов.
	 * <code>
	 * $param['category'] = array('1', '2', '3', '4', '5');
	 * $res = $api->run('deleteOrder', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['category'] - массив с id заказов, которые нужно удалить
	 * @return string
	 */
	public static function deleteOrder($param = null) {
		$model = new Models_Order;
		foreach ($param['orders'] as $item) {
			$model->deleteOrder($item);
		}
		return 'Удаление завершено';
	}

	//------------------------------------------------
	//		  	  ДЛЯ РАБОТЫ С ТОВАРАМИ                      
	//------------------------------------------------

	/**
	 * Метод для отправки товаров.
	 * <code>
	 * $param['page'] = 1;
	 * $param['count'] = 20;
	 * $param['variants'] = true;
	 * $param['property'] = true;
	 * $param['search'] = 1;
	 * $res = $api->run('exportProduct', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['page'] - страница для выгрузки
	 * 					 $param['count'] - количество заказов на страницу максимум 100
	 * 					 $param['variants'] - включает вывод вариантов
	 * 					 $param['property'] - включает вывод характеристик
	 * 					 $param['search'] - находит товар по id
	 * @return array
	 */
	public static function exportProduct($param = null) {
		// ставим параметры по умолчанию, если нет входящих параметров
		if(empty($param['page'])) $param['page'] = 1;
		if(empty($param['count'])) $param['count'] = 100;
		if(empty($param['variants'])) $param['variants'] = false;
		if(empty($param['property'])) $param['property'] = false;
		// получаем количество заказов
		$res = DB::query('SELECT count(id) AS count FROM '.PREFIX.'product');
		$tmp = DB::fetchAssoc($res);
		$result['countProduct'] = $tmp['count'];
		if($param['count'] > 100) $param['count'] = 100; 
		// определение того, что нужно выгрузить
		if(empty($param['search'])) {
			if($result['countProduct'] < $param['count']) {
				$limit = '';
			} else {
				$start = ($param['page'] - 1) * $param['count'];
				$limit = ' LIMIT '.DB::quote($start, true).','.DB::quote($param['count'], true);
			}
		} else {
			$limit = ' WHERE id = '.DB::quoteInt($param['search']);
		}
		// достаем список заказов
		$res = DB::query("SELECT * FROM ".PREFIX."product".$limit);
		while($row = DB::fetchAssoc($res)) {
			$result['products'][] = $row;
		}
		// загрузка вариантов
		if($param['variants']) {
			foreach ($result['products'] as $item) {
				$ids[] = $item['id'];
			}
			$prodIdIn = implode(',', $ids);
			unset($ids);
			$res = DB::query('SELECT * FROM '.PREFIX.'product_variant WHERE product_id IN ('.DB::quote($prodIdIn, true).')');
			while($row = DB::fetchAssoc($res)) {
				foreach ($result['products'] as $key => $value) {
					if($row['product_id'] == $value['id']) {
						$row['color_id'] = $row['color'];
						$row['size_id'] = $row['size'];
						unset($row['color']);
						unset($row['size']);
						$result['products'][$key]['variants'][] = $row;
					}
				}
			}
		}
		// загрузка списка характеристик
		if($param['property']) {
			// массив категорий из текщих выгруженных товаров
			$categories = array();
			foreach ($result['products'] as $item) {
				if(!in_array($item['cat_id'], $categories)) {
					$categories[] = $item['cat_id'];
				}
			}
			// загрузка характеристик (без значений)
			$catIdIn = implode(',', $categories);
			$property = null;
			$res = DB::query('
				SELECT p.*, (SELECT GROUP_CONCAT(distinct category_id) 
					FROM '.PREFIX.'category_user_property 
						WHERE property_id = p.id AND category_id IN ('.DB::quote($catIdIn, true).')) AS cat_id 
				FROM '.PREFIX.'property AS p
					RIGHT JOIN '.PREFIX.'category_user_property AS cup
						ON p.id = cup.property_id
					WHERE cup.category_id IN ('.DB::quote($catIdIn, true).') GROUP BY p.id');
			while($row = DB::fetchAssoc($res)) {
				$property[] = $row;
			}
			// прикрепляем характеристики к товарам
			foreach ($result['products'] as $key => $value) {
				foreach ($property as $prop) {
					if(substr_count(','.$prop['cat_id'].',', ','.$value['cat_id'].',') != 0) {
						unset($prop['cat_id']);
						$result['products'][$key]['property'][] = $prop; 
					}
				}
			}
			// загружаем данные характеристик для товаров
			// берем id товаров
			foreach ($result['products'] as $item) {
				$ids[] = $item['id'];
			}
			$prodIdIn = implode(',', $ids);
			unset($ids);
			// берем id характеристик
			foreach ($property as $prop) {
				$ids[] = $prop['id'];
			}
			$propIdIn = implode(',', $ids);
			unset($ids);
			// достаем значения характеристик
			$res = DB::query('SELECT * FROM '.PREFIX.'product_user_property_data WHERE prop_id IN ('.DB::quote($propIdIn, true).') 
				AND product_id IN ('.DB::quote($prodIdIn, true).')');
			while($row = DB::fetchAssoc($res)) {
				$propertyData[] = $row;
			}
			// прикрепляем значение характеристики к товарам
			foreach ($result['products'] as $key => $value) {
				foreach ($propertyData as $data) {
					if($data['product_id'] == $value['id']) {
						foreach ($value['property'] as $keyP => $valueP) {
							if($valueP['id'] == $data['prop_id']) {
								$result['products'][$key]['property'][$keyP]['data'][] = $data;
							}
						}
					}
				}
			}
		}
		// возврат параметров работы метода
		$result['page'] = $param['page'];
		$result['count'] = $param['count'];
		$result['variants'] = $param['variants'];
		$result['property'] = $param['property'];

		return $result;
	}

	/**
	 * Метод для добавления или обновления товаров.
	 * <code>
	 * $param['products'] = array(
	 * 	'id' => 1,	// id товара
	 * 	'cat_id' => 1,	// id категории
	 * 	'title' => 'Распределительный электрошкаф',	// название товара
	 * 	'description' => '<p>Периодичность обс>ила.</p>',	// описание товара
	 * 	'short_description' => '<p>Периодичность обс>ила.</p>',	// краткое описание товара
	 * 	'price' => 87894,	// цена
	 * 	'url' => 'raspredelitelnyy-elektroshkaf',	// последняя секция урла
	 * 	'image_url' => 'no-img.jpg',	// ссылки на изображения, разделитель |
	 * 	'code' => 'TR-15000-V1',	// артикул
	 * 	'count' => 35,	// количество
	 * 	'activity' => 1,	// видимость товара
	 * 	'meta_title' => 'Распределительный электрошкаф',	// заголовок страницы
	 * 	'meta_keywords' => 'Распределительный, электрошкаф',	// ключевые слова
	 * 	'meta_desc' => 'Распределительный электрошкаф',	// мета описание
	 * 	'old_price' => 38517,	// старая цена
	 * 	'weight' => 422.019,	// вес
	 * 	'link_electro' => ,	// сыылка на электронный товар
	 * 	'currency_iso' => 'RUR',	// символьный код валюты
	 * 	'price_course' => 87894,	// цена в валюте магазина
	 * 	'image_title' => ,	// 
	 * 	'image_alt' => ,	//
	 * 	'unit' => ,	// единица измерения
	 * 	'variants' => Array(	// варианты
	 * 		Array(												
	 * 			'title_variant' => '-Var1',	// заголовок варианта
	 * 			'image' => ,	// ссылка на изображение
	 * 			'price' => 87894,	// цена
	 * 			'old_price' => 38517,	// старая цена 
	 * 			'count' => 43,	// количество
	 * 			'code' => 'TR-15000-V1',	// артикул
	 * 			'weight' => 422.019,	// вес товара
	 * 			'currency_iso' => 'RUR',	// символьный код валюты
	 * 			'price_course' => 87894,	// цена в валюте магазина
	 * 			'color' => 87894,	// id цвета товара (если есть) от характеристики
	 * 			'size' => 87894,	// id размера товара (если есть) от характеристики
	 * 		),														
	 * 	),													
	 * 	'property' => Array(	// характиеристики
	 * 	    array(												
	 * 	        'name' => 'Строковая характеристик',	// название характеристики
	 * 	        'type' => 'string',	// тип хварактеристики
	 * 	        'value' => 'Значение,	// значение характеристики
	 * 	    ),													
	 * 	    array(												
	 * 	        'name' => 'Текстовая характеристика',	// название характеристики
	 * 	        'type' => 'textarea',	// тип хварактеристики
	 * 	        'value' => 'Тут может быть много текста',	// значение характеристики
	 * 	    )
	 * 	)
	 * );
	 * $res = $api->run('importProduct', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['products'] - входящий список товаров для импорта (максимум 100)
	 * @return string
	 */	
	public static function importProduct($param = null) {
		$model = new Models_Product;
		foreach ($param['products'] as $item) {
			$property = $item['property'];
			unset($item['property']);
			$variants = $item['variants'];
			unset($item['variants']);
			// если нет id, то пробуем его найти по артикулу
			if(empty($item['id'])) {
				$res = DB::query('SELECT id FROM '.PREFIX.'product WHERE code = '.DB::quote($item['code']));
				$id = DB::fetchAssoc($res);
				$item['id'] = $id['id'];
			}
			// если все равно нет id, то добавляем товар
			if(empty($item['id'])) {
				// создаем товар
				DB::query('INSERT INTO '.PREFIX.'product SET '.DB::buildPartQuery($item));
				$prodId = DB::insertId();
				// обрабатываем характеристики
				foreach ($property as $prop) {
					$propId = Property::createProp($prop['name'], $prop['type']);
					Property::createProductStringProp($prop['value'], $prodId, $propId);
					Property::createPropToCatLink($propId, $item['cat_id']);
				}
				// добавляем варианты товара
				foreach ($variants as $variant) {
					DB::query('INSERT INTO '.PREFIX.'product_variant SET '.DB::buildPartQuery($variant));
				}
			} else {
				$item['userProperty'] = $item['property'];
				unset($item['property']);
				$model->updateProduct($item);
			}
		}
		return 'Импорт завершен';
	}

	/**
	 * Метод для удаления товаров.
	 * <code>
	 * $param['products'] = array('1', '2', '3', '4', '5');
	 * $res = $api->run('deleteProduct', $param, true);
	 * viewData($res);
	 * </code>
	 * @param array|null $param['products'] - массив id для удаления товаров
	 * @return string
	 */
	public static function deleteProduct($param = null) {
		$model = new Models_Product;
		foreach ($param['products'] as $item) {
			$model->deleteProduct($item);
		}
		return 'Удаление завершено';
	}

}

?>