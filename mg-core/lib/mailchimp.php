<?php 
/**
 * Класс MailChimp используется для выгрузки пользователей магазина в MailChimp
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class MailChimp {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {
		$options = unserialize(stripslashes(MG::getSetting('mailChimp')));

		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';
		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Сохраняет настройки.
	* @param string $api ключ приложения
	* @param string $listId ID списка на MailChimp
	* @param string $perm спрашивать ли у пользователя разрешение на рассылку
	* @param string $uploadNew автоматически выгружать пользователей при регистрации на сайте
	* @return bool
	*/
	static function saveOptions($api, $listId, $perm, $uploadNew){
		$tmp = array('api' => $api, 'listId' => $listId, 'perm' => $perm, 'uploadNew' => $uploadNew);

		$tmp = addslashes(serialize($tmp));

		MG::setOption(array('option' => 'mailChimp', 'value'  => $tmp, 'active' => 'N'));

		return true;
	}
   /**
	* Массовая выгрузка пользователей.
	* @param string $api ключ приложения
	* @param string $listId ID списка на MailChimp
	* @param string $perm спрашивать ли у пользователя разрешение на рассылку
	* @return bool
	*/
	static function uploadAll($api, $listId, $perm){
		$dc = explode('-', $api);
		$dc = $dc[1];

		if (strlen($api) > 1 && strlen($dc) > 1 && strlen($listId) > 1 && strlen($perm) > 1) {
			
			$jsonData = array(
				"operations" => array()
			);

			$res = DB::query("SELECT `email`, `name`, `sname`, `birthday` FROM `".PREFIX."user`");
			while ($row = DB::fetchAssoc($res)) {

				$body = "{\"email_address\":\"".$row['email']."\", \"status_if_new\":\"".$perm."\", \"FNAME\":\"".$row['name']."\", \"merge_fields\": {\"FNAME\": \"".$row['name']."\"";
				if (strlen($row['sname']) > 1) {
					$body .= ", \"LNAME\": \"".$row['sname']."\"";
				}

				if (strlen($row['birthday']) > 1) {
					$bd = date('m/d', strtotime($row['birthday']));
					$body .= ", \"BIRTHDAY\": \"".$bd."\"";
				}

				$body .= "}}";
				$tmp = array(
					'method' => 'PUT',
					'path' => 'lists/'.$listId.'/members/'.md5($row['email']),
					'body' => $body
				);

				$jsonData['operations'][] = $tmp;

			}

			$jsonData = json_encode($jsonData);

			$url = 'https://'.$dc.'.api.mailchimp.com/3.0/batches/';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "anystring:$api");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
			$result = curl_exec($ch);
			curl_close($ch);

			return true;
		}
		else{
			return false;
		}
	}
   /**
	* Автоматическая выгрузка пользователя при регистрации.
	* @param string $api ключ приложения
	* @param string $listId ID списка на MailChimp
	* @param string $perm спрашивать ли у пользователя разрешение на рассылку
	* @param string $mail почта пользователя
	* @param string $name имя пользователя
	* @param string $sname фамилия пользователя
	* @param string $birthday день рождения пользователя
	*/
	static function uploadOne($api, $listId, $perm, $mail, $name, $sname, $birthday){

		$dc = explode('-', $api);
		$dc = $dc[1];

		if (strlen($api) > 1 && strlen($dc) > 1 && strlen($listId) > 1) {

			$user = array('email_address' => $mail, 'status' => $perm, 'merge_fields' => array('FNAME' => $name));

			if (strlen($sname) > 1) {
				$user['merge_fields']['LNAME'] = $sname;
			}

			if (strlen($birthday) > 1) {
				$bd = date('m/d', strtotime($row['birthday']));
				$user['merge_fields']['BIRTHDAY'] = $bd;
			}

			$jsonData = json_encode($user);

			$url = 'https://'.$dc.'.api.mailchimp.com/3.0/lists/'.$listId.'/members/';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "anystring:$api");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
			$result = curl_exec($ch);
			curl_close($ch);
		}
	}
}





?>