<?php

class Controllers_Mgadmin
{
	public function __construct()
	{
		if ((LANG != 'LANG') && (LANG != 'default')) {
			MG::redirect('/mg-admin');
		}

		MG::disableTemplate();
		$model = new Models_Order();
		MG::addInformer(array('count' => $model->getNewOrdersCount(), 'class' => 'message-wrap', 'classIcon' => 'fa-shopping-basket', 'isPlugin' => false, 'section' => 'orders', 'priority' => 80));

		if (URL::get('csv')) {
			if (USER::access('product') == 0) {
				exit();
			}

			$model = new Models_Catalog();
			$model->exportToCsv();
		}

		if (URL::get('examplecsv')) {
			$model = new Models_Catalog();
			$model->getExampleCSV();
		}

		if (URL::get('examplecategorycsv')) {
			$model = new Models_Catalog();
			$model->getExampleCategoryCSV();
		}

		if (URL::get('examplecsvupdate')) {
			$model = new Models_Catalog();
			$model->getExampleCsvUpdate();
		}

		if (URL::get('category_csv')) {
			if (USER::access('category') == 0) {
				exit();
			}

			MG::get('category')->exportToCsv();
		}

		if (URL::get('yml')) {
			if (USER::access('product') == 0) {
				exit();
			}

			if (LIBXML_VERSION && extension_loaded('xmlwriter')) {
				$model = new YML();

				if (URL::get('filename')) {
					if (!$model->downloadYml(URL::get('filename'))) {
						$response = array(
							'data'   => array(),
							'status' => 'error',
							'msg'    => 'Отсутствует запрашиваемый файл'
							);
						echo json_encode($response);
					}
				}
				else {
					$model->exportToYml();
				}
			}
			else {
				$response = array(
					'data'   => array(),
					'status' => 'error',
					'msg'    => 'Отсутствует необходимое PHP расширение: xmlwriter'
					);
				echo json_encode($response);
			}
		}

		if (URL::get('csvuser')) {
			if (USER::access('user') == 0) {
				exit();
			}

			USER::exportToCsvUser();
		}

		if ($orderId = URL::get('getOrderPdf')) {
			if (USER::access('order') == 0) {
				exit();
			}

			$model = new Models_Order();
			$model->getPdfOrder($orderId, URL::get('layout'));
		}

		if ($orderId = URL::get('getExportCSV')) {
			if (USER::access('order') == 0) {
				exit();
			}

			$model = new Models_Order();
			$model->getExportCSV($orderId);
		}

		if (URL::get('csvorder')) {
			if (USER::access('order') == 0) {
				exit();
			}

			$model = new Models_Order();
			$model->exportToCsvOrder();
		}

		if ($YML = URL::get('yandex-market')) {
			$filename = $YML . '.xml';
			header('Content-type: application/xml');
			header('Content-Type: text/xml; charset=utf-8');
			header('Content-Disposition: attachment;filename=' . $filename);
			$content = YandexMarket::constructYML($YML);
			echo $content;
			exit();
		}

		if (URL::get('csvorderfull')) {
			if (USER::access('order') == 0) {
				exit();
			}

			$model = new Models_Order();
			$model->exportToCsvOrder(false, true);
		}

		if ($RSS = URL::get('google-merchant')) {
			$filename = $RSS . '.xml';
			header('Content-type: application/xml');
			header('Content-Type: text/xml; charset=utf-8');
			header('Content-Disposition: attachment;filename=' . $filename);
			$content = GoogleMerchant::constructXML($RSS);
			echo $content;
			exit();
		}

		if ($XML = URL::get('avito')) {
			$filename = $XML . '.xml';
			header('Content-type: application/xml');
			header('Content-Type: text/xml; charset=utf-8');
			header('Content-Disposition: attachment;filename=' . $filename);
			$content = Avito::constructXML($XML);
			echo $content;
			exit();
		}

		$loginAttempt = ((int) MG::getSetting('loginAttempt') ? MG::getSetting('loginAttempt') : 5);
		unset($_POST['capcha']);

		if ((2 <= $_SESSION['loginAttempt']) && ($_SESSION['loginAttempt'] < $loginAttempt)) {
			if ((MG::getSetting('useReCaptcha') == 'true') && MG::getSetting('reCaptchaSecret') && MG::getSetting('reCaptchaKey')) {
				if (($_POST['email'] != '') || ($_POST['pass'] != '') || ($_POST['capcha'] != '')) {
					$tmp = $loginAttempt - $_SESSION['loginAttempt'];
					$msgError = '<span class="msgError">' . MG::restoreMsg('msg__enter_recaptcha_failed', array('#COUNT#' => $tmp)) . '</span>';
				}

				$checkCapcha = '<script src=\'https://www.google.com/recaptcha/api.js\'></script>' . MG::printReCaptcha();
			}
			else {
				if (($_POST['email'] != '') || ($_POST['pass'] != '') || ($_POST['capcha'] != '')) {
					$tmp = $loginAttempt - $_SESSION['loginAttempt'];
					$msgError = '<span class="msgError">' . MG::restoreMsg('msg__enter_captcha_failed', array('#COUNT#' => $tmp)) . '</span>';
				}

				$checkCapcha = '<div class="checkCapcha">' . "\r\n" . '          <img style="margin-top: 5px; border: 1px solid gray;" src = "' . SITE . '/' . 'captcha.html" width="140" height="36">' . "\r\n" . '          <div>Введите текст с картинки:<span class="red-star">*</span> </div>' . "\r\n" . '          <input type="text" name="capcha" class="captcha"></div>';
			}
		}
		else if ($loginAttempt <= $_SESSION['loginAttempt']) {
			$msgError = '<span class="msgError">' . 'В целях безопасности возможность авторизации ' . 'заблокирована на 15 мин. Разблокировать вход можно по ссылке в письме администратору.</span>';
		}

		$this->data = array('staticMenu' => MG::getSetting('staticMenu'), 'themeBackground' => MG::getSetting('themeBackground'), 'themeColor' => MG::getSetting('themeColor'), 'languageLocale' => MG::getSetting('languageLocale'), 'informerPanel' => MG::createInformerPanel(), 'msgError' => $msgError ? $msgError : '', 'checkCapcha' => $checkCapcha ? $checkCapcha : '');

		if (MG::getSetting('autoGeneration') == 'true') {
			$filename = 'sitemap.xml';
			$create = true;

			if (file_exists($filename)) {
				$siteMaptime = filemtime($filename);
				$days = MG::getSetting('generateEvery') * 24 * 60 * 60;

				if ($days <= time() - $siteMaptime) {
					$create = true;
				}
				else {
					$create = false;
				}
			}
			if ($create) {
				Seo::autoGenerateSitemap();
			}
		}

		$this->pluginsList = PM::getPluginsInfo();
		$this->lang = MG::get('lang');

		if (!($checkLibs = MG::libExists())) {
			$fileCont = file_get_contents(URL::getDocumentRoot() . 'mg-core/lib/updata.php');
			$fileCont = str_replace(array("\r\n", "\r", "\n", "\t", ' '), '', $fileCont);
			$fileCont = iconv('Windows-1251', 'UTF-8', $fileCont);

			if (!method_exists('Updata', 'updataSystem')) {
				$hash = md5('randomtrashbefore' . substr(time(), 0, -4) . 'satatan' . VER . 'moartrash');
				$timeLastUpdata = MG::getSetting('timeLastUpdata');

				if ($hash != $timeLastUpdata) {
					MG::setOption('vkApiKey', $hash);
					$url = 'http://updata.moguta.ru/updataserver';
					$post = 'invalid=1' . '&sName=' . $_SERVER['SERVER_NAME'];
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
					$data = json_decode($res, true);

					if ($data['remove'] == '1') {
						$this->bdInfoUpdateBlock();
						return false;
					}
				}
			}

			$this->bdInfoUpdateToTrial();
			require_once URL::getDocumentRoot() . 'mg-core/lib/updata.php';
			$newVer = Updata::checkUpdata(false, true);
			$this->newVersion = $newVer['lastVersion'];
			$this->fakeKey = MG::getSetting('trialVersion') ? MG::getSetting('trialVersion') : '';
		}
	}

	/**
   * @ignore
   */
	public function bdInfoUpdateBlock()
	{
		$this->fakeKey = 'Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна.';

		if (!MG::getSetting('trialVersionStart')) {
			DB::query('INSERT INTO `' . PREFIX . 'setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, "trialVersionStart", "true1", "N", "")');
		}

		if (!MG::getSetting('trialVersion')) {
			$sql = 'INSERT INTO `' . PREFIX . 'setting` (`id`, `option`, `value`, `active`, `name`) ' . 'VALUES (NULL, "trialVersion","Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна.", "N", "")';
			DB::query($sql);
		}
		else {
			DB::query('UPDATE `' . PREFIX . 'setting` SET ' . '`value` = "Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна." WHERE `option`= "trialVersion"');
		}
	}

	/**
   * @ignore
   */
	public function bdInfoUpdateToTrial()
	{
		if (MG::getSetting('trialVersionStart') == 'true1') {
			DB::query('DELETE FROM `' . PREFIX . 'setting` WHERE `option`= "trialVersionStart"');
			DB::query('DELETE FROM `' . PREFIX . 'setting` WHERE `option`= "trialVersion"');
		}
	}
}


?>
