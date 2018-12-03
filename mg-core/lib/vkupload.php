<?php 
/**
 * Класс VKUpload используется для загрузки и удаления товаров в VKontakte
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class VKUpload {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {
		$options = unserialize(stripslashes(MG::getSetting('vkUpload')));

		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';

		$model = new Models_Catalog;
		$arrayCategories = $model->categoryId = MG::get('category')->getHierarchyCategory(0);
		$categoriesOptions = MG::get('category')->getTitleCategory($arrayCategories, URL::get('category_id'));

		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Устанавливает соединение с VKontakte и получает списки категорий VKontakte и подборок VKontakte.
	* @param string $token токен соединения
	* @return array массив с версткой категорий VKontakte, токеном подключения, версткой подборок VKontakte и ошибками
	*/
	static function connect($token){
		$errors = '';
		$options = unserialize(stripslashes(MG::getSetting('vkUpload')));

		$result = json_decode(file_get_contents('https://oauth.vk.com/access_token?client_id='.$options["vkAppId"].
        '&client_secret='.$options["vkApiKey"].'&redirect_uri='.SITE.'/mg-admin&code='.$token),true);

		$access_token = $result['access_token'];

		$result = json_decode(file_get_contents('https://api.vk.com/method/market.getCategories?count=1000&v=5.73&offset=0&access_token='.$access_token),true);

		$parentCategories = array();
		$categories = array();

		foreach ($result['response']['items'] as $key => $value) {
			if (isset($value['id'])) {
				$parentCategories[$value['section']['id']] = $value['section']['name'];
				$categories[$value['section']['id']][$value['id']] = $value['name'];
			}
		}

		$selects = '<div class="row">
			<div class="small-4 columns">
				<label class="dashed">Группа категорий товаров в VK:</label>
			</div>
			<div class="small-8 columns">
			<select class="vkMainCat">';

		foreach ($parentCategories as $key => $value) {
			$selects .= '<option value="'.$key.'">'.$value.'</option>';
		}

		$selects .= '</select></div></div>';

		foreach ($categories as $key => $value) {
			$selects .= '<div class="row vkMiscCatContainer">
				<div class="small-4 columns">
					<label class="dashed">Категория товаров в VK:</label>
				</div>
				<div class="small-8 columns">
				<select class="vkMiscCat" part="'.$key.'">';
			foreach ($value as $key2 => $value2) {
				$selects .= '<option value="'.$key2.'">'.$value2.'</option>';
			}
			$selects .= '</select></div></div>';
		}

		$result = json_decode(file_get_contents('https://api.vk.com/method/market.getAlbums?owner_id=-'.$options["vkGroupId"].'&v=5.73&count=100&offset=0&access_token='.$access_token),true);

		if (empty($parentCategories) || empty($categories) || !empty($result['error']['error_code'])) {
			$errors .= 'Ошибка при подключении к VK, проверьте ваши настройки подключения'.PHP_EOL;
		}

		$err = false;
		$testFile = SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'test.txt';
		if(!$tf=@fopen($testFile, 'w')){
			$err = true;
		}elseif(!@chmod($testFile, 0777)){
			$err = true;
		}elseif(!@unlink($testFile)){
			$err = true;
		}
		if ($err) {
			$errors .= 'Нет прав на запись в '.SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.PHP_EOL;
		}

		$albums = '<div class="row">
			<div class="small-4 columns">
				<label class="dashed">Подборка товаров в VK:</label>
			</div>
			<div class="small-8 columns">
			<select class="vkAlbumsSelect">
			<option value="0">Не использовать подборку</option>';

		foreach ($result['response']['items'] as $key => $value) {
			if (isset($value['id']) && !empty($value['title'])) {
				$albums .= '<option value="'.$value['id'].'">'.$value['title'].'</option>';
			}
		}
		$albums .= '</select></div></div>';

		$result = array('selects' => $selects, 'access_token' => $access_token, 'albums' => $albums, 'errors' => $errors);

		return $result;
	}
   /**
	* Получает ID товаров магазина для выгрузки в VKontakte.
	* @param array $shopCats массив категорий магазина
	* @param string $inactiveToo выгружать ли неактивные товары
	* @return array массив с ID товаров магазина и количеством найденых товаров
	*/
	static function getNum($shopCats, $inactiveToo, $useAdditionalCats){
		foreach ($shopCats as $key => $value) {
			$shopCats[$key] = (int)$value;
		}

		// $cats = implode(', ', $shopCats);
		$productIDs = array();

		$filter = "(`cat_id` IN (".DB::quoteIn($shopCats).") ";

		if ($useAdditionalCats == 'true') {
			foreach ($shopCats as $cat) {
				$filter .= ' OR FIND_IN_SET('.DB::quote($cat).', `inside_cat`)';
			}							
		}

		$filter .= ')';

		if ($inactiveToo != 'true') {
			$filter .= " AND activity = 1";
		}

		$rows = DB::query("SELECT `id` FROM `".PREFIX."product` WHERE ".$filter);
		while ($row = DB::fetchAssoc($rows)) {
			$productIDs[] = $row['id'];
		}
		
		file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));
		
		$result = array('productsCount' => count($productIDs));
		return $result;
	}
   /**
	* Выгружает товары магазина.
	* @param string $access_token токен подключения
	* @param string $vkCat категория VKontakte
	* @param string $vkAlbum подборка VKontakte
	* @param array $productIDs ID товаров для выгрузки
	* @param string $useNull выгружать ли закончившиеся товары
	* @return array массив с оставшимеся ID товаров для выгрузки, количеством товаров для выгрузки и ошибками
	*/
	static function upload($access_token, $vkCat, $vkAlbum, $productIDs, $useNull){
		$options = unserialize(stripslashes(MG::getSetting('vkUpload')));
		$productIDs = unserialize(file_get_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt'));

		$timeHave = 15;
		$timerSave = microtime(true);
		$errorCounter = 0;
		$errors = '';

		while (count($productIDs) > 0) {

			$timeHave -= microtime(true) - $timerSave;
			$timerSave = microtime(true);
			if($timeHave < 0 || $errorCounter > 10) {
				file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));
				$result = array('remaining' => count($productIDs), 'errors' => $errors);
				return $result;
			}

			$photoId = array();
			$sqlRes = false;

			$currentProductId = array_shift($productIDs);

			$model = new Models_Product;
			$product = $model->getProduct($currentProductId, false, true);
			$descr = htmlspecialchars_decode(trim($product['description']));
			$descr = html_entity_decode($descr);
			$descr = strip_tags($descr);
			$title = htmlspecialchars_decode(trim($product['title']));
			$title = html_entity_decode($title);
			$title = strip_tags($title);
			$title = MG::textMore($title, 95);
			$title = substr($title, 0, 99);
			$price = (float)round(MG::numberDeFormat($product['price']),2);

			if ($price < 0.01) {
				$price = 0.01;
			}

			if ($useNull != 'true') {
				$variants = $model->getVariants($currentProductId);
				if (is_array($variants) && !empty($variants)) {
					$empty = true;
					foreach ($variants as $variant) {
						if ($variant['count'] != 0) {
							$empty = false;
						}
					}
					if ($empty) {
						$errors .= 'Товар "'.$title.'" закончился и был товар пропущен.'.PHP_EOL;
						$errorCounter++;
						// mg::loger($product);
						continue;
					}
				}
				else{
					if ((int)$product['count'] == 0) {
						$errors .= 'Товар "'.$title.'" закончился и был товар пропущен.'.PHP_EOL;
						$errorCounter++;
						// mg::loger($product);
						continue;
					}
				}
			}

			if (strlen($title) < 4) {
				$errors .= 'Название товара "'.$title.'" меньше 4 символов - товар пропущен.'.PHP_EOL;
				$errorCounter++;
				continue;
			}

			if (strlen($descr) < 10) {
				$errors .= 'Описание товара "'.$title.'" меньше 10 символов - товар пропущен.'.PHP_EOL;
				$errorCounter++;
				continue;
			}

			$sql = DB::query("SELECT * FROM `".PREFIX."vk-export` WHERE `moguta_id` = ".DB::quote($currentProductId));

			if ($sqlRes = DB::fetchassoc($sql)) {//обновление товара
				if ($sqlRes['moguta_img'] == $product['image_url']) {
					$photoId['id'] = $sqlRes['vk_img'];
					$oldPhoto = true;
					// mg::loger('same photo');
				}
				else{
					$photoId = self::dumpPhoto($product['image_url'], $options, $access_token, $title);
					$oldPhoto = false;
					if (array_key_exists('error', $photoId)) {
						$errors .= $photoId['error'].PHP_EOL;
						$errorCounter++;
						continue;
					}
					// mg::loger('new photo');
				}
				$marketItemId = $sqlRes['vk_id'];

				usleep(400000);
				$url = 'https://api.vk.com/method/market.edit';

				$fields = array(
					'owner_id' => '-'.$options["vkGroupId"],
					'item_id' => $sqlRes['vk_id'],
					'name' => $title,
					'description' => $descr,
					'category_id' => $vkCat,
					'price' => $price,
					'main_photo_id' => $photoId['id'],
					'deleted' => 0,
					'v' => '5.73',
					'access_token' => $access_token);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$res = json_decode(curl_exec($ch), true);
				curl_close($ch);

				// mg::loger('product res-update');
				// mg::loger($res);

				if ($res['response'] != 1) {

					if ($oldPhoto) {
						$photoId = self::dumpPhoto($product['image_url'], $options, $access_token, $title);
						if (array_key_exists('error', $photoId)) {
							$errors .= $photoId['error'].PHP_EOL;
							$errorCounter++;
							continue;
						}
					}

					usleep(400000);
					$url = 'https://api.vk.com/method/market.add';

					$fields = array(
						'owner_id' => '-'.$options["vkGroupId"],
						'name' => $title,
						'description' => $descr,
						'category_id' => $vkCat,
						'price' => $price,
						'main_photo_id' => $photoId['id'],
						'v' => '5.73',
						'access_token' => $access_token);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
					$res = json_decode(curl_exec($ch), true);
					curl_close($ch);

					// mg::loger('product re--res');
					// mg::loger($res);

					$marketItemId = $res['response']['market_item_id'];

					if (!empty($res['response']['market_item_id'])) {
						$marketItemId = $res['response']['market_item_id'];
					}
					else{
						$errors .= 'Ошибка при загрузке товара "'.$title.'".'.PHP_EOL;
						$errorCounter++;
						continue;
					}
				}

				DB::query("UPDATE `".PREFIX."vk-export` SET `vk_id` = ".DB::quote($marketItemId).", `moguta_img` = ".DB::quote($product['image_url']).", `vk_img` = ".DB::quote($photoId['id'])." WHERE `moguta_id` = ".DB::quote($currentProductId));

				if ((int)$vkAlbum > 0) {
					// mg::loger('add to album-update');
					usleep(400000);
					$rez = json_decode(file_get_contents('https://api.vk.com/method/market.addToAlbum?v=5.73&owner_id=-'.$options["vkGroupId"].
						'&item_id='.$marketItemId.
						'&album_ids='.$vkAlbum.
						'&access_token='.$access_token),true);
					// mg::loger($rez);
				}
			}
			else{//новый товар
				$photoId = self::dumpPhoto($product['image_url'], $options, $access_token, $title);
				if (array_key_exists('error', $photoId)) {
					$errors .= $photoId['error'].PHP_EOL;
					$errorCounter++;
					continue;
				}
				usleep(400000);
				$url = 'https://api.vk.com/method/market.add';

				$fields = array(
					'owner_id' => '-'.$options["vkGroupId"],
					'name' => $title,
					'description' => $descr,
					'category_id' => $vkCat,
					'price' => $price,
					'v' => '5.73',
					'main_photo_id' => $photoId['id'],
					'access_token' => $access_token);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$res = json_decode(curl_exec($ch), true);
				curl_close($ch);

				// mg::loger('product res');
				// mg::loger($res);

				$marketItemId = $res['response']['market_item_id'];

				if (!empty($res['response']['market_item_id'])) {
					$marketItemId = $res['response']['market_item_id'];
				}
				else{
					$errors .= 'Ошибка при загрузке товара "'.$title.'".'.PHP_EOL;
					$errorCounter++;
					continue;
				}

				if ((int)$vkAlbum > 0) {
					// mg::loger('add to album');
					usleep(400000);
					$rez = json_decode(file_get_contents('https://api.vk.com/method/market.addToAlbum?v=5.73&owner_id=-'.$options["vkGroupId"].
						'&item_id='.$marketItemId.
						'&album_ids='.$vkAlbum.
						'&access_token='.$access_token),true);
					// mg::loger($rez);
				}

				DB::query("INSERT INTO `".PREFIX."vk-export` (`moguta_id`, `vk_id`, `moguta_img`, `vk_img`) 
					VALUES (".DB::quote($currentProductId).", ".DB::quote($marketItemId).", ".DB::quote($product['image_url']).", ".DB::quote($photoId['id']).")");
			}
		}	
		file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));
		$result = array('remaining' => count($productIDs), 'errors' => $errors);
		return $result;
	}
   /**
	* Выгружает фото товара.
	* @param string $path путь до фото товара
	* @param array $options массив с настройками
	* @param string $access_token токен подключения
	* @param string $title название товара для текста ошибки
	* @return array массив с ID загруженного фото или ошибкой
	*/
	static function dumpPhoto($path, $options, $access_token, $title){
		$dropFile = false;
		usleep(400000);
		$result = json_decode(file_get_contents('https://api.vk.com/method/photos.getMarketUploadServer?v=5.73&group_id='.$options["vkGroupId"].'&main_photo=1&access_token='.$access_token),true);

		if (!empty($result['response']['upload_url'])) {
			$url = $result['response']['upload_url'];
		}
		else{
			// mg::loger('Ошибка при получении ссылки для изображения товара "'.$title);
			// mg::loger($result);
			return array('error' => 'Ошибка при получении ссылки для изображения товара "'.$title.'".'.PHP_EOL);
		}

		$file = URL::getDocumentRoot().'uploads'.DIRECTORY_SEPARATOR.$path;
		$basename = basename($file);

		if (function_exists("finfo_file")) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
		}
		else if (function_exists("mime_content_type")) {
			$mime = mime_content_type($file);
		}
		else if (function_exists("image_type_to_mime_type") && function_exists("exif_imagetype")) {
			$mime = image_type_to_mime_type(exif_imagetype($file));
		}
		else{
			$tmp = explode('.', $file);
			$mime = strtolower('image/'.array_pop($tmp));
		}

		if ($mime == 'image/jpg') {
			$mime = 'image/jpeg';
		}

		if ($mime == 'image/jpeg' || $mime == 'image/png' || $mime == 'image/gif') {

			$data = getimagesize($file);
			$oldWidth = $data[0];
			$oldHeight = $data[1];
			
			if ($oldWidth < 400 || $oldHeight < 400) {
				$scale = max(400/$oldWidth, 400/$oldHeight);
				$newWidth  = ceil($scale*$oldWidth);
				if ($newWidth < 400) {
					$newWidth = 400;
				}
				$newHeight = ceil($scale*$oldHeight);
				if ($newHeight < 400) {
					$newHeight = 400;
				}

				$savePath = URL::getDocumentRoot().'uploads'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR;

				$new = imagecreatetruecolor($newWidth, $newHeight);
				$whiteBackground = imagecolorallocate($new, 255, 255, 255); 
				imagefill($new,0,0,$whiteBackground);
				imageAlphaBlending($new, false);
				imageSaveAlpha($new, true);

				switch ($mime) {
					case 'image/jpeg':
						$image = imagecreatefromjpeg($file);
						imagecopyresampled($new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);
						$file = $savePath.'vktemp.jpeg';
						imagejpeg($new, $file, 90);
						
						break;
					case 'image/png':
						$image = imagecreatefrompng($file);
						imagecopyresampled($new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);
						$file = $savePath.'vktemp.png';
						imagepng($new, $file);
						break;
					case 'image/gif':
						$image = imagecreatefromgif($file);
						imagecopyresampled($new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);
						$file = $savePath.'vktemp.gif';
						imagegif($new, $file);
						break;
				}
				$basename = basename($file);
				$dropFile = true;

				// mg::loger('upscaled from '.$oldWidth.'x'.$oldHeight.'px to '.$newWidth.'x'.$newHeight.'px');
			}


			if ((version_compare(PHP_VERSION, '5.5') >= 0)) {
				$cfile['file'] = new CURLFile($file, $mime, $basename);
			} 
			else {
				$cfile['file'] = curl_file_create($file, $mime, $basename);
			}

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $cfile);
			$result = json_decode(curl_exec($ch), true);
			curl_close($ch);

			// mg::loger('$result');
			// mg::loger($result);

			if ($dropFile) {
				unlink($file);
				// mg::loger('unlinking....');
			}

			if (array_key_exists('error', $result)) {
				return array('error' => 'Ошибка при выгрузке изображения товара "'.$title.'".'.PHP_EOL);
			}
			else{
				$result['photo'] = stripslashes($result['photo']);
				usleep(400000);
				$result2 = json_decode(file_get_contents('https://api.vk.com/method/photos.saveMarketPhoto?v=5.73&group_id='.$options["vkGroupId"].
					'&photo='.$result['photo'].
					'&server='.$result['server'].
					'&hash='.$result['hash'].
					'&crop_data='.$result['crop_data'].
					'&crop_hash='.$result['crop_hash'].
					'&access_token='.$access_token),true);

				// mg::loger('$result2');
				// mg::loger($result2);

				if (!empty($result2['response'][0]['id'])) {
					return array('id' => $result2['response'][0]['id']);
				}
				else{
					return array('error' => 'Ошибка при сохранении изображения товара "'.$title.'".'.PHP_EOL);
				}

			}
		}
		else{
			return array('error' => 'Формат изображения товара "'.$title.'" не поддерживается - поддерживаются форматы JPG, PNG, GIF.'.PHP_EOL);
		}
	}
   /**
	* Получает ID товаров магазина для удаления из VKontakte.
	* @param array $shopCats массив категорий магазина
	* @return array массив с ID товаров магазина и количеством найденых товаров
	*/
	static function getNumDelete($shopCats, $useAdditionalCats){

		foreach ($shopCats as $key => $value) {
			$shopCats[$key] = (int)$value;
		}

		$shopCats = array_filter($shopCats);
		// $cats = implode(', ', $shopCats);
		$productIDs = array();

		$filter = "(p.cat_id IN (".DB::quoteIn($shopCats).") ";

		if ($useAdditionalCats == 'true') {
			foreach ($shopCats as $cat) {
				$filter .= ' OR FIND_IN_SET('.DB::quote($cat).', p.inside_cat)';
			}							
		}

		$filter .= ')';

		$rows = DB::query("SELECT * FROM `".PREFIX."vk-export` as vk
			LEFT JOIN `".PREFIX."product` as p
				ON  vk.moguta_id = p.id
			WHERE ".$filter);
		while ($row = DB::fetchAssoc($rows)) {
			$productIDs[$row['vk_id']] = $row['title'];
		}

		// mg::loger('delete ids');
		// mg::loger($productIDs);
		file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));

		$result = array('productsCount' => count($productIDs));
		return $result;
	}
   /**
	* Удаляет товары магазина из VKontakte.
	* @param string $access_token токен подключения
	* @param array $productIDs ID товаров для удаления
	* @return array массив с оставшимеся ID товаров для удаления, количеством товаров для удаления и ошибками
	*/
	static function delete($access_token){
		$options = unserialize(stripslashes(MG::getSetting('vkUpload')));
		$productIDs = unserialize(file_get_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt'));

		$timeHave = 10;
		$timerSave = microtime(true);
		$errorCounter = 0;
		$errors = '';

		while (count($productIDs) > 0) {

			$currentProductId = $currentProductTitle = NULL;
			foreach ($productIDs as $currentProductId => $currentProductTitle) {
				break;
			}

			unset($productIDs[$currentProductId]);
			usleep(400000);

			$result = json_decode(file_get_contents('https://api.vk.com/method/market.delete?v=5.73&owner_id=-'.$options["vkGroupId"].
				'&item_id='.$currentProductId.
				'&access_token='.$access_token),true);

			// mg::loger('delete rez');
			// mg::loger($result);

			if ($result['response'] == 1) {
				DB::query("DELETE FROM `".PREFIX."vk-export` WHERE `vk_id` = ".DB::quote($currentProductId));
			}
			else{
				$errors .= 'Ошибка при удалении товара "'.$currentProductTitle.'".'.PHP_EOL;
				$errorCounter++;
			}

			$timeHave -= microtime(true) - $timerSave;
			$timerSave = microtime(true);
			if($timeHave < 0 || $errorCounter > 10) {
				file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));
				$result = array('remaining' => count($productIDs), 'errors' => $errors);
				return $result;
			}
			
		}		
		file_put_contents(SITE_DIR.'mg-admin'.DIRECTORY_SEPARATOR.'section'.DIRECTORY_SEPARATOR.'controlers'.DIRECTORY_SEPARATOR.'vktemp.txt', serialize($productIDs));
		$result = array('remaining' => count($productIDs), 'errors' => $errors);
		return $result;
	}
}

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            . ($postname ?: basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}

?>