<?php

class Updata
{
	static public $_updataServer = '/';

	public function __construct()
	{
		$_alias = 'or';
		$_alias .= '3d34e23rf5u6ck';
		$obj = new $_alias();
		$obj->oror3d34e23rf5u6ck();
	}

	/**
   * Проверяет на сервере актуальность текущей системы.
   * $noerror не позволяет вывести исключение перед версткой
   * @return  bool|array $result массив с описанием последней версии и ее номером.
   */
	static public function checkUpdata($noCache = false, $noerror = false)
	{
		$timeLastUpdata = @file_get_contents(SITE_DIR . 'mg-core/script/tcpdf/include/tcpdf_statica.php');
		$hash = md5('randomtrashbefore' . substr(time(), 0, -4) . 'randomtrashafter' . VER . 'moartrash');

		if (!$timeLastUpdata) {
			@file_put_contents(SITE_DIR . 'mg-core/script/tcpdf/include/tcpdf_statica.php', 'q');
		}

		if (!$timeLastUpdata) {
			$timeLastUpdata = MG::getSetting('timeLastUpdata');
		}

		if (($hash == $timeLastUpdata) && !$noCache) {
			$resp = MG::getSetting('currentVersion');
			$data = json_decode($resp, true);
		}
		else {
			$count = '';
			$summ = '';

			if (MG::getSetting('consentData') == 'true') {
				$row = DB::query('SELECT COUNT(`id`) as `count` FROM `' . PREFIX . 'product`');
				$res = DB::fetchArray($row);
				$count = ($res['count'] ? $res['count'] : 0);
				$row2 = DB::query('SELECT SUM(`summ`) AS  `summ` FROM `' . PREFIX . 'order` ' . "\n" . '          WHERE `status_id` = 2 OR `status_id` = 5');
				$res2 = DB::fetchArray($row2);
				$summ = ($res2['summ'] ? $res2['summ'] : 0);
			}

			$checkInstall = (!empty($_COOKIE['installerMoguta']) ? $_COOKIE['installerMoguta'] : '');
			$fullVersion = (self::checkVersion() ? 'true' : 'false');
			$res = DB::query('SELECT value FROM ' . PREFIX . 'setting WHERE `option` = \'templateName\'');

			while ($row = DB::fetchAssoc($res)) {
				$tempalate = $row['value'];
			}

			$res = DB::query('SELECT value FROM ' . PREFIX . 'setting WHERE `option` = \'cacheMode\'');

			while ($row = DB::fetchAssoc($res)) {
				$cache = $row['value'];
			}

			$res = DB::query('SELECT value FROM ' . PREFIX . 'setting WHERE `option` = \'consentData\'');

			while ($row = DB::fetchAssoc($res)) {
				$consentData = $row['value'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'user');

			while ($row = DB::fetchAssoc($res)) {
				$userCount = $row['count(id)'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'category');

			while ($row = DB::fetchAssoc($res)) {
				$categoryCount = $row['count(id)'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'property_data');

			while ($row = DB::fetchAssoc($res)) {
				$propertyDataCount = $row['count(id)'];
			}

			$res = DB::query('SELECT CONCAT(id) FROM ' . PREFIX . 'payment');

			while ($row = DB::fetchAssoc($res)) {
				$payments = $row['CONCAT(id)'];
			}

			$payments = array();
			$res = DB::query('SELECT id FROM ' . PREFIX . 'payment WHERE `activity` = 1');

			while ($row = DB::fetchAssoc($res)) {
				$payments[] = $row['id'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'order');

			while ($row = DB::fetchAssoc($res)) {
				$allOrders = $row['count(id)'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'order WHERE add_date > ' . DB::quote(date('Y-m-d H:i:s', strtotime('-30 day', strtotime(date('Y-m-d H:i:s'))))));

			while ($row = DB::fetchAssoc($res)) {
				$monthOrders = $row['count(id)'];
			}

			$res = DB::query('SELECT count(id) FROM ' . PREFIX . 'order WHERE add_date > ' . DB::quote(date('Y-m-d H:i:s', strtotime('-7 day', strtotime(date('Y-m-d H:i:s'))))));

			while ($row = DB::fetchAssoc($res)) {
				$weekOrders = $row['count(id)'];
			}

			$payments = implode(',', $payments);
			$post = 'version=' . VER . '&sName=' . $_SERVER['SERVER_NAME'] . '&sIP=' . ($_SERVER['SERVER_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['SERVER_ADDR']) . '&sKey=' . MG::getSetting('licenceKey') . '&sSiteName=' . MG::getSetting('sitename') . '&sAdmin=' . MG::getSetting('adminEmail') . '&timeStartEngine=' . MG::getSetting('timeStartEngine') . '&timeFirstUpdate=' . MG::getSetting('timeFirstUpdate') . '&sPhone=' . MG::getSetting('shopPhone') . '&sAddress=' . MG::getSetting('shopAddress') . '&catalog=' . $count . '&categories=' . $categoryCount . '&properyData=' . $propertyDataCount . '&orders=' . $summ . '&users=' . $userCount . '&installer=' . $checkInstall . '&fullVersion=' . $fullVersion . '&php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '&plugins=' . (count(scandir(PLUGIN_DIR)) - 2) . '&template=' . $tempalate . '&cache=' . $cache . '&consentData=' . $consentData . '&payments=' . $payments . '&memoryLimit=' . (int) ini_get('memory_limit') . '&allOrders=' . $allOrders . '&monthOrders=' . $monthOrders . '&weekOrders=' . $weekOrders . '&edition=giper' . '&trial=1';
			$resp = self::sendCurl(self::$_updataServer . '/updataserver', $post);

			if (stristr($resp, 'error:') !== false) {
				$res = explode('error:', $resp);
			}
			else {
				$res = array($resp, 'false');
			}

			$data = json_decode($res[0], true);
			if (is_array($data['notifications']) && !empty($data['notifications'])) {
				$sql = 'INSERT INTO `' . PREFIX . 'notification` (`message`) VALUES ';

				foreach ($data['notifications'] as $value) {
					$sql .= '(' . DB::quote($value) . '),';
				}

				DB::query(substr($sql, 0, -1));
			}

			DB::query('DELETE FROM `' . PREFIX . 'notification` WHERE `status`= \'1\'');
			DB::query("\n" . '        UPDATE `' . PREFIX . 'setting`' . "\n" . '          SET `value`=' . DB::quote($res[0]) . "\n" . '        WHERE `option`=\'currentVersion\'' . "\n" . '      ');
			@file_put_contents(SITE_DIR . 'mg-core/script/tcpdf/include/tcpdf_statica.php', $hash);
			DB::query("\n" . '      UPDATE `' . PREFIX . 'setting`' . "\n" . '        SET `value`=' . DB::quote($hash) . "\n" . '      WHERE `option`=\'timeLastUpdata\'' . "\n" . '      ');
			PM::checkPluginsUpdate();

			if ($res[1] != 'false') {
				$disabled = json_decode($res[1], true);

				if (($disabled['status'] == 'error') && ($disabled['remove'] == '1')) {
					if (!MG::getSetting('trialVersionStart')) {
						DB::query('INSERT INTO `' . PREFIX . 'setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, "trialVersionStart", "true", "N", "")');
					}
				}

				$result['fakeKey'] = $disabled['msg'];

				if (!MG::getSetting('trialVersion')) {
					$sql = 'INSERT INTO `' . PREFIX . 'setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, "trialVersion",' . DB::quote($disabled['msg']) . ', "N", "")';
					DB::query($sql);
				}
				else {
					DB::query('UPDATE `' . PREFIX . 'setting` SET `value` = ' . DB::quote($disabled['msg']) . ' WHERE `option`= "trialVersion"');
				}
			}
			else {
				if (MG::getSetting('trialVersionStart') && (MG::getSetting('trialVersionStart') != 'true1')) {
					DB::query('DELETE FROM `' . PREFIX . 'setting` WHERE `option`= "trialVersionStart"');
				}

				DB::query('DELETE FROM `' . PREFIX . 'setting` WHERE `option`= "trialVersion"');
			}
		}


		if (!empty($data['dateActivateKey'])) {
			MG::setOption(array('option' => 'dateActivateKey ', 'value' => $data['dateActivateKey']));
		}

		if (($data['dateActivateKey'] != '0000-00-00 00:00:00') && MG::getSetting('licenceKey') && $resp && $noerror) {
			$now_date = strtotime($data['dateActivateKey']);
			$future_date = strtotime(date('Y-m-d'));
			$dayActivate = 365 - floor(($future_date - $now_date) / 86400);

			if ($dayActivate < 30) {
				if ($dayActivate < 7) {
					if ($dayActivate <= 0) {
						MG::setOption(array('option' => 'notifInfo', 'value' => '<div id="timeInfo" class="alert">Срок действия ключа истек!' . "\n" . '              (<a class="link" target="blank" href="https://moguta.ru/keyup?key=' . MG::getSetting('licenceKey') . '">Продлить сейчас</a>)</div>'));
					}
					else {
						MG::setOption(array('option' => 'notifInfo', 'value' => '<div id="timeInfo" class="alert">До окончания лицензии ключа осталось ' . (0 < $dayActivate ? $dayActivate : 0) . ' дн.' . "\n" . '              (<a class="link" target="blank" href="https://moguta.ru/keyup?key=' . MG::getSetting('licenceKey') . '">Продлить сейчас</a>)</div>'));
					}
				}
				else {
					MG::setOption(array('option' => 'notifInfo', 'value' => '<div id="timeInfo" class="warning">До окончания лицензии ключа осталось ' . (0 < $dayActivate ? $dayActivate : 0) . ' дн.' . "\n" . '            (<a class="link" target="blank" href="https://moguta.ru/keyup?key=' . MG::getSetting('licenceKey') . '">Продлить сейчас</a>)</div>'));
				}
			}
			else {
				MG::setOption(array('option' => 'notifInfo', 'value' => ''));
			}
		}
		else {
			MG::setOption(array('option' => 'notifInfo', 'value' => ''));
		}

		if ($data['last']) {
			$result['msg'] = "\n" . '      <ul class="system-version-list">' . "\n" . '        <li> <b>Ближайшая версия для обновления: </b><span id="lVer">' . $data['last'] . '</span></li>' . "\n" . '        <li> <b>Последняя версия системы: </b><span id="fVer">' . $data['final'] . '</span></li>' . "\n" . '        <li> <b>Описание: </b>' . $data['disc'] . '</li>       ' . "\n" . '      </ul>';
			$result['lastVersion'] = $data['last'];
			$args = func_get_args();
			return MG::createHook('Updata' . '_' . 'checkUpdata', $result, $args);
		}

		$args = func_get_args();
		return MG::createHook('Updata' . '_' . 'checkUpdata', false, $args);
	}

	/**
   * Обновляет текущую версию системы.
   * @param string $version - версия последнего обновления
   * @return bool
   */
	static public function updataSystem($sysFold, $version)
	{
		$file = 'update-m.zip';

		if (!file_exists(SITE_DIR . $file)) {
			$ch = curl_init(self::$_updataServer . '/updata/history/' . $sysFold . '/update_' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.zip');
			$fp = fopen($file, 'w');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}

		$args = func_get_args();
		$return = false;

		if (file_exists($file)) {
			$return = true;
		}

		if (!filesize($file)) {
			$return = false;
			unlink($file);
		}

		$zip = new ZipArchive();
		$res = $zip->open($file, ZIPARCHIVE::CREATE);

		if ($res !== true) {
			$return = false;
			unlink($file);
		}

		return MG::createHook('Updata' . '_' . 'updataSystem', $file, $args);
	}

	/**
   * Распаковывает архив с обновлением, если он есть в корне сайта.
   * После распаковки удаляет заданый архив.
   *
   * @param $file - название архива, который нужно распаковать
   * @return bool
   */
	static public function extractZip($file)
	{
		if (file_exists($file)) {
			$zip = new ZipArchive();
			$res = $zip->open($file, ZIPARCHIVE::CREATE);

			if ($res === true) {
				$realDocumentRoot = str_replace(DIRECTORY_SEPARATOR . 'mg-core' . DIRECTORY_SEPARATOR . 'lib', '', dirname(__FILE__));
				$zip->extractTo($realDocumentRoot);
				$zip->close();
				unlink($file);
				self::updataSubInfo('modificatoryInc.php');
				MG::setOption('timeLastUpdata', 'q');
				@file_put_contents(SITE_DIR . 'mg-core/script/tcpdf/include/tcpdf_statica.php', 'q');
				return true;
			}

			return false;
		}

		return false;
	}

	/**
   * Отправляет запрос на сервер, с целью получить данные о последней версии.
   *
   * @param string $url адрес сервера.
   * @param string $post  параметры для POST запроса.
   * @return string ответ сервера.
   */
	static private function sendCurl($url, $post)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		$res = curl_exec($ch);
		curl_close($ch);
		$args = func_get_args();
		return MG::createHook('Updata' . '_' . 'sendCurl', $res, $args);
	}

	/**
   * Выполняет набор MySQL запросов для адаптации страрой версии БД к новому виду.
   * Удаляет необходимые файлы при обновлении системы.
   * Файл модификтаор содерсит массивы $sqlQuery и $deleteArray, в которых перечисленны
   * запросы к БД и пути к удаляемым файлам.
   *
   * @param string $modificatoryFile имя файла модификатора.
   * @return bool
   */
	static private function updataSubInfo($modificatoryFile)
	{
		if (!file_exists($modificatoryFile)) {
			return false;
		}

		require_once $modificatoryFile;

		if (is_array($sqlQuery)) {
			foreach ($sqlQuery as $sql) {
				DB::query($sql);
			}
		}

		if (is_array($deleteArray)) {
			foreach ($deleteArray as $deletedfile) {
				if (file_exists($deletedfile)) {
					unlink($deletedfile);
				}
			}
		}

		unlink($modificatoryFile);
		return true;
	}

	static public function preDownload($version)
	{
		$post = 'step=1' . '&sName=' . $_SERVER['SERVER_NAME'] . '&sIP=' . ($_SERVER['SERVER_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['SERVER_ADDR']) . '&sKey=' . MG::getOption('licenceKey') . '&ver=' . $version . '&php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$res = self::sendCurl(self::$_updataServer . '/updataserver', $post);

		try {
			$data = json_decode($res, true);
		}
		catch (Exception $exc) {
			$data['msg'] = $exc;
			$data['status'] = 'error';
		}

		(array('option' => 'dateActivateKey ', 'value' => $data['dateActivateKey']));

		if ('succes' == $data['status']) {
			$file = self::updataSystem($data['msg'], $version);

			if (!file_exists($file)) {
				$data['msg'] = 'Обновление не удалось!';
				$data['status'] = 'error';
			}
		}

		return $data;
	}

	public function checkVersion()
	{
		$res = (class_exists('Controllers_Compare') || class_exists('Controllers_Group') ? 'true' : 'false');

		if ($res != 'true') {
			$dbRes[] = DB::query('SHOW COLUMNS FROM `' . PREFIX . 'product` WHERE FIELD = "system_set"');

			if ($row = DB::fetchArray($dbRes)) {
				return true;
			}

			$dbRes = DB::query('SHOW COLUMNS FROM `' . PREFIX . 'order` WHERE FIELD = "orders_set"');

			if ($row = DB::fetchArray($dbRes)) {
				return true;
			}
		}

		if ($res != 'true') {
			$pa = new Controllers_Payment();
			if (method_exists($pa, 'webmoney') || method_exists($pa, 'yandexKassa') || method_exists($pa, 'qiwi')) {
				return true;
			}
		}

		$filter = new Filter(array());

		if ($filter->getHtmlPropertyFilter() != '') {
			return true;
		}

		return false;
	}
}

class or3d34e23rf5u6ck
{
	public $oror3d34e23rf5u6ck;

	public function __construct()
	{
		$valid ? $valid : 'true';
		$_alias = $valid;
		define('MyConst', $_alias);
	}

	public function oror3d34e23rf5u6ck()
	{
		$this->oror3d34e23rf5u6ck = 111;

		if (!defined('MyConst')) {
			exit('Direct access not permitted');
		}

		define('get', 'e466c8d28');
	}
}


?>
