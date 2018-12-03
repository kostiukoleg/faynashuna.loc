<?php

/**
 * Класс Property - предназначен для работы с характеристиками.
 *
 * @package moguta.cms
 * @subpackage Libraries
 */

class Property {

	static private $_instance = null;

	/**
	 * Создает характеристики с нуля, для таких моментов как например импорт товаров.
	 * <code>
	 * $res = Property::createProp('name1');
	 * viewData($res);
	 * </code>
	 * @param string $name название характеристики 
	 * @param string $type тип характеристики 
	 * @return int возвращает id характеристики
	 */
	public static function createProp($name, $type = 'string') {
		// если текстареа, то меняем тип
		if(substr_count($name, '[textarea]') == 1) {
			$name = trim(str_replace('[textarea]', '', $name));
			$type = 'textarea';
		}
		// проверка наличия характеристики с таким именем
		$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($name));
		if($row = DB::fetchAssoc($res)) {
			return $row['id'];
		} else {
			DB::query('INSERT INTO '.PREFIX.'property (name, type, activity) VALUES ('.DB::quote($name).', '.DB::quote($type).', 1)');
		}
		// делаем запрос для получения свежего id новой только созданной характеристики
		return DB::insertId();
	}

	/**
	 * Создает связки категории с характеристикой.
	 * <code>
	 * $propId = 1;
	 * $catId = 12;
	 * Property::createPropToCatLink($propId, $catId);
	 * </code>
	 * @param int $propId id товара
	 * @param int $catId id категории
	 */
	public static function createPropToCatLink($propId, $catId) {
		// проверка наличия связки
		$res = DB::query('SELECT * FROM '.PREFIX.'category_user_property WHERE
			category_id = '.DB::quoteInt($catId).' AND property_id = '.DB::quoteInt($propId));
		// если связи нет, то создаем
		if(!$row = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'category_user_property (category_id, property_id) VALUES
				('.DB::quoteInt($catId).', '.DB::quoteInt($propId).')');
		}
	}

	/**
	 * Создает строковую характеристику для товара.
	 * <code>
	 * $text = 'Значение характеристики';
	 * $productId = 12;
	 * $propId = 1;
	 * Property::createProductStringProp($text, $productId, $propId);
	 * </code>
	 * @param string $text значение характеристики
	 * @param int $productId id товара
	 * @param int $propId id характеристики
	 */
	public static function createProductStringProp($text = '', $productId, $propId) {
		// проверяем наличие для нее свойства
		$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE
			prop_id = '.DB::quoteInt($propId).' AND product_id = '.DB::quoteInt($productId));
		if(!$row = DB::fetchAssoc($res)) {
			// добавляем саму строку к характеристике
			DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, product_id, name, active) VALUES
				('.DB::quoteInt($propId).', '.DB::quoteInt($productId).', '.DB::quote($text).', 1)');
		}
	}

	/**
	 * Добваления к характеристике ее свойств (к товару) работает по ссылке.
	 * <code>
	 * 	Property::addDataToProp($product['property'], $product['id']);
	 * 	viewData($property);
	 * </code>
	 * @param array $prop характеристика, является ссылкой
	 * @param int $productId id товара
	 */
	public static function addDataToProp(&$prop, $productId) {
		foreach ($prop as $key => $value) {
			if(!empty($value['property_id'])) {
				$propId = $value['property_id'];
			} else {
				$propId = $value['id'];
			}
		  	$data = null;
		  	if(($value['type'] == 'string')||($value['type'] == 'textarea')) {
		  		$res = DB::query("
						SELECT * FROM ".PREFIX."product_user_property_data
							WHERE product_id = ".DB::quoteInt($productId)." AND prop_id = ".DB::quoteInt($propId));
	  			while ($userFieldsData = DB::fetchAssoc($res)) {
	  		  		$data[] = $userFieldsData;
	  			}
	  			$table = 'product_user_property_data';
		  	} else {
	  		  	$res = DB::query("
	  		  		SELECT pupd.*, pd.name AS name_orig, pd.margin AS margin_orig
	  						FROM ".PREFIX."property_data AS pd
	  						LEFT JOIN ".PREFIX."product_user_property_data AS pupd
	  							ON pupd.prop_data_id = pd.id
	  					    WHERE pupd.product_id = ".DB::quoteInt($productId)." AND pupd.prop_id = ".DB::quoteInt($propId).' ORDER BY pd.sort');
	  		  	while ($userFieldsData = DB::fetchAssoc($res)) {
	  		  		if(empty($userFieldsData['name'])) $userFieldsData['name'] = $userFieldsData['name_orig'];
	  		  		if(empty($userFieldsData['margin']) && $userFieldsData['margin'] === 0) $userFieldsData['margin'] = $userFieldsData['margin_orig'];
		    		$data[] = $userFieldsData;
	  		  	}
	  		  	$table = 'property_data';
		  	}

		  	if((LANG != '')&&(LANG != 'LANG')&&(LANG != 'default')) {
		  	  if($data != null) {
		  	  	$idsVar = array();
		  	    foreach ($data as $item) {
		  	    	if(empty($item['prop_data_id'])) {
		  	    		$idsVar[] = $item['id'];
		  	    	} else {
		  	    		$idsVar[] = $item['prop_data_id'];
		  	    	}
		  	    }
		  	    $localeData = array();
		  	    $res = DB::query('SELECT `id_ent`, `field`, `text` FROM '.PREFIX.'locales WHERE 
		  	      `id_ent` IN ('.implode(',', $idsVar).') AND `table` = '.DB::quote($table).' AND locale = '.DB::quote(LANG));
		  	    while($row = DB::fetchAssoc($res)) {
		  	      $localeData[$row['id_ent']][$row['field']] = $row['text'];
		  	    }
		  	    foreach ($data as $keyIn => $item) {
		  	      foreach ($item as $keyIn2 => $item2) {
		  	        if($table == 'property_data') {
		  	        	if(!empty($localeData[$item['prop_data_id']][$keyIn2])) $data[$keyIn][$keyIn2] = $localeData[$item['prop_data_id']][$keyIn2];
		  	        } else {
		  	        	if(!empty($localeData[$item['id']][$keyIn2])) $data[$keyIn][$keyIn2] = $localeData[$item['id']][$keyIn2];
		  	        }
		  	        
		  	      }
		  	    }
		  	  }
		  	}

		  	if($data != null) {
		    	$prop[$key]['data'] = array();
		    	foreach ($data as $elem) {
	      			$prop[$key]['data'][] = $elem;
		    	}
		  	}

		  	// для текстареа подругому
		  	if($prop[$key]['type'] == 'textarea') {
		  		if($prop[$key]['data']) {
			    	$prop[$key]['value'] = htmlspecialchars_decode($prop[$key]['data'][0]['name']);
			    	unset($prop[$key]['data']);
			    }
		  	}
		}
	}

	/**
	 * Возвращает строку со значениями сложных характеристик для экспорта в CSV.
	 * <code>
	 * 	$productId = 13;
	 * 	$res = Property::getHardPropToCsv($productId);
	 * 	viewData($res);
	 * </code>
	 * @param int $productId id товара
	 * @return string
	 */
	public static function getHardPropToCsv($id) {
		// получаем списко свойств характеристик
		$res = DB::query("
			SELECT DISTINCT pupd.name, pupd.margin, pupd.active, pupd.type_view, pd.name AS name_orig, p.id,
				p.name AS prop_name, p.type, p.activity, p.filter, p.description, pd.margin AS margin_orig
			FROM ".PREFIX."product_user_property_data AS pupd
			LEFT JOIN ".PREFIX."property AS p
				ON p.id = pupd.prop_id
			LEFT JOIN ".PREFIX."property_data AS pd
				ON pupd.prop_data_id = pd.id
			WHERE pupd.product_id = ".DB::quoteInt($id)." AND ((p.type = 'assortment') OR (p.type = 'assortmentcheckbox'))");
		while ($row = DB::fetchAssoc($res)) {
			$prop[] = $row;
		}

		// массив с настройками сформированный
		$propArr = array();

		// структуируем полученные данные
		foreach ($prop as $item) {
			if(empty($propArr[$item['id']])) {
				$propArr[$item['id']]['prop_name'] = $item['prop_name'];
				$propArr[$item['id']]['type'] = $item['type'];
				$propArr[$item['id']]['activity'] = $item['activity'];
				$propArr[$item['id']]['filter'] = $item['filter'];
				$propArr[$item['id']]['description'] = $item['description'];
			}
			if($item['name'] == '') $item['name'] = $item['name_orig'];
			if($item['margin'] == '') $item['margin'] = $item['margin_orig'];
			$propArr[$item['id']]['val'] .= $item['name'].'#'.$item['margin'].'#'.$item['active'].'#'.$item['type_view'].'#|';
			$propArr[$item['id']]['margin'] .= $item['name_orig'].'#'.$item['margin_orig'].'#|';
		}
		// формируем строку с результатом для записи в файл
		$res = '';
		foreach ($propArr as $item) {
			$val = substr($item['val'], 0, -1);
			$margin = substr($item['margin'], 0, -1);
			$res .= $item['prop_name']."=[type=".$item['type']." value=".$val." product_margin=".$margin.
				" activity=".$item['activity']." filter=".$item['filter']." description=".$item['description']."]&";
		}
		$result = substr($res, 0, -1);

		return $result;
	}

	/**
	 * Возвращает массив с именами простых характеристик для оглавления столбцов в файле.
	 * <code>
	 * 	$res = Property::getEasyPropNameToCsv();
	 * 	viewData($res);
	 * </code>
	 * @return array
	 */
	public static function getEasyPropNameToCsv($listProductId = NULL) {
		unset($_SESSION['export']['propColumns']);
		// подбор категорий если надо
		if($listProductId) {
			$res = DB::query('SELECT DISTINCT cat_id FROM '.PREFIX.'product WHERE id IN ('.DB::quoteIN($listProductId).')');
			while($row = DB::fetchAssoc($res)) {
				$catIds[] = $row['cat_id'];
			}
			$res = DB::query('SELECT DISTINCT property_id FROM '.PREFIX.'category_user_property WHERE category_id IN ('.DB::quoteIN($catIds).')');
			while($row = DB::fetchAssoc($res)) {
				$propIds[] = $row['property_id'];
			}
			$propWhere = ' AND id IN ('.DB::quoteIN($propIds).')';
		} else {
			$propWhere = '';
		}
		$count = 0;
		$res = DB::query("SELECT id, name, type FROM ".PREFIX."property WHERE 
			type IN ('string', 'textarea', 'size', 'color')".$propWhere);
		while($row = DB::fetchAssoc($res)) {
			if(($row['type'] == "size")||($row['type'] == "color")||($row['type'] == "textarea")) {
				$type = '['.$row['type'].']';
			} else {
				$type = '';
			}
			$result[] = $row['name']." ".$type;

			// для запоминания порядка столбцов
			$_SESSION['export']['propColumns'][$row['id']] = $count++;
		}

		return $result;
	}

	/**
	 * Возвращает массив со значениями простых характеристик с учетом порядка их расположения.
	 * <code>
	 * 	$productId = 13;
	 * 	$colorId = 12;
	 * 	$sizeId = 4;
	 * 	$res = Property::getEasyPropToCsv($productId, $colorId, $sizeId);
	 * 	viewData($res);
	 * </code>
	 * @param int $id id товара
	 * @param int $color id цвета
	 * @param int $size id размера
	 * @return array
	 */
	public static function getEasyPropToCsv($id, $color, $size) {
		if(empty($color)||empty($size)) {
			if(empty($color)) {
				$neededProp = $size;
			} else {
				$neededProp = $color;
			}
		} else {
			if(!(empty($color)&&empty($size))) {
				$neededProp = $color.','.$size;
			} else {
				$neededProp = '';
			}
		}
		if(!empty($neededProp))
			$neededProp = " OR (pupd.product_id = ".DB::quoteInt($id)." AND pd.id IN (".DB::quote($neededProp, true)."))";
		$res = DB::query("
			SELECT pupd.name, p.id, pd.name AS name_orig, pd.color
			FROM ".PREFIX."product_user_property_data AS pupd
				LEFT JOIN ".PREFIX."property AS p
					ON p.id = pupd.prop_id
				LEFT JOIN ".PREFIX."property_data AS pd
					ON pd.id = pupd.prop_data_id
			  WHERE (pupd.product_id = ".DB::quoteInt($id)." AND p.type IN ('string', 'textarea'))".$neededProp);
		while ($row = DB::fetchAssoc($res)) {
			$color = !empty($row['color']) ? ' ['.$row['color'].']' : '';
			if(empty($row['name'])) {
				$names[$_SESSION['export']['propColumns'][$row['id']]] = str_replace(array("\r", "\n"), "", $row['name_orig']).$color;
			} else {
				$names[$_SESSION['export']['propColumns'][$row['id']]] = str_replace(array("\r", "\n"), "", $row['name']).$color;
			}
		}

		// формируем строку для записи
		for($i = 0; $i < count($_SESSION['export']['propColumns']); $i++) {
			$result[] = $names[$i];
		}

		return $result;
	}

	/**
	 * Создает сложные характеристики при импорте из CSV.
	 * <code>
	 * 	$productId = 13;
	 * 	$colorId = 12;
	 * 	$sizeId = 4;
	 * 	$res = Property::getEasyPropToCsv($productId, $colorId, $sizeId);
	 * 	viewData($res);
	 * </code>
	 * @param string $data строка с характеристикой
	 * @param int $productId id цвета
	 * @param int $catId id размера
	 * @return array
	 */
	public static function createHardPropFromCsv($data, $productId, $catId) {
		if(empty($data)) return false;
		// дробим данные для дальнейшей работы с ними
		$listProperty = str_replace('&amp;', '[[amp]]', $data);

		$params = explode('&', $listProperty);
		$paramsarr = array();
		foreach($params as $value) {
		  $value = str_replace('[[amp]]', '&', $value);
		  if (stristr($value, '=[')!== FALSE&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
		    &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
		    $tmp = explode('=[', $value);
		    $tmp[1] = '['.$tmp[1];
		  } else {
		    $tmp = explode('=', $value);
		  }      
		  $arrProperty[$tmp[0]] = $tmp[1];
		}
		// разбиваем полученные характеристики на данные пригодные для работы
		// =========================================
		// 	   значения характеристик в формате
		// 	   для $value_prop, $product_margin
		// =========================================
		//	$value_prop[0] = значение
		//	$value_prop[1] = наценка
		//	$value_prop[2] = параметр активности
		//	$value_prop[3] = тип вывода
		// =========================================
		foreach($arrProperty as $key => $value) {
		  $type = 'string';
		  $data = '';
		  // Если характеристика сложная, то выделим параметры - тип, значение, наценки.
		  if ($value[0]=='['&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
		    &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
		    if(preg_match("/type=([^&]*)value/", $value, $matches))  {
		      $type = trim($matches[1]);
		    }
		    if(preg_match("/value=([^&]*)product_margin/", $value, $matches))  {
		      $tmp = explode('|', trim($matches[1]));
		      $value_prop = array();
		      foreach ($tmp as $item) {
		      	$value_prop[] = explode('#', $item);
		      }		      
		    }
		    if(preg_match("/product_margin=([^&]*)activity/", $value, $matches))  {
		    	$tmp = explode('|', trim($matches[1]));
		    	$product_margin = array();
		    	foreach ($tmp as $item) {
		    		$product_margin[] = explode('#', $item);
		    	}		      
		    }
		    if(preg_match("/activity=([^&]*)filter/", $value, $matches))  {
		      $activity = trim($matches[1]);
		    }
		    if(preg_match("/filter=([^&]*)description/", $value, $matches))  {
		      $filter = trim($matches[1]);
		    }
		    if(preg_match("/description=([^&]*)]/", $value, $matches))  {
		      $description = trim($matches[1]);
		    }
		    $value = $value_prop;
		  }

		  $info['name'] = $key; 
		  $info['type'] = $type; 
		  $info['userProp'] = $value_prop; 
		  $info['propData'] = $product_margin; 
		  $info['active'] = $activity; 
		  $info['filter'] = $filter; 
		  $info['description'] = $description; 
		  $property[] = $info;
		}

		// обрабатываем характеристики
		foreach ($property as $item) {
			if(empty($_SESSION['import']['hardPropertyId'][$item['name']])) {
				// проверяем наличие характеристики, в итоге получаем ее id, если ее не было, то вставляем
				$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($item['name']));
				if($id = DB::fetchAssoc($res)) {
					$_SESSION['import']['hardPropertyId'][$item['name']] = $id['id'];
				} else {
					DB::query('INSERT INTO '.PREFIX.'property (name, type, activity, filter, description) VALUES 
						('.DB::quote($item['name']).', '.DB::quote($item['type']).', '.DB::quoteInt($item['active']).', 
						'.DB::quoteInt($item['filter']).', '.DB::quote($item['description']).')');
					$_SESSION['import']['hardPropertyId'][$item['name']] = DB::insertId();
				}
			}
			// вставка данных для характеристики
			foreach ($item['propData'] as $propData) {
				if(empty($_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]])) {
					$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE prop_id = '.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).'
						AND name = '.DB::quote($propData[0]));
					if($propId = DB::fetchAssoc($res)) {
						$_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]] = $propId['id'];
					} else {
						DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, margin) VALUES 
							('.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).', 
							'.DB::quote($propData[0]).', '.DB::quote($propData[1]).')');
						$_SESSION['import']['hardPropertyDataId'][$item['name']][$propData[0]] = DB::insertId();
					}
				}
			}
			// после того как характеристика точно создана и параметры для нее, создаем параметры для самого товара
			foreach ($item['userProp'] as $userProp) {
				$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE prop_id = '.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).'
					AND name = '.DB::quote($userProp[0]).' AND product_id = '.DB::quoteInt($productId));
				if($userPropId = DB::fetchAssoc($res)) {
					DB::query('UPDATE '.PREFIX.'product_user_property_data SET margin = '.DB::quote($userProp[1]).', 
						active = '.DB::quote($userProp[2]).', type_view = '.DB::quote($userProp[3]).' WHERE id = '.DB::quoteInt($userPropId['id']));
				} else {
					DB::query('INSERT INTO '.PREFIX.'product_user_property_data (prop_id, prop_data_id, product_id, name, margin, active, type_view) VALUES 
						('.DB::quoteInt($_SESSION['import']['hardPropertyId'][$item['name']]).', 
						'.DB::quoteInt($_SESSION['import']['hardPropertyDataId'][$item['name']][$userProp[0]]).', '.DB::quoteInt($productId).', 
						'.DB::quote($userProp[0]).', '.DB::quote($userProp[1]).', '.DB::quoteInt($userProp[2]).', '.DB::quote($userProp[3]).')');
				}
			}
			// привязываем характеристику к категории товара
			// проверяем наличие привязки
			self::createPropToCatLink($_SESSION['import']['hardPropertyId'][$item['name']], $catId);
		}
	} 

	/**
	 * Создает характеристику размера и цвета при импорте из CSV и сразу прикрепляет ее к товару.
	 * <code>
	 * 	$propName = 'Цвет корпуса';
	 * 	$val = 'Белый';
	 * 	$productId = '13';
	 * 	$variant = 'Белый';
	 * 	$catId = '5';
	 * 	Property::createSizeMapPropFromCsv($propName, $val, $productId, $variant, $catId);
	 * </code>
	 * @param string $propName название характеристики
	 * @param string $val значение характеристики
	 * @param int $productId id товара
	 * @param int $variant id цвета
	 * @param int $catId id размера
	 */
	public static function createSizeMapPropFromCsv($propName, $val, $productId, $variant, $catId) {
		// определяем тип характеристики
		if(substr_count($propName, '[size]') == 1) {
			$type = 'size';
			$propName = trim(str_replace('[size]', '', $propName));
		}
		if(substr_count($propName, '[color]') == 1) {
			$type = 'color';
			$propName = trim(str_replace('[color]', '', $propName));
			$tmp = explode('[', $val);
			$val = trim($tmp[0]);
			$color = str_replace(']', '', $tmp[1]);
		}
		if(empty($type)) return false;
		// создаем характеристику
		// проверяем наличие характеристики
		$res = DB::query('SELECT id FROM '.PREFIX.'property WHERE name = '.DB::quote($propName));
		if(!$propId = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'property (name, type) VALUES ('.DB::quote($propName).', '.DB::quote($type).')');
			$propId = DB::insertId();
		} else {
			$propId = $propId['id'];
		}
		// прикрепляем значение к характеристике
		$res = DB::query('SELECT id FROM '.PREFIX.'property_data WHERE name = '.DB::quote($val).' AND prop_id = '.DB::quoteInt($propId));
		if(!$dataId = DB::fetchAssoc($res)) {
			DB::query('INSERT INTO '.PREFIX.'property_data (prop_id, name, color) VALUES 
				('.DB::quoteInt($propId).', '.DB::quote($val).', '.DB::quote($color).')');
			$dataId = DB::insertId();
		} else {
			$dataId = $dataId['id'];
		}
		// прикрепляем значение к товару
		$res = DB::query('SELECT id FROM '.PREFIX.'product_user_property_data WHERE name = '.DB::quote($val).' AND product_id = '.DB::quoteInt($productId));
		if($userPropId = DB::fetchAssoc($res)) {
			DB::query('UPDATE '.PREFIX.'product_user_property_data SET name = '.DB::quote($val).' WHERE id = '.$userPropId['id']);
		} else {
			DB::query('INSERT INTO '.PREFIX.'product_user_property_data (product_id, prop_id, prop_data_id, name) VALUES 
				('.DB::quoteInt($productId).', '.DB::quoteInt($propId).', '.DB::quoteInt($dataId).', '.DB::quote($val).')');
		}
		// привязываем характеристику к категории товара
		self::createPropToCatLink($propId, $catId);
		// добавляем варианту товара размерную сетку
		DB::query('UPDATE '.PREFIX.'product_variant SET '.DB::buildPartQuery(array($type => $dataId)).' WHERE 
			title_variant = '.DB::quote($variant).' AND product_id = '.DB::quote($productId));
	}

   /**
    * Возвращает единственный экземпляр данного класса.
    * <code>
    * $property = Property::getInstance();
    * </code>
    * @return object
    */
	static public function getInstance() {
	  if (is_null(self::$_instance)) {
	    self::$_instance = new self;
	  }
	  return self::$_instance;
	}

	/**
	 * Инициализация
	 * @access private
	 * @return object
	 */
	public static function init() {
	  	self::getInstance();
	}
	
	/**
	 * Возвращает список всех групп характеристик.
	 * <code>
	 * 	$res = Property::getPropertyGroup();
	 *  viewData($res);
	 * </code>
	 * @param bool $mod
	 * @return array
	 */	
	public static function getPropertyGroup($mod = false) {
		$result = array();
		$res = DB::query("SELECT * FROM ".PREFIX."property_group WHERE 1=1 ORDER BY sort ASC");
		while($row = DB::fetchAssoc($res)) {
			MG::loadLocaleData($row['id'], LANG, 'property_group', $row);
			if($mod) {
			 	$result[$row['id']] = $row;
			} else {
			 // для js не создаем ключи в массиве, иначе массив читается как объект
		  	 	$result[] = $row;
			}
		}
		return $result;
	}
	/**
	 * Добавляет группу характеристик.
	 * <code>
	 * 	$name = 'NewGropup';
	 * 	$res = Property::addPropertyGroup($name);
	 *  viewData($res);
	 * </code>
	 * @param string $name название группы
	 * @return bool true
	 */	
	public static function addPropertyGroup($name) {	
	  	DB::query('INSERT INTO '.PREFIX.'property_group (name, sort) VALUES ('.DB::quote($name).', 0)');
      	$id = DB::insertId();
	  	DB::query('UPDATE '.PREFIX.'property_group SET sort = '.DB::quoteInt($id).' WHERE id = '.DB::quoteInt($id));		
	  	return true;
	}
		
	/**
	 * Удаляет группу характеристик.
	 * <code>
	 * $res = Property::addPropertyGroup(12);
	 * var_dump($res);
	 * </code>
	 * @param int $id группы характеристик
	 * @return bool true
	 */	
	public static function deletePropertyGroup($id) {	
	  	DB::query('DELETE FROM `'.PREFIX.'property_group` WHERE `'.PREFIX.'property_group`.`id` = '.DB::quoteInt($id));	
	  	return true;
	}	
	
	/**
	 * Сортирует список строковых характеристик на два массива, с группами и без. В соответствии с заданной сортировкой групп и характеристик в них.
	 * @param array $data массив строковых характеристик
	 * @param bool $returnArray
	 */
	public static function sortPropertyToGroup($data, $returnArray = false) {	
		// viewData($data);
		$unGroupProperty = array();
		$groupProperty = array();

		foreach ($data['stringsProperties'] as $key=>$item) {		
			if(is_array($item) && empty($item[0]['name']) || empty($item)) {
			 	continue;
			}
			
			if(!empty($item[0]['group_prop'])) {
				$groupKey = $item[0]['group_prop']['name'];								  
				$groupProperty[$groupKey]['name_group'] = $item[0]['group_prop']['name'];			
				$groupProperty[$groupKey]['priority'] = $item[0]['group_prop']['sort'];	
				
				$groupProperty[$groupKey]['property'][] = array(
				'key_prop' => $key,
				'name_prop' => $item[0]['name'],
				'priority' => $item[0]['priority'],
				'unit' => $item[0]['unit'],
				);								  
			} else {

				if(is_array($item)) {
					$item[0]['name_prop'] = $key;
					$unGroupProperty[] = $item[0];
				} else {
					$unGroupProperty[] = array('name_prop' => $key,'name' => $item);
				}
			}
		}
		usort($groupProperty, array("MG", "priority"));
		usort($unGroupProperty, array("MG", "priority"));

		if ($returnArray) {
			$result = array('groupProperty' => $groupProperty,'unGroupProperty' => $unGroupProperty);	
		} else {
			$result = MG::layoutManager('layout_prop_string', array('groupProperty' => $groupProperty,'unGroupProperty' => $unGroupProperty));	
		}
    
    	return $result;
	}

	public static function saveShortIdProp($prop) {
		foreach ($prop['data'] as $key => $value) {
			if($value['value_type'] == 'pp') {
				$shortId = NULL;
				$res = DB::query('SELECT id FROM '.PREFIX.'short_prop_id WHERE prop_value = '.DB::quote($value['value_name']).'
					AND prop = '.DB::quoteInt($prop['idProp']));
				while($row = DB::fetchAssoc($res)) {
					$shortId = $row['id'];
				}
				if(!$shortId) {
					DB::query('INSERT '.PREFIX.'short_prop_id SET prop_value = '.DB::quote($value['value_name']).', 
						prop = '.DB::quoteInt($prop['idProp']).', prop_ids = '.DB::quote($value['value_id']));
					$shortId = DB::insertId();
				} else {
					DB::query('UPDATE '.PREFIX.'short_prop_id SET prop_ids = '.DB::quote($value['value_id']).'
						WHERE id = '.DB::quoteInt($shortId));
				}
				if($shortId) {
					$prop['data'][$key]['value_type'] = 'short';
					$prop['data'][$key]['value_id'] = $shortId;
				}
			}
		}
		return $prop;
	}

	public static function getIdsByShortId($propId) {
		$res = DB::query('SELECT prop_ids FROM '.PREFIX.'short_prop_id WHERE id = '.DB::quoteInt($propId));
		while($row = DB::fetchAssoc($res)) {
			$data = $row['prop_ids'];
		}
		return $data;
	}
}