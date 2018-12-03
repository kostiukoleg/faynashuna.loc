<?php
/**
 * Класс YandexMarket используется для создания и редактирования выгрузок для Yandex.Market
 *
 * @package moguta.cms
 * @subpackage Libraries
 */
class YandexMarket {
	static $nonRootCats = array();
	static $allIds = array();
   /**
	* Подготавливает данные для страницы интеграции
	*/
	static function createPage() {
		echo '<script src="'.SITE.'/mg-core/script/admin/integrations/'.pathinfo(__FILE__, PATHINFO_FILENAME).'.js"></script>';

		$rows = DB::query("SELECT `name` FROM `".PREFIX."yandexmarket` ORDER BY `edited` DESC");
		while ($row = DB::fetchAssoc($rows)) {
			$names[] = $row['name'];
		}

		$rows = DB::query("SELECT `id`, `name` FROM `".PREFIX."property` ORDER BY `sort` asc");
		while ($row = DB::fetchAssoc($rows)) {
			$props[$row['id']] = $row['name'];
		}

		$model = new Models_Catalog;
		$arrayCategories = $model->categoryId = MG::get('category')->getHierarchyCategory(0);
		$categoriesOptions = MG::get('category')->getTitleCategory($arrayCategories, URL::get('category_id'));

		$rows = DB::query("SELECT `title` FROM `".PREFIX."product` ORDER BY RAND() LIMIT 1");
		while ($row = DB::fetchAssoc($rows)) {
			$exampleName = $row['title'];
		}

		include('mg-admin/section/views/integrations/'.basename(__FILE__));
	}
   /**
	* Создает новую выгрузку.
	* @param string $name название выгрузки
	* @return string|bool название выгрузки, если оно уникально или false если повторяется
	*/
	static function newTab($name) {
		$dbRes = DB::query("SELECT `name` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));

		if(!$row = DB::fetchArray($dbRes)) {
			DB::query("INSERT IGNORE INTO  `".PREFIX."yandexmarket` (`name`) VALUES (".DB::quote($name).")");
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

		DB::query("UPDATE `".PREFIX."yandexmarket` SET `settings`=".DB::quote($data)." WHERE `name`=".DB::quote($name));

		return true;
	}
   /**
	* Получает настройки выгрузки.
	* @param string $name название выгрузки
	* @return array массив с данными выгрузки
	*/
	static function getTab($name) {

		$rows = DB::query("SELECT `settings` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		$options = unserialize(stripslashes($res));
		$options['ignoreProducts'] = self::getRelated($options['ignoreProducts']);
		$options['addProducts'] = self::getRelated($options['addProducts']);

		return $options;
	}
   /**
	* Удаляет выгрузку.
	* @param string $name название выгрузки
	* @return bool
	*/
	static function deleteTab($name) {
		DB::query("DELETE FROM `".PREFIX."yandexmarket` WHERE `name`=".DB::quote($name));
		
		return true;
	}
   /**
	* Удаляет из URL все запрещенные спецсимволы, заменяет пробелы на тире.
	* @param string $str строка для операции
	* @return string
	*/
	static function prepareUrl($str) {

		$str = strtolower($str);
		$str = preg_replace('%\s%i', '-', $str);
		$str = str_replace('`', '', $str);
		$str = str_replace(array("\\","<",">"),"",$str);    
		$str = preg_replace('%[^/-a-zа-я#\.\d]%iu', '', $str);
		return $str;
	}
	
   /**
	* Возвращает данные игнорируемых или дополнительных товаров.
	* @param string $option артикулы товаров
	* @return array массив с данными игнорируемых или дополнительных товаров
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
	* Получение ID товаров по их артикулам
	* @param array $arr артикулы товаров
	* @return array массив ID товаров
	*/
	static function getIdByCode($arr) {

		if (!empty($arr)) {

			foreach ($arr as $key => $value) {
				$arr[$key] = DB::quote($value);
			}

			$databaseres = DB::query('SELECT `id` FROM `'.PREFIX.'product`  WHERE `code` IN ('.implode(', ', $arr).')');
			$arr = array();
			while ($databaserow = DB::fetchAssoc($databaseres)) {
				$arr[] = $databaserow['id'];
			}
		}
		return $arr;
	}
   /**
	* Приводит характеристики к нормальному виду
	* @param array $thisUserFields массив с характеристиками
	* @return array массив с характеристиками
	*/
	static function convertProps($thisUserFields) {
		$result = array();
		if (!empty($thisUserFields)) {
			foreach ($thisUserFields as $key => $value) {
				$tmp = array();

				$name = explode('[', $value['name']);
				$name = $name[0];

				if (strlen($name) < 1) {continue;}

				switch ($value['type']) {
					case 'string':
						if (strlen($value['data'][0]['name']) < 1) {break;}
						$result['string'][$value['prop_id']] = array(
							'id' => $value['prop_id'],
							'name' => $name,
							'unit' => $value['unit'],
							'active' => $value['activity'],
							'value' => $value['data'][0]['name']
						);
						break;
					
					case 'assortmentCheckBox':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[] = $v['name'];
							}
						}
						if (count($tmp) > 0) {
							$result['string'][$value['prop_id']] = array(
								'id' => $value['prop_id'],
								'name' => $name,
								'unit' => $value['unit'],
								'active' => $value['activity'],
								'value' => implode(', ', $tmp)
							);
						}
						break;
					
					case 'color':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'active' => $value['activity'],
									'value' => $v['name']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['color'] = $tmp;
						}
						break;
					
					case 'size':
						foreach ($value['data'] as $k => $v) {
							if ($v['active'] > 0 && strlen($v['name']) > 0) {
								$tmp[$v['prop_data_id']] = array(
									'id' => $value['prop_id'],
									'name' => $name,
									'value' => $v['name'],
									'unit' => $value['unit'],
									'active' => $value['activity']
								);
							}
						}
						if (count($tmp) > 0) {
							$result['size'] = $tmp;
						}
						break;
					default:
						# code...
						break;
				}
			}
			// mg::loger($result);
			return $result;
		}
		return false;
	}
	static function buildCats($xml, $catsTmp, $children){
		foreach ($children as $child) {
			$xml->startElement('category');
			$xml->writeAttribute('id', $catsTmp[$child]['id']);
			if (in_array($catsTmp[$child]['parent'], self::$allIds)) {
				$xml->writeAttribute('parentId', $catsTmp[$child]['parent']);
			}
			$xml->text($catsTmp[$child]['title']);
			$xml->endElement();
			unset(self::$nonRootCats[$catsTmp[$child]['id']]);
			if (!empty($catsTmp[$child]['children'])) {
				self::buildCats($xml, $catsTmp, $catsTmp[$child]['children']);
			}
		}
	}
   /**
	* Создает результат выгрузки.
	* @param string $name название выгрузки
	* @return string результат выгрузки
	*/
	static function constructYML($name){

		$rows = DB::query("SELECT `settings` FROM `".PREFIX."yandexmarket` WHERE `name` = ".DB::quote($name));
		while ($row = DB::fetchAssoc($rows)) {
			$res = $row['settings'];
		}

		if (strlen($res) > 1) {
			$ds = DIRECTORY_SEPARATOR;
			$options = unserialize(stripslashes($res));
			$currencies = MG::getSetting('currencyRate');

			if (($options['uploadType'] == 'custom' || $options['uploadType'] == 'musicNvidio') && $options['skipName'] == 'true') {
				$skipName = true;
			}
			else{
				$skipName = false;
			}
			
		 	$YML = '<?xml version="1.0" encoding="UTF-8"?>';

			$xml = new XMLWriter();
			$xml->openMemory();
			$xml->setIndent(true);
			$xml->startElement('yml_catalog');
			$xml->writeAttribute('date', date('Y-m-d H:i'));
			$xml->startElement('shop');
			$xml->writeElement('name', MG::getSetting('sitename'));
			$xml->writeElement('company', $options['company']);
			$xml->writeElement('url', SITE);
			if($options['useMarket'] == 'true') {$xml->writeElement('cpa', '0');}
			$xml->startElement('currencies');///////////////////  start currencies

			$mainCurr = MG::getSetting('currencyShopIso');
			
			$xml->startElement('currency');
			$xml->writeAttribute('id', $mainCurr);
			$xml->writeAttribute('rate', round($currencies[$mainCurr],6));
			$xml->endElement();

			unset($currencies[$mainCurr]);
			foreach ($currencies as $key => $value) {
				if ($key != '' && (float)$value != 1) {
					$xml->startElement('currency');
					$xml->writeAttribute('id', $key);
					$xml->writeAttribute('rate', round($value,6));
					$xml->endElement();
				}
			}
			$xml->endElement();//////////////////////////////////  end currencies

			$xml->startElement('categories');////////////////////  start categories

			$addedCats = array();
			$or = '';
			if (!empty($options['addProducts']) && $options['catsType'] != 'all') {

				$res = DB::query("SELECT `cat_id` FROM `".PREFIX."product` WHERE `code` IN (".DB::quoteIN($options['addProducts']).")");
				while ($row = DB::fetchAssoc($res)) {
					if (!in_array($row['cat_id'], $options['catsSelect']) || $options['catsType'] == 'fromCats') {
						$addedCats[] = $row['cat_id'];
					}
				}

				if (!empty($addedCats)) {
					$or = implode(',', $addedCats);
					$or = ' OR `id` IN ('.DB::quoteIN($or).')';
				}
			}

			switch ($options['catsType']) {
				case 'fromCats':
					$filter = 'WHERE `export` = 1'.$or;
					break;
				case 'selected':
					$options['catsSelect'][] = '-1';
					$tmp = implode(',', $options['catsSelect']);
					$filter = 'WHERE `id` IN ('.DB::quoteIN($tmp).') '.$or;
					break;
				default:
					$filter = 'WHERE 1 = 1';
					break;
			}

			$catsForExport = $catsTmp = array();
			$res = DB::query("
				SELECT `id`, `parent`, `title`, `export` 
				FROM `".PREFIX."category`".$filter." ORDER BY `sort` ASC");
			while ($row = DB::fetchAssoc($res)) {
				if ($options['catsType'] == 'fromCats' && $row['export'] == 1) {
					array_push($catsForExport, $row['id']);
				}
				$catsTmp[$row['id']] = array('id' => $row['id'], 'parent' => $row['parent'], 'title' => $row['title']);
			}

			foreach ($catsTmp as $key => $value) {
				if ($value['parent'] > 0 && !empty($catsTmp[$value['parent']])) {
					$catsTmp[$value['parent']]['children'][] = $value['id'];
				}
				self::$nonRootCats[$value['id']] = $value['id'];
			}
			self::$allIds = self::$nonRootCats;
			set_time_limit(30);
			foreach ($catsTmp as $key => $value) {
				if ($value['parent'] == 0) {
					$xml->startElement('category');
					$xml->writeAttribute('id', $value['id']);
					$xml->text($value['title']);
					$xml->endElement();
					unset(self::$nonRootCats[$value['id']]);

					if (!empty($value['children'])) {
						self::buildCats($xml, $catsTmp, $value['children']);
					}
				}
			}
			while (!empty(self::$nonRootCats)) {
				$tmp = array_shift(self::$nonRootCats);
				self::buildCats($xml, $catsTmp, array($tmp));
			}
			unset($catsTmp);
			self::$allIds = array();
			$xml->endElement();/////////////////////////////////////  end categories

			if (!empty($options['deliverys'])) {
				$xml->startElement('delivery-options');/////////////  start delivery-options
				foreach ($options['deliverys'] as $key => $value) {

					if (strlen($value['cost']) > 0) {
						$xml->startElement('option');
						$xml->writeAttribute('cost', $value['cost']);
						if (strlen($value['time']) > 0) {$xml->writeAttribute('days', $value['time']);}
						if (strlen($value['before']) > 0) {$xml->writeAttribute('order-before', $value['before']);}
						$xml->endElement();
					}
					
				}
				$xml->endElement();/////////////////////////////////  end delivery-options
			}

			$xml->startElement("offers");///////////////////////////  start offers

			$filter = 'WHERE ((';

			switch ($options['catsType']) {
				case 'fromCats':
					$tmp = implode(',', $catsForExport);
					$filter .= '`cat_id` IN ('.DB::quoteIN($tmp).'))';					
					break;
				case 'selected':
					$tmp = implode(',', $options['catsSelect']);
					$filter .= '`cat_id` IN ('.DB::quoteIN($tmp).'))';
					break;
				default:
					$filter .= '1 = 1)';
					break;
			}

			if ($options['useAdditionalCats'] == 'true') {
				switch ($options['catsType']) {
					case 'fromCats':
						foreach ($catsForExport as $key => $value) {
							$filter .= ' OR FIND_IN_SET('.DB::quote($value).', `inside_cat`)';
						}						
						break;
					case 'selected':
						foreach ($options['catsSelect'] as $key => $value) {
							$filter .= ' OR FIND_IN_SET('.DB::quote($value).', `inside_cat`)';
						}
						break;
					default:
						break;
				}
			}

			if (!empty($options['addProducts'])) {
				$filter .= ' OR `code` IN ('.DB::quoteIN($options['addProducts']).')';
			}
			$filter .= ')';

			if (!empty($options['ignoreProducts'])) {
				$filter .= ' AND `code` NOT IN ('.DB::quoteIN($options['ignoreProducts']).')';
			}

			if ($options['inactiveToo'] == "false") {
				$filter .= ' AND `activity` = 1';
			}

			// mg::loger("SELECT `id`, `code`, `count` FROM `".PREFIX."product` ".$filter);
			// exit;

			$model = new Models_Product;

			$res = DB::query("SELECT `id`, `code`, `count`, `inside_cat` FROM `".PREFIX."product` ".$filter);
			while ($row = DB::fetchAssoc($res)) {

				set_time_limit(30);

				$product = $model->getProduct($row['id']);

				$variants = $model->getVariants($row['id']);

				if (empty($variants) || $options['useVariants'] == 'false') {
					$printMain = true;
				}
				else{
					$printMain = false;
				}

				if ($printMain && $product['count'] == 0 && $options['useNull'] == 'false') {continue;}

				if ($printMain && $product['price'] == 0) {continue;}

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

				$product['thisUserFields'] = self::convertProps($product['thisUserFields']);

				$utm = array();
				if (!empty($options['customUtm'])) {
					foreach ($options['customUtm'] as $key => $value) {
						if ($value['type'] == 'text') {
							
							$tmp = $value['name'];
							if (strlen($value['val']) > 0) {
								$tmp .= '='.$value['val'];
							}
							$utm[] = $tmp;
						}

						if ($value['type'] == 'prop' && $product['thisUserFields']['string'][$value['val']]['value']) {
							$tmp = self::prepareUrl($product['thisUserFields']['string'][$value['val']]['value']);
							if (strlen($tmp) > 0) {
								$utm[] = $value['name'].'='.$tmp;
							}
						}

						if ($value['type'] == 'prop' && !is_numeric($value['val'])) {
							if ($value['val'] == 'catUrl') {
								$tmp = explode('/', $product['category_url']);
								$tmp = array_pop($tmp);
								if (strlen($tmp) > 0) {
									$utm[] = $value['name'].'='.$tmp;
								}
							}
							if ($value['val'] == 'prodUrl') {
								$utm[] = $value['name'].'='.$product['product_url'];
							}
						}
					}
				}

				if (!empty($utm)) {
					$utm = implode('&amp;', $utm);
					$utm = '?'.$utm;
				}
				else{
					$utm = '';
				}

				if (MG::getSetting('shortLink')=='true') {
					$url = SITE.'/'.$product['url'].$utm;
				}
				else {
					$url = SITE.'/'.(isset($product["category_url"]) ? $product["category_url"] : 'catalog').'/'.$product['url'].$utm;
				}

				if ($printMain) {
					$xml->startElement('offer');
					$codde = $product['id'];
					$xml->writeAttribute('id', $codde);
					if ($product['count'] == 0) {
						$xml->writeAttribute('available', 'false');
					}
				}
				switch ($options['uploadType']) {
					case 'custom':
						$typeUploading = 'vendor.model';
						break;
					case 'books':
						$typeUploading = 'book';
						break;
					case 'audiobooks':
						$typeUploading = 'audiobook';
						break;
					case 'musicNvidio':
						$typeUploading = 'artist.title';
						break;
					case 'medicine':
						$typeUploading = 'medicine';
						break;
					case 'tickets':
						$typeUploading = 'event-ticket';
						break;
					case 'tours':
						$typeUploading = 'tour';
						break;
					
					default:
						$typeUploading = '';
						break;
				}

				if (strlen($typeUploading) > 0) {
					if ($printMain) {
						$xml->writeAttribute('type', $typeUploading);
					}
				}

				if ($options['useVariants'] == 'true') {
					$variantz = array_values($variants);
					if ($printMain && !$skipName) {
						$xml->writeElement('name', $product['title'].' '.$variantz[0]['title_variant']);
					}
				}
				else{
					if ($printMain && !$skipName) {
						$xml->writeElement('name', $product['title']);
					}
				}

				$tmpRec = '';

				if ($options['useRelated'] == "true" && strlen($product['related'])>0){//////  rec
					$tmpRec = explode(',', $product['related']);
					$tmpRec = self::getIdByCode($tmpRec);
				}
				if ($options['useAdditionalCats'] == 'true' && !in_array($product['cat_id'], $catsForExport)) {
					$tmp = explode(',', $row['inside_cat']);
					foreach ($tmp as $in_cat) {
						if (in_array($in_cat, $catsForExport)) {
							$product['cat_id'] = $in_cat;
							break;
						}
					}
				}
				if ($printMain) {
					$prodRate = 1 + (float)$product['rate'];
					if ($options['useCode'] == "true") {$xml->writeElement('vendorCode', $product['code']);}
					$xml->writeElement('url', $url);
					$xml->writeElement('price', round($product['price']*$prodRate,2));
					if ($options['useOldPrice'] == "true" && $product['price'] < $product['old_price']){$xml->writeElement("oldprice", round($product['old_price']*$prodRate,2));}
					$xml->writeElement('currencyId', $product['currency_iso']);
					$xml->writeElement('categoryId', $product['cat_id']);
				}

				$i=0;

				if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && strlen($product['description']) > 0) {
						
					$product['description'] = str_replace('&nbsp;', ' ', $product['description']);
					if ($options['useCdata'] == "true") {
						$product['description'] = strip_tags($product['description'], '<h3> <ul> <li> <p> <br>');
					}
					else{
						$product['description'] = strip_tags($product['description']);
					}
					$product['description'] = html_entity_decode(htmlspecialchars_decode($product['description']));
					$product['description'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $product['description']);
					$product['description'] = preg_replace('/[[:cntrl:]]/', '', $product['description']);
					if ($options['useCdata'] == "true") {
						$product['description'] = strip_tags($product['description'], '<h3> <ul> <li> <p> <br>');
					}
					else{
						$product['description'] = strip_tags($product['description']);
					}
					$product['description'] = preg_replace('/\s+/', ' ',$product['description']);
					$product['description'] = mb_substr(trim($product['description']), 0,2975, 'utf-8');
				}

				if ($printMain) {
					foreach ($product['images_product'] as $key => $value) {
						if ($i < 10) {
							$value = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $value);
							$xml->writeElement('picture', SITE.$ds.'uploads'.$ds.$value);
						}
						$i++;
					}

					if (is_array($tmpRec) && count($tmpRec) > 0) {
						$tmp = array_slice($tmpRec, 0, 29);
						$tmp = implode(',', $tmp);
						$xml->writeElement('rec', $tmp);
					}

					if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && strlen($product['description']) > 0) {
						$xml->writeElement('description', '<![CDATA[ '.$product['description'].' ]]>');
					}
					// if (!in_array('sales_notes', $options['customTagNames']) && strlen($product['yml_sales_notes']) > 0) {
					// 	$xml->writeElement('sales_notes', $product['yml_sales_notes']);
					// }
					if (!in_array('weight', $options['customTagNames']) && $product['weight'] > 0) {
						if ($product['weight'] < 0.001) {
							$xml->writeElement('weight', 0.001);
						}
						else{
							$xml->writeElement('weight', $product['weight']);
						}
					}
				}

				// custom tags
				$customTags = array();
				foreach ($options['customTags'] as $key => $value) {
					if ($value['type'] == 'text' && strlen($value['val']) > 0) {
						if ($printMain) {
							$xml->writeElement($value['name'], $value['val']);
						}
						$customTags[$value['name']] = $value['val'];
					}

					if ($value['type'] == 'prop' && $product['thisUserFields']['string'][$value['val']]['value']) {

						if ($printMain) {
							$xml->writeElement($value['name'], $product['thisUserFields']['string'][$value['val']]['value']);
						}
						$customTags[$value['name']] = $product['thisUserFields']['string'][$value['val']]['value'];
					}
				}

				// custom params
				$customParams = array();
				foreach ($options['customParams'] as $key => $value) {
					if ($value['type'] == 'text' && strlen($value['val']) > 0) {
						if ($printMain) {
							$xml->startElement('param');
							$xml->writeAttribute('name', $value['name']);
							$xml->text($value['val']);
							$xml->endElement();
						}

						$customParams[$value['name']] = array('val' => $value['val'], 'unit' => '');
					}
					if ($value['type'] == 'prop' && strlen($product['thisUserFields']['string'][$value['val']]['value'] > 0)) {

						if ($printMain) {
							$xml->startElement('param');
							$xml->writeAttribute('name', $value['name']);
							if (strlen($product['thisUserFields']['string'][$value['val']]['unit']) > 0) {$xml->writeAttribute('unit', $product['thisUserFields']['string'][$value['val']]['unit']);}
							$xml->text(strip_tags($product['thisUserFields']['string'][$value['val']]['value']));
							$xml->endElement();
						}
						$customParams[$value['name']] = array(
															'val' => strip_tags($product['thisUserFields']['string'][$value['val']]['value']),
															'unit' => $product['thisUserFields']['string'][$value['val']]['unit']
														);
					}
				}

				// params
				$params = array();
				if ($options['useProps'] == 'true') {

					foreach ($product['thisUserFields']['string'] as $key => $value) {

						if (!in_array($key, $options['propDisable']) && $value['active'] > 0) {

							if ($printMain) {
								$xml->startElement('param');
								$xml->writeAttribute('name', $value['name']);
								if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
								$xml->text($value['value']);
								$xml->endElement();
							}
							$params[] = $value;
						}
					}
				}

				
				if ($printMain) {
					$xml->endElement();/////////////////////////////////////////////////////////  end offer
				}

				if ($options['useVariants'] == 'true') {////////////////////////////////////////  start variants
					// for ($i=1; $i < count($variants); $i++) {
					foreach ($variants as $variant) {

						if(($variant['count'] == 0 && $options['useNull'] == 'false') || (in_array($variant['code'], $ignored))) {continue;}

						if ($variant['price'] == 0) {continue;}

						$xml->startElement('offer');////////////////////////////////////////////  start offer

						$codde = $product['id'].'V'.$variant['id'];
						$xml->writeAttribute('id', $codde);
						if ($variant['count'] == 0) {
							$xml->writeAttribute('available', 'false');
						}

						if (strlen($typeUploading) > 0) {
							$xml->writeAttribute('type', $typeUploading);
						}

						if (!$skipName) {
							$xml->writeElement('name', $product['title'].' '.$variant['title_variant']);
						}
						if ($options['useCode'] == "true") {$xml->writeElement('vendorCode', $variant['code']);}
						$xml->writeElement('url', $url);

						$varRate = 1 + (float)$variant['rate'];

						$xml->writeElement('price', round($variant['price']*$varRate,2));
						if ($options['useOldPrice'] == 'true' && $variant['price'] < $variant['old_price']){$xml->writeElement('oldprice', round($variant['old_price']*$varRate,2));}
						$xml->writeElement('currencyId', $variant['currency_iso']);
						$xml->writeElement('categoryId', $product['cat_id']);

						if (strlen($variant['image']) > 0) {
							$j=1;
							$folder = floor($row['id']/100)*100;
							if ($folder == 0) {$folder = '000';}
							$imgPath = SITE.$ds.'uploads'.$ds.'product'.$ds.$folder.$ds.$row['id'].$ds.$variant['image'];
							$imgPath = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $imgPath);
							$xml->writeElement('picture', $imgPath);
						}
						else{
							$j=0;
						}

						foreach ($product['images_product'] as $key => $value) {
							if ($j < 10) {
								$value = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $value);
								$xml->writeElement('picture', SITE.$ds.'uploads'.$ds.$value);
							}
							$j++;
						}

						if ($options['useRelated'] == 'true' && strlen($product['related'])>0){//////  rec

							if (is_array($tmpRec) && count($tmpRec) > 0) {
								$tmp = array();
								foreach ($variants as $var) {
									foreach ($tmpRec as $rec) {
										$tmp[] = $rec.'V'.$var['id'];
									}
								}
								$tmp = array_slice($tmp, 0, 29);
								$tmp = implode(',', $tmp);
								$xml->writeElement('rec', $tmp);
							}
						}

						if ($options['skipDesc'] == "false" && !in_array('description', $options['customTagNames']) && strlen($product['description']) > 0) {
							$xml->writeElement('description', '<![CDATA[ '.$product['description'].' ]]>');							
							// $xml->writeElement('description', '<![CDATA[ '.MG::textMore(html_entity_decode(htmlspecialchars_decode($product['description'])), 2975).' ]]>');
						}
						// if (!in_array('sales_notes', $options['customTagNames']) && strlen($product['yml_sales_notes']) > 0) {
						// 	$xml->writeElement('sales_notes', $product['yml_sales_notes']);
						// }
						if (!in_array('weight', $options['customTagNames']) && $variant['weight'] > 0) {
							if ($variant['weight'] < 0.001) {
								$xml->writeElement('weight', 0.001);
							}
							else{
								$xml->writeElement('weight', $variant['weight']);
							}
						}

						//  custom tags
						foreach ($customTags as $key => $value) {
							$xml->writeElement($key, $value);
						}

						//  custom params
						foreach ($customParams as $key => $value) {
							$xml->startElement('param');
							$xml->writeAttribute('name', $key);
							if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
							$xml->text($value['val']);
							$xml->endElement();
						}

						//  params
						foreach ($params as $key => $value) {
							$xml->startElement('param');
							$xml->writeAttribute('name', $value['name']);
							if (strlen($value['unit']) > 0) {$xml->writeAttribute('unit', $value['unit']);}
							$xml->text($value['value']);
							$xml->endElement();
						}

						// size+color
						// mg::loger($product['thisUserFields']['color'][$variant['color']]);
						if ($options['useProps'] == 'true' && 
							is_array($product['thisUserFields']['color'][$variant['color']]) && 
							!in_array($product['thisUserFields']['color'][$variant['color']]['id'], $options['propDisable'])
							) {

							$xml->startElement('param');
							$xml->writeAttribute('name', $product['thisUserFields']['color'][$variant['color']]['name']);
							$xml->text($product['thisUserFields']['color'][$variant['color']]['value']);
							$xml->endElement();
						}

						if ($options['useProps'] == 'true' && 
							is_array($product['thisUserFields']['size'][$variant['size']]) && 
							!in_array($product['thisUserFields']['size'][$variant['size']]['id'], $options['propDisable'])) {
							
							$xml->startElement('param');
							$xml->writeAttribute('name', $product['thisUserFields']['size'][$variant['size']]['name']);
							if (strlen($product['thisUserFields']['size'][$variant['size']]['unit']) > 0) {$xml->writeAttribute('unit', $product['thisUserFields']['size'][$variant['size']]['unit']);}
							$xml->text($product['thisUserFields']['size'][$variant['size']]['value']);
							$xml->endElement();
						}

						$xml->endElement();/////////////////////////////////////////////  end offer
					}
				}///////////////////////////////////////////////////////////////////////  end variants
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
}