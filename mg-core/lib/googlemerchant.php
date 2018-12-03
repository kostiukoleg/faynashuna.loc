<?php
/**
 * Класс GoogleMerchant используется для создания и редактирования выгрузок для Google Merchant
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class GoogleMerchant {
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {

		$rows = DB::query("SELECT COUNT(id) FROM `".PREFIX."googlemerchantcats`;");
		$res = DB::fetchAssoc($rows);
		//MG::loger($res['COUNT(id)']);
		$databaseError = false;

		if ($res['COUNT(id)'] != 5428) {
			$databaseError = true;
		}
		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';

		$rows = DB::query("SELECT `name` FROM `".PREFIX."googlemerchant` ORDER BY `edited` DESC");
		while ($row = DB::fetchAssoc($rows)) {
			$names[] = $row['name'];
		}

		// $rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` ORDER BY `sort` asc");
		// while ($row = DB::fetchAssoc($rows)) {
		// 	$props[$row['id']] = $row['name'];
		// }


		$array = array();
		$res = DB::query('SELECT DISTINCT * FROM '.PREFIX.'category WHERE parent = 0 GROUP BY sort ASC');
		while($row = DB::fetchAssoc($res)) {
			$array[] = $row;
		}

		// необходимо для корректного вывода таблицы
		$_SESSION['categoryCountToAdmin'] = 0;
		$categoryList = Category::getPagesSimple($array, 0, 0);
		if($categoryList == '') {
			$categoryList = '<tr><td colspan="6" style="text-align:center;">Категории не найдены</td></tr>';
		}
		unset($_SESSION['categoryCountToAdmin']);

		$rows = DB::query("SELECT `title` FROM `".PREFIX."product` WHERE 1=1 LIMIT 0,1");
		while ($row = DB::fetchAssoc($rows)) {
			$exampleName = $row['title'];
		}

		echo '<script type="text/javascript">
			includeJS("'.SITE.'/mg-core/script/admin/category.js")
			</script>';
		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Записывает категории google в базу данных
	* @return bool
	*/
	static function updateDB(){
		$ds = DIRECTORY_SEPARATOR;
		DB::query("TRUNCATE TABLE `".PREFIX."googlemerchantcats`;");
		$dir = URL::getDocumentRoot();

		for ($i=1; $i < 6; $i++) { 
			$values = file_get_contents('zip://'.$dir.'mg-admin'.$ds.'section'.$ds.'views'.$ds.'integrations'.$ds.'google_merchant_cats.zip#part'.$i.'.txt');
			DB::query("INSERT INTO `".PREFIX."googlemerchantcats` (`id`, `name`, `parent_id`) VALUES ".$values.";");
		}

		return true;
	}
   /**
	* Возвращает название google категории по ID.
	* @param string $id ID google категории
	* @return string название google категории
	*/
	static function getCatName($id) {
		
		$res = DB::query("SELECT `name` FROM `".PREFIX."googlemerchantcats` WHERE `id` = ".DB::quote((int)$id));
		$row = DB::fetchAssoc($res);
		return $row['name'];
	}
   /**
	* Возвращает верстку для выбора google категорий по ID родительской категории.
	* @param string $id ID google категории
	* @return array массив с версткой селектов и ID возможных выборов
	*/
	static function buildSelects($id){

		$res = DB::query("SELECT `parent_id` FROM `".PREFIX."googlemerchantcats` WHERE `id` = ".DB::quote((int)$id));
		$row = DB::fetchAssoc($res);
		$parentId = $row['parent_id'];
		$parentsArray = array();
		$html = '';

		if ($parentId != 0) {
			array_push($parentsArray, $parentId);
		}

		while ($parentId != 0) {//иерархия выборов

			$res = DB::query("SELECT `parent_id` FROM `".PREFIX."googlemerchantcats` WHERE `id` = ".DB::quote((int)$parentId));
			$row = DB::fetchAssoc($res);
			$parentId = $row['parent_id'];

			if ($parentId != 0) {
				array_unshift($parentsArray, $parentId);
			}
		}

		//базовый селект
		$res = DB::query("SELECT `id`, `name`
			FROM `".PREFIX."googlemerchantcats` 
			WHERE `parent_id` = 0");
		$html .= '<select class="customCatSelect">';
		$html .= '<option value="-5">Не выбрано</option>';
		while ($row = DB::fetchAssoc($res)) {
			if ($row['id'] > 0) {
				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}			
		}
		$html .= '</select>';

		foreach ($parentsArray as $key => $value) {//вторичные селекты

			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."googlemerchantcats` 
				WHERE `parent_id` = ".DB::quote((int)$value));
			$html .= '<select class="customCatSelect">';
			$html .= '<option value="-5">Не выбрано</option>';

			while ($row = DB::fetchAssoc($res)) {


				$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$html .= '</select>';
			
		}

		if ($id > 0) {//следующий селект
			$res = DB::query("SELECT `id`, `name`
				FROM `".PREFIX."googlemerchantcats` 
				WHERE `parent_id` = ".DB::quote((int)$id));

			if ($res->num_rows > 0) {
				$html .= '<select class="customCatSelect">';
				$html .= '<option value="-5">Не выбрано</option>';

				while ($row = DB::fetchAssoc($res)) {

					$html .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
				}
				$html .= '</select>';
			}
			
		}

		array_push($parentsArray, $id);
		$data = array('html' => $html, 'choices' => $parentsArray);

		return $data;

	}
   /**
	* Возвращает список соответствий google категорий и категорий магазина по названию выгрузки.
	* @param string $name название выгрузки
	* @return array массив соответствий ID категорий
	*/
	static function getCats($name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['cats'];
		}

		$cats = unserialize(stripslashes($res));
		return $cats;
	}
   /**
	* Применяет соответствующую google категорию ко всем вложенным категориям магазина.
	* @param string $shopId ID категории магазина
	* @param string $googleId ID категории google
	* @param string $name название выгрузки
	* @return bool
	*/
	static function updateCatsRecurs($shopId, $googleId, $name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['cats'];
		}

		$cats = unserialize(stripslashes($res));
		$model = new Category;
		$catIds = $model->getCategoryList($shopId);

		foreach ($catIds as $key => $value) {
			$cats[$value] = $googleId;
		}
		$cats = addslashes(serialize($cats));

		DB::query("UPDATE `".PREFIX."googlemerchant` SET `cats`=".DB::quote($cats)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Сохраняет соответствие google категории и категории магазина.
	* @param string $shopId ID категории магазина
	* @param string $googleId ID категории google
	* @param string $name название выгрузки
	* @return bool
	*/
	static function saveCat($shopId, $googleId, $name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['cats'];
		}
		$cats = unserialize(stripslashes($res));
		$cats[$shopId] = $googleId;
		$cats = addslashes(serialize($cats));

		DB::query("UPDATE `".PREFIX."googlemerchant` SET `cats`=".DB::quote($cats)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Убирает повторы из соответствий категорий.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function clearTrash($name){
		$rows = DB::query("SELECT `cats` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['cats'];
		}
		$cats = unserialize(stripslashes($res));
		$cats = array_filter($cats);
		$cats = addslashes(serialize($cats));

		DB::query("UPDATE `".PREFIX."googlemerchant` SET `cats`=".DB::quote($cats)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Создает новую выгрузку.
	* @param string $name название выгрузки
	* @return string|bool название выгрузки, если оно уникально или false если повторяется
	*/
	static function newTab($name) {
		$dbRes = DB::query("SELECT `name` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));

		if(!$row = DB::fetchArray($dbRes)) {
			DB::query("INSERT IGNORE INTO  `".PREFIX."googlemerchant` (`name`) VALUES (".DB::quote($name).")");
			return $name;
		}

		return false;
	}
   /**
	* Сохраняет настройки выгрузки.
	* @param string $name название выгрузки
	* @param array $data массив с данными для сохранения
	* @return bool
	*/
	static function saveTab($name, $data) {
		$data = addslashes(serialize($data));

		DB::query("UPDATE `".PREFIX."googlemerchant` SET `settings`=".DB::quote($data)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Получает настройки выгрузки.
	* @param string $name название выгрузки
	* @return array массив с данными выгрузки
	*/
	static function getTab($name) {
		$rows = DB::query("SELECT `settings` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		$options = unserialize(stripslashes($res));
		
		$options['ignoreProducts'] = self::getRelated($options['ignoreProducts']);

		return $options;
	}
   /**
	* Удаляет выгрузку.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function deleteTab($name) {
		DB::query("DELETE FROM `".PREFIX."googlemerchant` WHERE `name`=".DB::quote($name));
		
		return true;
	}
   /**
	* Возвращает данные игнорируемых товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых товаров
	*/
	static function getRelated($option) {
		$stringRelated = ' null';
	    $sortRelated = array();
	    if (!empty($option)) {
	      foreach (explode(',', $option) as $item) {
	        $stringRelated .= ','.DB::quote($item);
	        if (!empty($item)) {
	          $sortRelated[$item] = $item;
	        }
	      }
	      $stringRelated = substr($stringRelated, 1);
	    }

	    $res = DB::query('
	      SELECT  CONCAT(c.parent_url,c.url) as category_url,
	        p.url as product_url, p.id, p.image_url,p.price_course as price,p.title,p.code
	      FROM `'.PREFIX.'product` p
	        LEFT JOIN `'.PREFIX.'category` c
	        ON c.id = p.cat_id
	        LEFT JOIN `'.PREFIX.'product_variant` AS pv
	        ON pv.product_id = p.id
	      WHERE p.code IN ('.$stringRelated.') OR pv.code IN ('.$stringRelated.')');

	    while ($row = DB::fetchAssoc($res)) {
	      $img = explode('|', $row['image_url']);
	      $row['image_url'] = $img[0];
	      $sortRelated[$row['code']] = $row;
	    }
	    $productsRelated = array();

	    if (!empty($sortRelated)) {
	      foreach ($sortRelated as $item) {
	        if (is_array($item)) {
	          $item['image_url'] = mgImageProductPath($item['image_url'], $item['id'], 'small');
	          $productsRelated[] = $item;
	        }
	      }
	    }
	    
	    return $productsRelated;
	}
   /**
	* Создает результат выгрузки.
	* @param string $name название выгрузки
	* @return string результат выгрузки
	*/
	static function constructXML($name){

		$rows = DB::query("SELECT `settings` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		if (strlen($res) > 1) {

			$ds = DIRECTORY_SEPARATOR;
			$options = unserialize(stripslashes($res));
			$rows = DB::query("SELECT `cats` FROM `".PREFIX."googlemerchant` WHERE `name` = ".DB::quote($name));
			while ($row = DB::fetchAssoc($rows)) {
				$res = $row['cats'];
			}
			$cats = unserialize(stripslashes($res));
			$cats = array_filter($cats);

			$currencies = MG::getSetting('currencyRate');

		 	$RSS = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;

			$xml = new XMLWriter();
			$xml->openMemory();
			$xml->setIndent(true);
			$xml->startElement('rss');
			$xml->writeAttribute('version', '2.0');
			$xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
			$xml->startElement('channel');

			$xml->startElement('title');
			if (strlen($options['rssName']) > 0) {
				$xml->text($options['rssName']);
			}
			else{
				$xml->text($name);
			}
			$xml->endElement();

			$xml->startElement('description');
			if (strlen($options['rssDesc']) > 0) {
				$xml->text($options['rssDesc']);
			}
			else{
				$xml->text($name);
			}
			$xml->endElement();

			$xml->startElement('link');
			$xml->text(SITE);
			$xml->endElement();

			$filter = 'WHERE `cat_id` IN (';

			foreach ($cats as $key => $value) {
				$filter .= DB::quote((int)$key).', ';
			}
			$filter = substr($filter, 0, -2);
			$filter .= ') ';

			if (!empty($options['ignoreProducts'])) {
				$filter .= 'AND (';
				$ignored = explode(',', $options['ignoreProducts']);

				foreach ($ignored as $key => $value) {
					$filter .=" (`code` != ".DB::quote($value).") AND";
				}

				$filter = substr($filter, 0, -3);
				$filter .= ')';
			}

			if ($options['inactiveToo'] == "false") {
				$filter .= ' AND `activity` = 1';
			}

			$model = new Models_Product;

			$res = DB::query("SELECT `id`, `code`, `count` FROM `".PREFIX."product` ".$filter);
			while ($row = DB::fetchAssoc($res)) {

				set_time_limit(30);
				// if($row['count'] == 0 && $options['useNull'] == 'false' && $options['useVariants'] == 'false') {continue;}

				$product = $model->getProduct($row['id']);
				$variants = $model->getVariants($row['id']);

				if (empty($variants) || $options['useVariants'] == 'false') {
					$printMain = true;
				}
				else{
					$printMain = false;
				}

				if ($printMain && $product['count'] == 0 && $options['useNull'] == 'false') {
					continue;
				}

				if (!$printMain && $options['useNull'] == 'false') {
					$continue = true;
					foreach ($variants as $var) {
						if ($var['count'] != 0) {
							$continue = false;
							break;
						}
					}
					if ($continue) {
						continue;
					}
				}

				$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
				$product['description'] = strip_tags($product['description']);
				$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
				$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
				$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
				$product['description'] = str_replace(array("~", "<", ">"), "", $product['description']);
				$product['description'] = strip_tags($product['description']);
				$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
				$product['description'] = mb_substr(trim($product['description']), 0,4975, 'utf-8');

				if ($printMain) {

					$xml->startElement('item');
					$codde = $product['id'];
					$xml->startElement('g:id');
					$xml->text($codde);
					$xml->endElement();


					if ($options['useVariants'] == 'true') {
						$variantz = array_values($variants);
						$xml->startElement('g:title');
						$xml->text($product['title'].' '.$variantz[0]['title_variant']);
						$xml->endElement();
					}
					else{
						$xml->startElement('g:title');
						$xml->text($product['title']);
						$xml->endElement();
					}

					if (strlen($product['description']) > 0) {
						$xml->startElement('g:description');
						$xml->text($product['description']);
						$xml->endElement();
					}
					if ($product['currency_iso'] == 'RUR') {
						$product['currency_iso'] = 'RUB';
					}

					$xml->startElement('g:link');
					$xml->text(SITE.$ds.$product['category_url'].$ds.$product['url']);
					$xml->endElement();

					$i=0;
					foreach ($product['images_product'] as $key => $value) {
						if ($i==0) {
							$xml->startElement('g:image_link');
							$xml->text(SITE.$ds.'uploads'.$ds.$value);
							$xml->endElement();
						}
						if ($i>0 && $i < 11) {
							$xml->startElement('g:additional_image_link');
							$xml->text(SITE.$ds.'uploads'.$ds.$value);
							$xml->endElement();
						}
						$i++;
					}

					$xml->startElement('g:condition');
					switch ($options['condition']) {
						case 'used':
							$xml->text('used');
							break;
						case 'repaired':
							$xml->text('refurbished');
							break;
						default:
							$xml->text('new');
							break;
					}
					$xml->endElement();

					$xml->startElement('g:availability');
					switch ($product['count']) {
						case 0:
							$xml->text('out of stock');
							break;
						default:
							$xml->text('in stock');
							break;
					}
					$xml->endElement();

					$prodRate = 1 + (float)$product['rate'];

					if ($options['useOldPrice'] == "true" && $product['price'] < $product['old_price']){

						$xml->startElement('g:price');
						$xml->text(max(round($product['price']*$prodRate,2), round($product['old_price']*$prodRate),2).' '.$product['currency_iso']);
						$xml->endElement();

						$xml->startElement('g:sale_price');
						$xml->text(min(round($product['old_price']*$prodRate,2), round($product['price']*$prodRate),2).' '.$product['currency_iso']);
						$xml->endElement();
					}
					else{
						$xml->startElement('g:price');
						$xml->text(round($product['price']*$prodRate,2).' '.$product['currency_iso']);
						$xml->endElement();
					}

					$xml->startElement('g:identifier_exists');
					$xml->text('no');
					$xml->endElement();

					$xml->startElement('g:google_product_category');
					$xml->text($cats[$product['cat_id']]);
					$xml->endElement();
					$xml->endElement();
				}

				if ($options['useVariants'] == 'true') {////////////////////////////////////////  start variants
					// for ($i=1; $i < count($variants); $i++) { 
					foreach ($variants as $variant) {
						if(($variant['count'] == 0 && $options['useNull'] == 'false') || (in_array($variant['code'], $ignored))) {continue;}

						$xml->startElement('item');////////////////////////////////////////////  start offer
						$codde = $product['id'].'V'.$variant['id'];
						$xml->startElement('g:id');
						$xml->text($codde);
						$xml->endElement();

						$xml->startElement('g:title');
						$xml->text($product['title'].' '.$variant['title_variant']);
						$xml->endElement();

						if (strlen($product['description']) > 0) {
							$xml->startElement('g:description');
							$xml->text($product['description']);
							$xml->endElement();
						}

						$xml->startElement('g:link');
						$xml->text(SITE.$ds.$product['category_url'].$ds.$product['url']);
						$xml->endElement();

						if (strlen($variant['image']) > 0) {
							$j=1;
							$folder = floor($row['id']/100)*100;
							if ($folder == 0) {$folder = '000';}
							$imgPath = SITE.$ds.'uploads'.$ds.'product'.$ds.$folder.$ds.$row['id'].$ds.$variant['image']; 
							$xml->startElement('g:image_link');
							$xml->text($imgPath);
							$xml->endElement();
						}
						else{
							$j=0;
						}

						foreach ($product['images_product'] as $key => $value) {
							if ($j==0) {
								$xml->startElement('g:image_link');
								$xml->text(SITE.$ds.'uploads'.$ds.$value);
								$xml->endElement();
							}
							if ($j>0 && $j < 11) {
								$xml->startElement('g:additional_image_link');
								$xml->text(SITE.$ds.'uploads'.$ds.$value);
								$xml->endElement();
							}
							$j++;
						}

						$xml->startElement('g:condition');
						switch ($options['condition']) {
							case 'used':
								$xml->text('used');
								break;
							case 'repaired':
								$xml->text('refurbished');
								break;
							default:
								$xml->text('new');
								break;
						}
						$xml->endElement();

						$xml->startElement('g:availability');
						switch ($variant['count']) {
							case 0:
								$xml->text('out of stock');
								break;
							default:
								$xml->text('in stock');
								break;
						}
						$xml->endElement();

						if ($variant['currency_iso'] == 'RUR') {
							$variant['currency_iso'] = 'RUB';
						}

						$varRate = 1 + (float)$variant['rate'];

						if ($options['useOldPrice'] == "true" && $variant['price'] < $variant['old_price']){

							$xml->startElement('g:price');
							$xml->text(max(round($variant['price']*$varRate,2), round($variant['old_price']*$varRate,2)).' '.$variant['currency_iso']);
							$xml->endElement();

							$xml->startElement('g:sale_price');
							$xml->text(min(round($variant['old_price']*$varRate,2), round($variant['price']*$varRate,2)).' '.$variant['currency_iso']);
							$xml->endElement();
						}
						else{
							$xml->startElement('g:price');
							$xml->text(round($variant['price']*$varRate,2).' '.$variant['currency_iso']);
							$xml->endElement();
						}

						$xml->startElement('g:identifier_exists');
						$xml->text('no');
						$xml->endElement();

						$xml->startElement('g:google_product_category');
						$xml->text($cats[$product['cat_id']]);
						$xml->endElement();
						$xml->endElement();/////////////////////////////////////////////  end offer
					}
				}///////////////////////////////////////////////////////////////////////  end variants
			}
			$xml->endElement();/////////////////////////////////////////////////////////  end channel
			$xml->endElement();/////////////////////////////////////////////////////////  end rss
			$RSS .= $xml->outputMemory();    
			return html_entity_decode($RSS);
		}
	}
}