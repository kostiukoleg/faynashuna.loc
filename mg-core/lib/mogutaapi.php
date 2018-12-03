<?php

/**
 * Класс mogutaApi - класс для облегченного взаимодействия с API Могуты
 *
 * @package moguta.cms
 * @subpackage Libraries
 */

	class mogutaApi {

		public static $site = '';
		public static $token = '';
		public static $key = '';

		/**
		 * Конструктор класса для работы с API.
		 * @param string $site адрес магазина для обращения
		 * @param string $token токен
		 * @param string $key секретный ключ
		 */
		public function __construct($site, $token, $key) {
			self::$site = $site;
			self::$token = $token;
			self::$key = $key;
		}

		/**
		 * Инициализация запроса к магазину.
		 * @param string $method название метода для вызова
		 * @param array $param массив параметров для передачи в метод
		 * @param bool $allData выводить ли полный массив с информацией
		 * @param bool $validKey сравнивать ли подписи
		 * @return array
		 */
		public static function run($method, $param = '', $allData = false, $validKey = true) {
			$param = json_encode($param);

			$post['token'] = self::$token;
			$post['method'] = $method;
			$post['param'] = $param;
			$url = self::$site.'/api';
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$result = json_decode(curl_exec($ch), true);
			$info = curl_getinfo($ch);
			curl_close($ch);

			if($result['status'] != 'OK') {
				$allData = true;
			} else {
				if($validKey) {
					if(!self::validKey($result['sign'], $method, $param)) {
						if(!$allData) {
							$result['response'] = 'Некорректная подпись ответа сервера';
						} else {
							$result['message'] = 'Некорректная подпись ответа сервера';
						}
					}
				}
			}

			if($allData) {
				return $result;
			} else {
				return $result['response'];
			}
		}

		/**
		 * Проверка подписей.
		 * @param string $sign подпись с магазина
		 * @param array $method название метода
		 * @param bool $param набор параметров
		 * @return bool
		 */
		private static function validKey($sign, $method, $param) {
			if($sign == md5(self::$token.$method.str_replace('amp;', '', htmlspecialchars($param)).self::$key)) {
				return true;
			} else {
				return false;
			}
		}
	}

?>